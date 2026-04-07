<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',     // head ka name, jaise Bank, Expense, etc.
        'status',   // active/inactive
    ];

    // Relation with Accounts
    public function accounts()
    {
        return $this->hasMany(Account::class, 'head_id');
    }
}
