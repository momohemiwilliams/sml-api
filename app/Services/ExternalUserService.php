<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class ExternalUserService
{
    protected string $baseUrl;

    public function __construct()
    {
        // Using the URL provided by the user. 
        // In a real scenario, this should be in config/services.php
        $this->baseUrl = 'https://adgdev.fmdqgroup.com/authService/api/apps/3/users/stateless';
    }

    /**
     * Fetch all users from the external service.
     * Caches the result for performance.1
     * 
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return Cache::remember('external_users', 300, function () {
            $users = collect();
            $page = 1;
            $lastPage = 1;

            do {
                try {
                    $response = Http::timeout(5)
                        ->withBasicAuth('fmdq_admin', 'golddoor2025_secure!')
                        ->get($this->baseUrl, [
                            'page' => $page,
                            'per_page' => 100 
                        ]);

                    if ($response->successful()) {
                        $data = $response->json('data');
                        
                        if (empty($data['data'])) {
                            break;
                        }

                        $users = $users->concat($data['data']);
                        $lastPage = $data['last_page'] ?? 1;
                        $page++;
                    } else {
                        // Log error or handle failure
                        break;
                    }
                } catch (\Exception $e) {
                    // Handle connection errors
                    break;
                }
            } while ($page <= $lastPage);

            \Log::info('External users fetched', [
                'total_users' => $users->count(),
                'sample_ids' => $users->keys()->take(10)->values()->toArray()
            ]);

            // Key by ID for easy lookup
            return $users->keyBy('id');
        });
    }

    /**
     * Get a specific user by ID from the cached list.
     * 
     * @param int $id
     * @return array|null
     */
    public function getUserById(int $id): ?array
    {
        $users = $this->getAllUsers();
        $user = $users->get($id);
        
        if ($user && !isset($user['name'])) {
            $user['name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }
        
        return $user;
    }

    /**
     * Map user details to a list of items.
     * 
     * @param mixed $items Collection or Paginator
     * @param array $mappings Array of local_field => target_field mappings (e.g. ['created_by' => 'creator'])
     * @return mixed
     */
    public function enrichWithUsers($items, array $mappings)
    {
        $users = $this->getAllUsers();

        // Handle both simple Collections and Paginators
        $collection = $items instanceof \Illuminate\Pagination\LengthAwarePaginator 
            ? $items->getCollection() 
            : $items;

        $collection->transform(function ($item) use ($users, $mappings) {
            foreach ($mappings as $localField => $targetField) {
                if (isset($item->$localField)) {
                    $userId = $item->$localField;
                    $user = $users->get($userId);
                    
                    \Log::info('Enriching user field', [
                        'local_field' => $localField,
                        'target_field' => $targetField,
                        'user_id' => $userId,
                        'user_found' => $user !== null,
                        'total_users_available' => $users->count()
                    ]);
                    
                    // Return only specific fields instead of the entire user object
                    if ($user) {
                        $item->$targetField = [
                            'id' => $user['id'] ?? null,
                            'firstname' => $user['firstname'] ?? null,
                            'lastname' => $user['lastname'] ?? null,
                            'email' => $user['email'] ?? null,
                            'name' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
                        ];
                    } else {
                        $item->$targetField = null;
                    }
                }
            }
            return $item;
        });

        if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $items->setCollection($collection);
        }

        return $items;
    }
}
