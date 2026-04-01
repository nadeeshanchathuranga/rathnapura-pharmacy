<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoodsReceivedNote extends Model
{
    use HasFactory;

    protected $table = 'goods_received_notes';

    protected $fillable = [
        'purchase_order_request_id',
        'purchase_order_id',
        'goods_received_note_no',
        'batch_number',
        'supplier_id',
        'user_id',
        'goods_received_note_date',
        'subtotal',
        'discount',
        'discount_type',
        'discount_percentage',
        'tax_total',
        'remarks',
        'status',
        'approval_status',
    ];

    public function goods_received_note_products()
    {
        return $this->hasMany(GoodsReceivedNoteProduct::class, 'goods_received_note_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
