<?php

namespace App\Imports;

use App\Services\SecurityTypeService;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class SecurityTypesImport implements ToCollection, WithHeadingRow
{
    protected $securityTypeService;
    protected $requestedBy;
    protected $authoriserId;

    public function __construct(SecurityTypeService $securityTypeService, int $requestedBy, int $authoriserId)
    {
        $this->securityTypeService = $securityTypeService;
        $this->requestedBy = $requestedBy;
        $this->authoriserId = $authoriserId;
    }

    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Basic validation for existing creation requests
            if (!isset($row['name']) || !isset($row['code'])) {
                continue; // Skip invalid rows
            }

            $data = [
                'name' => $row['name'],
                'code' => $row['code'],
                'description' => $row['description'] ?? null,
                'is_active' => isset($row['is_active']) ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
                'requested_by' => $this->requestedBy,
                'authoriser_id' => $this->authoriserId,
            ];

            // Create a pending request for each row
            $this->securityTypeService->createRequest($data);
        }
    }
}
