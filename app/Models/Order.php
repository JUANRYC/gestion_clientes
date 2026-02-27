<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customerId',
        'orderNumber',
        'status',
        'totalAmount',
        'notes',
    ];

    protected $casts = [
        'totalAmount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }
}
