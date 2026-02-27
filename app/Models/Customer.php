<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullName',
        'email',
        'phone',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customerId');
    }
}
