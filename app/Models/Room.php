<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'room_number',
        'capacity',
        'type',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];
}
