<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'head_id',       // foreign key: account head
        'account_code',  // account code
        'title',         // account title
        'type',          // Debit / Credit
        'total_debit',
        'total_credit',
        'status',        // active/inactive
        'opening_balance',        // active/inactive

        
    ];

    // Relation with AccountHead
    public function head()
    {
        return $this->belongsTo(AccountHead::class, 'head_id');
    }
    
}
