<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTransferRequest extends Model
{
    protected $table = 'product_transfer_requests';

    protected $fillable = [
        'product_transfer_request_no',
        'request_date',
        'remarks',
        'status',
        'user_id',
    ];

    // Relationships
    public function product()
    {
        return $this->hasMany(ProductTransferRequestProduct::class, 'product_transfer_request_id');
    }

    public function product_transfer_request_products()
    {
        return $this->hasMany(ProductTransferRequestProduct::class, 'product_transfer_request_id');
    }

    public function ptr_products()
    {
        return $this->hasMany(ProductTransferRequestProduct::class, 'product_transfer_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prns()
    {
        return $this->hasMany(ProductReleaseNote::class, 'product_transfer_request_id');
    }
}
