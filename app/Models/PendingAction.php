<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PendingAction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'action_type', // create, update, delete
        'model_type',  // App\Models\Security, etc.
        'model_id',    // ID of the record (null if create)
        'data',        // JSON of the new data
        'status',      // pending, approved, rejected
        'remarks',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    // Helper to get the target model instance (if it exists)
    public function targetModel()
    {
        if ($this->model_id) {
            return $this->model_type::withTrashed()->find($this->model_id);
        }
        return null;
    }
}
