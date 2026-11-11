<?php

namespace App\Imports;

use App\Services\SecurityMasterDataService;
use App\Models\SecurityManagement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SecurityMasterImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $service;
    protected $categoryId;
    protected $productId; // Add property
    protected $createdBy;
    protected $authoriserId;
    protected $fieldMap;
    protected $failures = [];
    protected $amortisationScheduleMap; // [rowNumber => parsedScheduleData]

    public function __construct(
        SecurityMasterDataService $service,
        int $categoryId,
        ?int $productId, // Accept nullable product_id
        int $createdBy,
        int $authoriserId,
        array $amortisationScheduleMap = []
    ) {
        $this->service = $service;
        $this->categoryId = $categoryId;
        $this->productId = $productId;
        $this->createdBy = $createdBy;
        $this->authoriserId = $authoriserId;
        $this->amortisationScheduleMap = $amortisationScheduleMap;
        
        $this->loadFieldMap();
    }

    /**
     * Load field definitions to map Excel headers to field IDs
     */
    protected function loadFieldMap()
    {
        $query = SecurityManagement::where('category_id', $this->categoryId)
            ->where('status', 1);

        if ($this->productId) {
            // Check if the product type has its own field definitions
            $productFieldCount = SecurityManagement::where('category_id', $this->categoryId)
                ->where('product_id', $this->productId)
                ->where('status', 1)
                ->count();

            if ($productFieldCount > 0) {
                $query->where('product_id', $this->productId);
            } else {
                $query->whereNull('product_id');
            }
        } else {
            $query->whereNull('product_id');
        }

        $fields = $query->get();
            
        $this->fieldMap = [];
        foreach ($fields as $field) {
            // Index by ID (string or int depending on Excel conversion)
            $this->fieldMap[(string)$field->id] = $field;
            
            // Index by normalized field name slug
            $slug = \Illuminate\Support\Str::slug($field->field_name, '_');
            $this->fieldMap[$slug] = $field;
        }
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 1; // Start after header
        $successCount = 0;

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

    protected function processRow($row, $rowNumber)
    {
        // 1. Extract static fields
        $productName = $row['product'] ?? $row['product_type'] ?? null;
        $status = isset($row['status']) ? (int)$row['status'] : 1;

        $productId = null;
        if ($productName) {
            $product = \App\Models\ProductType::where('name', $productName)
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
            $fieldDef = $this->fieldMap[(string)$header] ?? null;
            
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
                if (\Illuminate\Support\Str::slug($fieldDef->field_name, '_') === 'security_name' || $fieldDef->id == 3) {
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

        // Attach amortisation schedule for this specific row if one was uploaded for it
        if (isset($this->amortisationScheduleMap[$rowNumber])) {
            $payload['amortisation_schedule_data'] = $this->amortisationScheduleMap[$rowNumber];
        }

        // 4. Create request via service (handles validation)
        $this->service->createRequest($payload);
    }
}
