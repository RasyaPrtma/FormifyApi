<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forms extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'limit_one_response',
        'creator_id',
    ];
}
