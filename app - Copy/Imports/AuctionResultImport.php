<?php

namespace App\Imports;

use App\Services\AuctionResultService;
use App\Models\SecurityMasterData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Log;

class AuctionResultImport implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithHeadingRow, \Maatwebsite\Excel\Concerns\SkipsEmptyRows, \Maatwebsite\Excel\Concerns\WithValidation, \Maatwebsite\Excel\Concerns\SkipsOnFailure, \Maatwebsite\Excel\Concerns\WithMapping
{
    use SkipsFailures;
    protected $service;
    protected $createdBy;
    protected $authoriser_id;
    protected $errors = [];
    protected $successCount = 0;
    protected $errorCount = 0;

    public function __construct(
        AuctionResultService $service,
        int $createdBy,
        int $authoriserId
    ) {
        $this->service = $service;
        $this->createdBy = $createdBy;
        $this->authoriserId = $authoriserId;
    }

    public function map($row): array
    {
        if (isset($row['auction_date']) && is_numeric($row['auction_date'])) {
             $row['auction_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['auction_date'])->format('Y-m-d');
        }
        if (isset($row['value_date']) && is_numeric($row['value_date'])) {
             $row['value_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['value_date'])->format('Y-m-d');
        }
        return $row;
    }

    public function rules(): array
    {
        return [
            'security_id' => 'nullable|exists:security_master_data,id',
            'isin' => 'nullable|string',
            'security_name' => 'nullable|string',
            'auction_number' => 'nullable|string',
            'auction_date' => 'required|date',
            'value_date' => 'required|date',
            'tenor' => 'nullable|integer|min:0',
            'tenor_days' => 'nullable|integer|min:0',
            'amount_offered' => 'required|numeric|min:0',
            'amount_subscribed' => 'required|numeric|min:0',
            'amount_allotted' => 'nullable|numeric|min:0',
            'amount_sold' => 'required|numeric|min:0',
            'non_competitive_sales' => 'nullable|numeric|min:0',
            'stop_rate' => 'required|numeric|min:0',
            'marginal_rate' => 'nullable|numeric|min:0',
            'true_yield' => 'nullable|numeric|min:0',
            'bid_cover_ratio' => 'nullable|numeric|min:0',
            'auction_type' => 'nullable',
            'status' => 'nullable',
        ];
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 1; 

        // Pre-fetch securities to minimize queries? 
        // Or just query individually (simpler for now if list is not huge).
        // Let's optimize slightly by caching names.
        // Assuming security names are unique or enough to identify.

        foreach ($rows as $row) {
            $rowNumber++;
            
            // Basic check if row is actually empty despite SkipsEmptyRows
            if (empty(array_filter($row->toArray()))) {
                continue;
            }

            try {
                $this->processRow($row, $rowNumber);
                $this->successCount++;
            } catch (\Exception $e) {
                $this->errorCount++;
                $errorMessage = "Row {$rowNumber}: " . $e->getMessage();
                $this->errors[] = $errorMessage;
                Log::error("Auction Result Import Error " . $errorMessage);
            }
        }

        Log::info("Auction Import completed: {$this->successCount} successful, {$this->errorCount} errors.");
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getCounts(): array
    {
        return [
            'success' => $this->successCount,
            'errors' => $this->errorCount,
        ];
    }

    protected function processRow($row, $rowNumber)
    {
        // 1. Identify Security
        $security = null;
        if (!empty($row['security_id'])) {
             $security = \App\Models\SecurityMasterData::find($row['security_id']);
        } elseif (!empty($row['isin'])) {
             // Assuming security_master_data has isin or equivalent field, or we stick to checking name.
             // SecurityMasterData has 'security_name'. ISIN might not be there directly based on migration? 
             // Migration 2026_02_03_113336 shows only security_name. 
             // Let's assume security_name matches.
             $security = \App\Models\SecurityMasterData::where('security_name', $row['isin'])->first();
        } elseif (!empty($row['security_name'])) {
            $security = \App\Models\SecurityMasterData::where('security_name', $row['security_name'])->first();
        }

        if (!$security) {
            $identifier = $row['security_id'] ?? $row['security_name'] ?? $row['isin'] ?? 'Unknown';
            throw new \Exception("Security not found for identifier: {$identifier}. Please ensure the security exists in the Security Master.");
        }

        // 2. Validate uniqueness of auction_number for this security if needed
        // Though auction_number is usually unique globally based on request
        if (!empty($row['auction_number'])) {
            $exists = \App\Models\AuctionResult::where('auction_number', $row['auction_number'])->exists();
            if ($exists) {
                 throw new \Exception("Auction number '{$row['auction_number']}' already exists in the system.");
            }
            
            $pendingExists = \App\Models\PendingAuctionResult::where('auction_number', $row['auction_number'])
                ->where('approval_status', 'pending')
                ->exists();
            if ($pendingExists) {
                throw new \Exception("A pending request for auction number '{$row['auction_number']}' is already awaiting approval.");
            }
        }

        // 3. Prepare Data
        $data = [
            'security_id' => $security->id,
            'created_by' => $this->createdBy,
            'authoriser_id' => $this->authoriserId,
            
            'auction_number' => $row['auction_number'] ?? null,
            'auction_date' => $this->parseDate($row['auction_date'] ?? null),
            'value_date' => $this->parseDate($row['value_date'] ?? null),
            'tenor_days' => $row['tenor'] ?? $row['tenor_days'] ?? 0,
            
            'amount_offered' => $row['amount_offered'] ?? 0,
            'amount_subscribed' => $row['amount_subscribed'] ?? 0,
            'amount_allotted' => $row['amount_allotted'] ?? 0,
            'amount_sold' => $row['amount_sold'] ?? 0,
            'non_competitive_sales' => $row['non_competitive_sales'] ?? 0,
            
            'stop_rate' => $row['stop_rate'] ?? 0,
            'marginal_rate' => $row['marginal_rate'] ?? null,
            'true_yield' => $row['true_yield'] ?? null,
            'bid_cover_ratio' => $row['bid_cover_ratio'] ?? null,
            
            'auction_type' => $row['auction_type'] ?? null,
            'remarks' => $row['remarks'] ?? null,
            'status' => $row['status'] ?? null,
        ];


        // 3. Create Request
        $this->service->createRequest($data);
    }

    private function parseDate($date)
    {
        if (empty($date)) return now(); // Or null? Required field.
        try {
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return now();
        }
    }
}
