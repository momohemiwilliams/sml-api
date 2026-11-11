<?php

namespace App\Imports;

use App\Services\SecurityMasterDataService;
use App\Models\MarketCategory;
use App\Models\ProductType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SecurityMasterImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * Spreadsheet columns that are not dynamic fields.
     */
    public const RESERVED_COLUMNS = ['product', 'product_type', 'status'];

    protected $service;
    protected $categoryId;
    protected $productId; // Add property
    protected $createdBy;
    protected $authoriserId;
    protected $ignoreUnknownColumns;
    protected $fieldMap;
    protected $compactMap = [];
    /** @var \Illuminate\Support\Collection Active field definitions keyed by id */
    protected $fieldDefinitions;
    protected $failures = [];
    protected $warnings = [];
    protected $amortisationScheduleMap; // [rowNumber => parsedScheduleData]
    protected $callScheduleMap; // [rowNumber => parsedCallScheduleData]
    protected $resetScheduleMap; // [rowNumber => parsedResetScheduleData]

    public function __construct(
        SecurityMasterDataService $service,
        int $categoryId,
        ?int $productId, // Accept nullable product_id
        int $createdBy,
        int $authoriserId,
        array $amortisationScheduleMap = [],
        array $callScheduleMap = [],
        array $resetScheduleMap = [],
        bool $ignoreUnknownColumns = false
    ) {
        $this->service = $service;
        $this->categoryId = $categoryId;
        $this->productId = $productId;
        $this->createdBy = $createdBy;
        $this->authoriserId = $authoriserId;
        $this->amortisationScheduleMap = $amortisationScheduleMap;
        $this->callScheduleMap = $callScheduleMap;
        $this->resetScheduleMap = $resetScheduleMap;
        $this->ignoreUnknownColumns = $ignoreUnknownColumns;

        $this->loadFieldMap();
    }

    /**
     * Non-fatal notices collected during the import (e.g. ignored columns).
     *
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Load field definitions to map Excel headers to field IDs.
     *
     * Uses the same resolver as single create/validation so a column is accepted
     * here exactly when the resulting field_id would pass validation.
     */
    protected function loadFieldMap()
    {
        $this->fieldDefinitions = $this->service->fieldDefinitionsFor($this->categoryId, $this->productId);

        $this->fieldMap = [];
        $compact = [];
        foreach ($this->fieldDefinitions as $field) {
            // Index by ID (string or int depending on Excel conversion)
            $this->fieldMap[(string)$field->id] = $field;

            // Index by normalized field name slug
            $this->fieldMap[static::slug($field->field_name)] = $field;

            $compact[static::compact($field->field_name)][] = $field;
        }

        // Index by letters/digits only, so separator differences still match:
        // "Average Life/TTM (years)" slugs to average_lifettm_years, while a user
        // naturally writes average_life_ttm_years. Skipped where two fields collide.
        $this->compactMap = [];
        foreach ($compact as $key => $fields) {
            if (count($fields) === 1) {
                $this->compactMap[$key] = $fields[0];
            }
        }
    }

    /**
     * Letters and digits only, lower-case: average_life_ttm_years => averagelifettmyears.
     */
    public static function compact($value): string
    {
        return preg_replace('/[^a-z0-9]/', '', static::slug($value));
    }

    /**
     * Resolve a spreadsheet header to an active field definition (by id, slug or compact name).
     */
    protected function fieldFor($header)
    {
        $header = (string)$header;

        return $this->fieldMap[$header]
            ?? $this->fieldMap[static::slug($header)]
            ?? $this->compactMap[static::compact($header)]
            ?? null;
    }

    /**
     * Normalise a header / field name the same way the heading row formatter does.
     */
    public static function slug($value): string
    {
        return Str::slug((string)$value, '_');
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 1; // Start after header
        $successCount = 0;

        if ($rows->isNotEmpty()) {
            $this->checkColumns($rows->first());
        }

        foreach ($rows as $row) {
            $rowNumber++;

            // Skip if row is empty (Maatwebsite SkipsEmptyRows might handle this, but being safe)
            if ($row->filter()->isEmpty()) {
                continue;
            }

            try {
                $this->processRow($row, $rowNumber);
                $successCount++;
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->failures[] = "Row {$rowNumber}: {$message}";
                    }
                }
            } catch (\Exception $e) {
                $this->failures[] = "Row {$rowNumber}: " . $e->getMessage();
                Log::error("Security Master Import Error Row {$rowNumber}: " . $e->getMessage());
            }
        }

        if (!empty($this->failures)) {
            throw ValidationException::withMessages([
                'file' => $this->failures
            ]);
        }

        Log::info("Import completed: {$successCount} successful.");
    }

    /**
     * Make sure every column in the file maps to an active field (or is a reserved
     * column). Runs before any row is submitted so a bad template fails as a whole
     * instead of silently dropping the unmatched columns from every record.
     *
     * @param  \Illuminate\Support\Collection|array  $firstRow
     */
    protected function checkColumns($firstRow): void
    {
        $headers = collect($firstRow instanceof Collection ? $firstRow->keys() : array_keys((array)$firstRow));

        $unknown = $headers
            ->filter(fn($header) => is_string($header) && trim($header) !== '')
            ->map(fn($header) => (string)$header)
            ->reject(fn($header) => in_array($header, static::RESERVED_COLUMNS, true))
            ->reject(fn($header) => $this->fieldFor($header) !== null)
            ->values();

        if ($unknown->isEmpty()) {
            return;
        }

        $scope = $this->scopeLabel();
        $expected = $this->fieldDefinitions
            ->map(fn($f) => static::slug($f->field_name))
            ->values()
            ->all();

        if ($this->ignoreUnknownColumns) {
            foreach ($unknown as $header) {
                $this->warnings[] = "Column '{$header}' was ignored: it does not match any active field for {$scope}.";
            }
            Log::warning('Security Master Import: unknown columns ignored', [
                'category_id' => $this->categoryId,
                'product_id' => $this->productId,
                'columns' => $unknown->all(),
            ]);
            return;
        }

        throw ValidationException::withMessages([
            'file' => [
                "The file has " . $unknown->count() . " column(s) that do not match any active field for {$scope}: "
                    . $unknown->implode(', ')
                    . ". No records were submitted. Fix the column headers (see expected_columns), "
                    . "or resend with ignore_unknown_columns=1 to skip these columns.",
            ],
            'unknown_columns' => $unknown->all(),
            'expected_columns' => $expected,
        ]);
    }

    /**
     * Human-readable description of the field set the file is matched against.
     */
    protected function scopeLabel(): string
    {
        if ($this->productId) {
            $product = ProductType::withTrashed()->find($this->productId);
            if ($product) {
                return "product type '{$product->name}' (id {$product->id})";
            }
            return "product type id {$this->productId}";
        }

        $category = MarketCategory::withTrashed()->find($this->categoryId);

        return $category
            ? "market category '{$category->name}' (id {$category->id})"
            : "market category id {$this->categoryId}";
    }

    protected function processRow($row, $rowNumber)
    {
        // 1. Extract static fields
        $productName = $row['product'] ?? $row['product_type'] ?? null;
        $status = isset($row['status']) ? (int)$row['status'] : 1;

        $productId = null;
        if ($productName) {
            $product = ProductType::where('name', $productName)
                ->where('market_category_id', $this->categoryId) // Ensure it belongs to the category
                ->first();

            if ($product) {
                $productId = $product->id;
            } else {
                 Log::warning("Product '{$productName}' not found for category {$this->categoryId} (Row {$rowNumber})");
                 throw new \Exception("Product '{$productName}' not found (Row {$rowNumber})");
            }
        } elseif ($this->productId) {
            // Fallback to global product_id
            $productId = $this->productId;
        } else {
             throw new \Exception("Product Type is required (Row {$rowNumber})");
        }

        // 2. Map dynamic fields
        $fieldsData = [];
        $securityName = null;

        // We loop through the row keys (headers) and check if they match any known field name or ID
        foreach ($row as $header => $value) {
            $fieldDef = $this->fieldFor($header);

            if ($fieldDef) {
                // Determine normalized value (handle Excel dates)
                $normalizedValue = $value;

                // Handle date fields or fields with 'Date' in name
                $isDateField = ($fieldDef->field_type === 'Date' || stripos($fieldDef->field_name, 'Date') !== false);

                if ($isDateField && !empty($value)) {
                    try {
                        if ($value instanceof \DateTimeInterface) {
                            $normalizedValue = Carbon::instance($value)->format('Y-m-d');
                        } elseif (is_numeric($value) && $value > 25569) { // Basic heuristic for Excel serial date
                            $normalizedValue = Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
                        } else {
                            // Try parsing string date
                            $normalizedValue = Carbon::parse((string)$value)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        // Fallback to original string if parsing fails, service will catch validation error
                        $normalizedValue = (string)$value;
                    }
                } else {
                    $normalizedValue = (string)$value;
                }

                // Extract security name specifically if this is the security name field
                if (static::slug($fieldDef->field_name) === 'security_name' || $fieldDef->id == 3) {
                    $securityName = $normalizedValue;
                }

                $fieldsData[] = [
                    'field_id' => $fieldDef->id,
                    'field_value' => $normalizedValue
                ];
            }
        }

        // 3. Construct payload for service
        $payload = [
            'category_id' => $this->categoryId,
            'product_id' => $productId,
            'security_name' => $securityName,
            'status' => $status,
            'created_by' => $this->createdBy,
            'authoriser_id' => $this->authoriserId,
            'fields' => $fieldsData
        ];

        // Require the schedule upload(s) this row's Option Type calls for
        $required = $this->service->requiredSchedules($fieldsData);
        $missing = [];
        if ($required['amortisation'] && !isset($this->amortisationScheduleMap[$rowNumber])) {
            $missing[] = "an amortisation schedule (amortisation_schedules[{$rowNumber}])";
        }
        if ($required['call'] && !isset($this->callScheduleMap[$rowNumber])) {
            $missing[] = "a call schedule (call_schedules[{$rowNumber}])";
        }
        if ($required['reset'] && !isset($this->resetScheduleMap[$rowNumber])) {
            $missing[] = "a reset schedule (reset_schedules[{$rowNumber}])";
        }
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'file' => ['The Option Type for this row requires ' . implode(' and ', $missing) . '.']
            ]);
        }

        // Attach amortisation schedule for this specific row if one was uploaded for it
        if (isset($this->amortisationScheduleMap[$rowNumber])) {
            $payload['amortisation_schedule_data'] = $this->amortisationScheduleMap[$rowNumber];
        }

        // Attach call schedule for this specific row if one was uploaded for it
        if (isset($this->callScheduleMap[$rowNumber])) {
            $payload['call_schedule_data'] = $this->callScheduleMap[$rowNumber];
        }

        // Attach coupon reset schedule for this specific row if one was uploaded for it
        if (isset($this->resetScheduleMap[$rowNumber])) {
            $payload['reset_schedule_data'] = $this->resetScheduleMap[$rowNumber];
        }

        // 4. Create request via service (handles validation)
        $this->service->createRequest($payload);
    }
}
