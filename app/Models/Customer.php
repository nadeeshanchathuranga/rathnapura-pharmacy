<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $address
 * @property float|int|null $credit_limit
 * @property int $status
 */
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'address',
        'credit_limit',
        'status',
    ];

    
}
