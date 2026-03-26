<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'purchase_order_requests';

    protected $fillable = [
        'order_number',
        'order_date',
        'user_id',
        'supplier_id',
        'status', 
    ];

    

    /**
     * Get the products for the POR
     */
    public function products()
    {
        return $this->hasMany(PurchaseOrderRequestProduct::class, 'purchase_order_request_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    

    public function por_products()
    {
        return $this->hasMany(PurchaseOrderRequestProduct::class, 'purchase_order_request_id');
    }
}
