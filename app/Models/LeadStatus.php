<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    protected $fillable = [
        'name',
        'color',
        'sort_order',
        'is_active',
        'is_closed',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }
}