<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $sale_id
 * @property int|null $customer_id
 * @property int|null $user_id
 * @property string|null $return_date
 * @property int $return_type
 * @property float|null $refund_amount
 * @property string|null $refund_method
 * @property string|null $notes
 * @property int $status
 * @property-read string $status_text
 * @property-read string $status_color
 * @property-read string $return_type_text
 * @property-read string $return_type_color
 * @property-read float|int $total_refund
 */
class SalesReturn extends Model
{
    use HasFactory;

    protected $table = 'sales_return';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'user_id',
        'return_date',
        'return_type',
        'refund_amount',
        'refund_method',
        'notes',
        'status',
    ];

    protected $casts = [
        'return_date' => 'date',
        'status' => 'integer',
        'return_type' => 'integer',
        'refund_amount' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    // Return Type constants
    const TYPE_PRODUCT_RETURN = 1; // Return to inventory
    const TYPE_CASH_RETURN = 2;    // Cash refund

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray'
        };
    }

    public function getReturnTypeTextAttribute()
    {
        return match($this->return_type) {
            self::TYPE_PRODUCT_RETURN => 'Product Return',
            self::TYPE_CASH_RETURN => 'Cash Refund',
            default => 'Unknown'
        };
    }

    public function getReturnTypeColorAttribute()
    {
        return match($this->return_type) {
            self::TYPE_PRODUCT_RETURN => 'blue',
            self::TYPE_CASH_RETURN => 'green',
            default => 'gray'
        };
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(SalesReturnProduct::class, 'sales_return_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function replacements()
    {
        return $this->hasMany(SalesReturnReplacementProduct::class, 'sales_return_id');
    }

    // Scope for returnable products (where return_product is true)
    public function returnableProducts()
    {
        return $this->products()->whereHas('product', function($query) {
            $query->where('return_product', true);
        });
    }

    // Calculate total refund amount
    public function getTotalRefundAttribute()
    {
        // For cash returns, use the refund_amount field
        if ($this->return_type == self::TYPE_CASH_RETURN) {
            return $this->refund_amount ?? 0;
        }
        
        // For product returns, calculate from products
        return $this->products->sum('total');
    }

    // Relationship: Sales return has one Expense (for cash refunds)
    public function expense()
    {
        return $this->hasOne(Expense::class, 'reference_id')->where('reference_type', 'sales_return');
    }
}