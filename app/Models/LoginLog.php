<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'status',
        'ip_address',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
