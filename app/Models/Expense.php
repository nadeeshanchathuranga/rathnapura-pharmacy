<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'purchase_expenses';

    protected $fillable = [
        'title',
        'amount',
        'remark',
        'expense_date',
        'payment_type',
        'card_type',
        'user_id',
        'supplier_id',
        'reference',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'payment_type' => 'integer',
        'card_type' => 'string',
    ];

    // Relationship: Expense belongs to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: Expense belongs to Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getPaymentTypeNameAttribute()
    {
        if ((int) $this->payment_type === 1) {
            $resolvedCardType = strtolower((string) ($this->card_type ?? ''));

            if ($resolvedCardType === 'visa') {
                return 'Card (Visa)';
            }

            if ($resolvedCardType === 'mastercard') {
                return 'Card (MasterCard)';
            }

            return 'Card';
        }

        $types = [
            0 => 'Cash',
            2 => 'Cheque',
           
        ];
        return $types[$this->payment_type] ?? 'Unknown';
    }
}
