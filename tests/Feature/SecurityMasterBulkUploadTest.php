<?php

namespace Tests\Feature;

use App\Models\MarketCategory;
use App\Models\PendingSecurityMasterData;
use App\Models\ProductType;
use App\Models\SecurityManagement;
use App\Services\ExternalUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression tests for product-type field resolution on security master create / bulk upload.
 *
 * Background: fields added to a product type after its creation were saved with a
 * category_id that did not match the product's market category. The create and bulk
 * upload paths filtered fields on category_id AND product_id, so those fields were
 * silently dropped, while the configuration endpoint (product_id only) listed them.
 */
class SecurityMasterBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    protected MarketCategory $bonds;
    protected MarketCategory $moneyMarket;
    protected ProductType $promissoryNotes;

    /** @var array<string,int> field name => id */
    protected array $fieldIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // APP_URL carries a sub-folder (http://localhost/smlars_api/) which would be
        // prepended to every test request path; route the tests against the app root.
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        // No external user directory in tests: no authoriser e-mails are sent.
        $this->mock(ExternalUserService::class, function ($mock) {
            $mock->shouldReceive('getUserById')->andReturn(null);
            $mock->shouldReceive('getAllUsers')->andReturn(collect());
            $mock->shouldReceive('enrichWithUsers')->andReturnUsing(fn ($items) => $items);
        });

        $this->bonds = MarketCategory::create(['name' => 'Bonds', 'code' => 'BONDS', 'is_active' => true]);
        $this->moneyMarket = MarketCategory::create(['name' => 'Money Market (SML)', 'code' => 'MMKT_SML', 'is_active' => true]);

        $this->promissoryNotes = ProductType::create([
            'market_category_id' => $this->moneyMarket->id,
            'name' => 'Promissory Notes (SML)',
            'code' => 'PROMISSORY_NOTES_SML',
            'is_active' => true,
        ]);

        // Fields created together with the product carry the product's own category...
        $this->field('Issuer', $this->moneyMarket->id, required: true);
        $this->field('Maturity Date', $this->moneyMarket->id);

        // ...while fields added later were saved under a different category (Bonds).
        $this->field('ISIN', $this->bonds->id, required: true);
        $this->field('Security Name', $this->bonds->id);
        $this->field('Coupon (%)', $this->bonds->id, type: 'Float');
    }

    protected function field(string $name, int $categoryId, string $type = 'Text', bool $required = false): int
    {
        $field = SecurityManagement::create([
            'category_id' => $categoryId,
            'product_id' => $this->promissoryNotes->id,
            'field_name' => $name,
            'field_type' => $type,
            'required' => $required,
            'status' => 1,
        ]);

        return $this->fieldIds[$name] = $field->id;
    }

    protected function upload(string $csv, array $extra = [])
    {
        return $this->withoutMiddleware()->post('/api/v1/admin/security-master/bulk-upload', array_merge([
            'file' => UploadedFile::fake()->createWithContent('securities.csv', $csv),
            'category_id' => $this->moneyMarket->id,
            'product_id' => $this->promissoryNotes->id,
            'created_by' => 1,
            'authoriser_id' => 2,
        ], $extra), ['Accept' => 'application/json']);
    }

    protected function csv(array $header, array ...$rows): string
    {
        return implode("\n", array_map(fn ($r) => implode(',', $r), [$header, ...$rows])) . "\n";
    }

    #[Test]
    public function bulk_upload_saves_fields_added_to_the_product_after_its_creation()
    {
        $response = $this->upload($this->csv(
            ['Issuer', 'Maturity Date', 'ISIN', 'Security Name', 'Coupon (%)'],
            ['FGN', '2031-09-01', 'NGFGN0000001', 'FGN PN 2031', '12.5'],
        ));

        $response->assertOk()->assertJson(['warnings' => []]);

        $pending = PendingSecurityMasterData::sole();
        $saved = collect($pending->fields_data)->pluck('field_value', 'field_id');

        $this->assertSame('FGN PN 2031', $pending->security_name);
        $this->assertCount(5, $saved);
        $this->assertSame('NGFGN0000001', $saved[$this->fieldIds['ISIN']]);
        $this->assertSame('FGN PN 2031', $saved[$this->fieldIds['Security Name']]);
        $this->assertSame('12.5', $saved[$this->fieldIds['Coupon (%)']]);
        $this->assertSame('2031-09-01', $saved[$this->fieldIds['Maturity Date']]);
    }

    #[Test]
    public function bulk_upload_rejects_a_file_with_an_unknown_column_before_submitting_any_row()
    {
        $response = $this->upload($this->csv(
            ['Issuer', 'ISIN', 'Yield at Issue'],
            ['FGN', 'NGFGN0000001', '18.2'],
            ['CBN', 'NGCBN0000002', '17.9'],
        ));

        $response->assertStatus(422)
            ->assertJsonPath('errors.unknown_columns', ['yield_at_issue'])
            ->assertJsonPath('errors.expected_columns', ['issuer', 'maturity_date', 'isin', 'security_name', 'coupon']);

        $this->assertStringContainsString('yield_at_issue', $response->json('errors.file.0'));
        $this->assertStringContainsString("Promissory Notes (SML)", $response->json('errors.file.0'));
        $this->assertSame(0, PendingSecurityMasterData::count(), 'No row should be submitted when the template is wrong.');
    }

    #[Test]
    public function bulk_upload_can_skip_unknown_columns_and_report_them_as_warnings()
    {
        $response = $this->upload($this->csv(
            ['Issuer', 'ISIN', 'Yield at Issue'],
            ['FGN', 'NGFGN0000001', '18.2'],
        ), ['ignore_unknown_columns' => 1]);

        $response->assertOk();

        $warnings = $response->json('warnings');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString("'yield_at_issue'", $warnings[0]);

        $saved = collect(PendingSecurityMasterData::sole()->fields_data)->pluck('field_id');
        $this->assertEqualsCanonicalizing([$this->fieldIds['Issuer'], $this->fieldIds['ISIN']], $saved->all());
    }

    #[Test]
    public function bulk_upload_matches_columns_by_field_id_as_well_as_name()
    {
        $response = $this->upload($this->csv(
            [$this->fieldIds['Issuer'], $this->fieldIds['ISIN']],
            ['FGN', 'NGFGN0000001'],
        ));

        $response->assertOk();

        $saved = collect(PendingSecurityMasterData::sole()->fields_data)->pluck('field_value', 'field_id');
        $this->assertSame('NGFGN0000001', $saved[$this->fieldIds['ISIN']]);
    }

    #[Test]
    public function bulk_upload_matches_headers_that_differ_only_in_separators()
    {
        $this->field('Average Life/TTM (years)', $this->moneyMarket->id, type: 'Float');

        // The field slugs to average_lifettm_years; users write average_life_ttm_years
        $response = $this->upload($this->csv(
            ['issuer', 'isin', 'average_life_ttm_years'],
            ['FGN', 'NGFGN0000001', '1.5'],
        ));

        $response->assertOk()->assertJson(['warnings' => []]);

        $saved = collect(PendingSecurityMasterData::sole()->fields_data)->pluck('field_value', 'field_id');
        $this->assertSame('1.5', $saved[$this->fieldIds['Average Life/TTM (years)']]);
    }

    #[Test]
    public function single_create_accepts_fields_added_to_the_product_after_its_creation()
    {
        $response = $this->withoutMiddleware()->postJson('/api/v1/admin/security-master', [
            'category_id' => $this->moneyMarket->id,
            'product_id' => $this->promissoryNotes->id,
            'created_by' => 1,
            'authoriser_id' => 2,
            'fields' => [
                ['field_id' => $this->fieldIds['Issuer'], 'field_value' => 'FGN'],
                ['field_id' => $this->fieldIds['ISIN'], 'field_value' => 'NGFGN0000001'],
                ['field_id' => $this->fieldIds['Coupon (%)'], 'field_value' => '12.5'],
            ],
        ]);

        $response->assertCreated();

        $saved = collect(PendingSecurityMasterData::sole()->fields_data)->pluck('field_value', 'field_id');
        $this->assertSame('NGFGN0000001', $saved[$this->fieldIds['ISIN']]);
    }

    #[Test]
    public function single_create_still_requires_required_fields_added_after_product_creation()
    {
        $response = $this->withoutMiddleware()->postJson('/api/v1/admin/security-master', [
            'category_id' => $this->moneyMarket->id,
            'product_id' => $this->promissoryNotes->id,
            'created_by' => 1,
            'authoriser_id' => 2,
            'fields' => [
                ['field_id' => $this->fieldIds['Issuer'], 'field_value' => 'FGN'],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString("'ISIN' is required", $response->json('errors.fields.0'));
    }

    #[Test]
    public function a_product_field_is_stored_under_the_products_market_category()
    {
        $response = $this->withoutMiddleware()->postJson('/api/v1/admin/security-management/direct', [
            'category_id' => $this->bonds->id, // wrong category sent by the client
            'product_id' => $this->promissoryNotes->id,
            'fields' => [
                ['field_name' => 'Listing Status', 'field_type' => 'Text', 'required' => true],
            ],
        ]);

        $response->assertCreated();

        $field = SecurityManagement::where('field_name', 'Listing Status')->sole();
        $this->assertSame($this->moneyMarket->id, $field->category_id);
    }
}
