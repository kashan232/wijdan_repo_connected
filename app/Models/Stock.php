<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $guarded = [];

    public function branch()   { return $this->belongsTo(Branch::class); }
    public function warehouse(){ return $this->belongsTo(Warehouse::class); }
    public function product()  { return $this->belongsTo(Product::class); }
    
    protected $casts = [
        'qty' => 'float',
    ];


    
}
