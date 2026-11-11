<?php

namespace App\Imports;

use App\Services\SecurityService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SecuritiesImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    protected $securityService;
    protected $requestedBy;
    protected $authoriserId;

    public function __construct(SecurityService $securityService, int $requestedBy, int $authoriserId)
    {
        $this->securityService = $securityService;
        $this->requestedBy = $requestedBy;
        $this->authoriserId = $authoriserId;
    }

    /**
     * Process each row in the collection
     */
    public function collection(Collection $rows)
    {
        $rowNumber = 1; // Start from 1 (header is row 0, already skipped by WithHeadingRow)
        $successCount = 0;
        $errorCount = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            try {
                // Skip empty rows
                if ($this->isEmptyRow($row)) {
                    \Log::info("Skipping empty row {$rowNumber}");
                    continue;
                }

                $data = $this->mapRowToData($row);
                $data['requested_by'] = $this->requestedBy;
                $data['authoriser_id'] = $this->authoriserId;

                $this->securityService->createRequest($data);
                $successCount++;
                \Log::info("Successfully processed row {$rowNumber}");
            } catch (\Exception $e) {
                $errorCount++;
                \Log::error("Security import error on row {$rowNumber}: " . $e->getMessage(), [
                    'row' => $row->toArray(),
                    'exception' => $e->getTraceAsString()
                ]);
            }
        }

        \Log::info("Import completed: {$successCount} successful, {$errorCount} errors, Total rows: " . ($rowNumber - 1));
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow($row): bool
    {
        // Check if all values in the row are null or empty
        return $row->filter(function ($value) {
            return !empty($value);
        })->isEmpty();
    }

    /**
     * Map Excel row to security data array
     */
    private function mapRowToData($row): array
    {
        return [
            'issue_category' => $row['issue_category'] ?? null,
            'issuer' => $row['issuer'] ?? null,
            'security_type_id' => $row['security_type_id'] ?? null,
            'isin' => $row['isin'] ?? null,
            'description' => $row['description'] ?? null,
            'issue_date' => $this->parseDate($row['issue_date'] ?? null),
            'maturity_date' => $this->parseDate($row['maturity_date'] ?? null),
            'tenor' => $row['tenor'] ?? null,
            'coupon' => $row['coupon'] ?? null,
            'coupon_type' => $row['coupon_type'] ?? null,
            'frm' => $row['frm'] ?? $row['floating_rate_margin'] ?? null,
            'frb' => $row['frb'] ?? $row['floating_rate_benchmark'] ?? null,
            'frbv' => $row['frbv'] ?? $row['floating_rate_benchmark_value'] ?? null,
            'coupon_floor' => $row['coupon_floor'] ?? $row['cf'] ?? null,
            'coupon_cap' => $row['coupon_cap'] ?? $row['cc'] ?? null,
            'coupon_frequency' => $row['coupon_frequency'] ?? null,
            'effective_coupon' => $row['effective_coupon'] ?? null,
            'fgn_benchmark_yield' => $row['fgn_benchmark_yield_at_issue'] ?? $row['fgn_benchmark_yield'] ?? null,
            'issue_size' => $row['issue_size'] ?? null,
            'outstanding_value' => $row['outstanding_value'] ?? null,
            'ttm' => $row['ttm'] ?? null,
            'day_count_convention' => $row['day_count_convention'] ?? null,
            'day_count_basis' => $row['day_count_basis'] ?? null,
            'option_type' => $row['option_type'] ?? null,
            'call_date' => $this->parseDate($row['call_date'] ?? null),
            'yield_at_issue' => $row['yield_at_issue'] ?? null,
            'interest_determination_date' => $this->parseDate($row['interest_determination_date'] ?? null),
            'listing_status' => $row['listing_status'] ?? null,
            'rating_1_agency' => $row['rating_1_agency'] ?? null,
            'rating_1' => $row['rating_1'] ?? null,
            'rating_1_issuance_date' => $this->parseDate($row['rating_1_issuance_date'] ?? null),
            'rating_1_expiration_date' => $this->parseDate($row['rating_1_expiration_date'] ?? null),
            'rating_2_agency' => $row['rating_2_agency'] ?? null,
            'rating_2' => $row['rating_2'] ?? null,
            'rating_2_issuance_date' => $this->parseDate($row['rating_2_issuance_date'] ?? null),
            'rating_2_expiration_date' => $this->parseDate($row['rating_2_expiration_date'] ?? null),
            'final_rating' => $row['final_rating'] ?? null,
            'product_type_id' => $row['product_type_id'] ?? null,
            'first_settlement_date' => $this->parseDate($row['first_settlement_date'] ?? null),
            'last_trading_date' => $this->parseDate($row['last_trading_date'] ?? null),
            'face_value' => $row['face_value'] ?? null,
            'issue_price' => $row['issue_price'] ?? null,
            'discount_rate' => $row['discount_rate'] ?? null,
            'amount_issued' => $row['amount_issued'] ?? null,
            'amount_outstanding' => $row['amount_outstanding'] ?? null,
            'status' => $row['status'] ?? 'Active',
            'remarks' => $row['remarks'] ?? null,
        ];
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Handle Excel date serial numbers
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }

            // Handle string dates
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
