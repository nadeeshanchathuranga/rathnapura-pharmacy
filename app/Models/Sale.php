<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $invoice_no
 * @property int|null $customer_id
 * @property float $total_amount
 * @property float $discount
 * @property float $net_amount
 * @property float $paid_amount
 * @property float $balance
 * @property string|null $sale_date
 */
class Sale extends Model
{
   use HasFactory;

    protected $fillable = [
        'invoice_no',
        'type',
        'customer_id',
        'user_id',
        'total_amount',
        'discount',
        'net_amount',
        'paid_amount',
        'return_amount',
        'balance',
        'has_return',
        'sale_date',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'return_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'has_return' => 'boolean',
    ];

    // Relationships
    public function products()
    {
        return $this->hasMany(SalesProduct::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship: Sale has one Income
    public function income()
    {
        return $this->hasOne(Income::class);
    }

    // Relationship: Sale has many payments (Income records)
    public function payments()
    {
        return $this->hasMany(Income::class, 'sale_id');
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class, 'sale_id');
    }

    /**
     * Calculate the effective net amount after applying approved returns
     * This considers partial returns with discounts properly
     * 
     * @return float
     */
    public function getNetAmountAfterReturnsAttribute()
    {
        $totalReturned = $this->returns()
            ->where('status', SalesReturn::STATUS_APPROVED)
            ->get()
            ->map(function ($return) {
                if ($return->return_type == SalesReturn::TYPE_CASH_RETURN) {
                    return (float) ($return->refund_amount ?? 0);
                }
                return $return->products
                    ->map(fn($p) => (float) ($p->total ?? 0))
                    ->sum();
            })
            ->sum();

        return max(0, $this->net_amount - $totalReturned);
    }

    /**
     * Calculate the effective discount after returns
     * When items are returned, the proportional discount is also reduced
     * 
     * @return float
     */
    public function getEffectiveDiscountAttribute()
    {
        // Sum the discount amounts from all remaining (non-returned) items
        $remainingDiscount = $this->products()
            ->where('is_return', false)
            ->where('quantity', '>', 0)
            ->sum('discount_amount');

        return round($remainingDiscount, 2);
    }

    /**
     * Calculate the effective total amount (before discount) after returns
     * 
     * @return float
     */
    public function getEffectiveTotalAmountAttribute()
    {
        $remainingTotal = $this->products()
            ->where('is_return', false)
            ->where('quantity', '>', 0)
            ->sum('total');

        return round($remainingTotal, 2);
    }

    /**
     * Get a summary of return impact on this sale
     * 
     * @return array
     */
    public function getReturnSummary()
    {
        $returns = $this->returns()->where('status', SalesReturn::STATUS_APPROVED)->get();
        
        $totalReturned = $returns->sum(function ($return) {
            if ($return->return_type == SalesReturn::TYPE_CASH_RETURN) {
                return (float) ($return->refund_amount ?? 0);
            }
            return $return->products
                ->map(fn($p) => (float) ($p->total ?? 0))
                ->sum();
        });

        return [
            'original_total' => (float) $this->total_amount,
            'original_discount' => (float) $this->discount,
            'original_net' => (float) $this->net_amount,
            'returned_amount' => round($totalReturned, 2),
            'current_net' => $this->getNetAmountAfterReturnsAttribute(),
            'returns_count' => $returns->count(),
        ];
    }
}
