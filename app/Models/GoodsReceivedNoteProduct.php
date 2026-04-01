<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoodsReceivedNoteProduct extends Model
{
    use HasFactory;

    protected $table = 'goods_received_notes_products';

    protected $fillable = [
        'goods_received_note_id',
        'product_id',
        'batch_number',
        'quantity',
        'purchase_price', 
        'discount',
        'discount_percentage',
        'expire_date',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'expire_date' => 'date',
        'total' => 'decimal:2',
    ];

    protected $appends = ['parent_batch_number'];

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'goods_received_note_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the batch number from the parent GoodsReceivedNote if not set on product
     * Returns the product's batch_number first, then falls back to parent GRN batch_number
     */
    public function getParentBatchNumberAttribute()
    {
        // If this product has its own batch_number, return it
        if ($this->attributes['batch_number'] ?? null) {
            return $this->attributes['batch_number'];
        }
        
        // Otherwise, try to get it from the parent GRN
        if ($this->grn && $this->grn->batch_number) {
            return $this->grn->batch_number;
        }
        
        return null;
    }
}
