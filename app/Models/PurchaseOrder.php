<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'order_number',
        'order_date',
        'supplier_id',
        'user_id',
        'subtotal',
        'discount',
        'discount_type',
        'discount_percentage',
        'tax_total',
        'total_amount',
        'status',
        'remarks',
    ];

    protected $casts = [
        'order_date'         => 'date',
        'subtotal'           => 'decimal:2',
        'discount'           => 'decimal:2',
        'discount_percentage'=> 'decimal:2',
        'tax_total'          => 'decimal:2',
        'total_amount'       => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products()
    {
        return $this->hasMany(PurchaseOrderProduct::class, 'purchase_order_id');
    }

    public function goodsReceivedNotes()
    {
        return $this->hasMany(GoodsReceivedNote::class, 'purchase_order_id');
    }
}
