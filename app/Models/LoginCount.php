<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginCount extends Model
{
    use HasFactory;

    protected $fillable = ['login_count', 'password_age', 'login_history_count'];
}
