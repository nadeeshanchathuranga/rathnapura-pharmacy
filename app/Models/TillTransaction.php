<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $shift_id
 * @property int $user_id
 * @property string $type
 * @property float $amount
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $transaction_time
 */
class TillTransaction extends Model
{
    use HasFactory;

    public const TYPE_CASH_IN = 'cash_in';
    public const TYPE_CASH_OUT = 'cash_out';

    protected $fillable = [
        'shift_id',
        'user_id',
        'type',
        'amount',
        'note',
        'transaction_time',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_time' => 'datetime',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
