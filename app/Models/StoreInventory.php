<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'store_inventory';

    protected $fillable = [
        'product_id',
        'user_id',
        'reference_no',
        'transaction_type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'measurement_unit',
        'remarks',
        'transaction_date',
        'status',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:2',
        'quantity_change' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate unique reference number
    public static function generateReferenceNo()
    {
        $lastRecord = self::latest('id')->first();
        $nextId = $lastRecord ? $lastRecord->id + 1 : 1;
        return 'SI-' . date('Ymd') . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
