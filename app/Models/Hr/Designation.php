<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $table = 'hr_designations';

    protected $fillable = ['name', 'description', 'requires_location', 'is_sale_officer'];

    protected $casts = [
        'requires_location' => 'boolean',
        'is_sale_officer' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}

