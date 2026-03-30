<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $division_id
 * @property \Illuminate\Support\Carbon|null $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property string $status
 * @property float $opening_till_amount
 * @property string|null $start_note
 * @property float|null $closing_cash_amount
 * @property string|null $end_note
 * @property float|null $expected_closing_amount
 * @property float|null $total_sales
 * @property float|null $variance_amount
 * @property int|null $transactions_count
 * @property int|null $sales_count
 */
class Shift extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'division_id',
        'start_time',
        'end_time',
        'status',
        'opening_till_amount',
        'start_note',
        'closing_cash_amount',
        'end_note',
        'expected_closing_amount',
        'total_sales',
        'variance_amount',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'opening_till_amount' => 'decimal:2',
        'closing_cash_amount' => 'decimal:2',
        'expected_closing_amount' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'variance_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\TillTransaction::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
