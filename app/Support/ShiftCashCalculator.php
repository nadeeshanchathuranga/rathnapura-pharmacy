<?php

namespace App\Support;

use App\Models\Sale;
use App\Models\Shift;
use App\Models\TillTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ShiftCashCalculator
{
    private static function windowStart(Shift $shift): Carbon
    {
        return $shift->start_time
            ? Carbon::parse($shift->start_time)
            : Carbon::parse($shift->created_at ?? now());
    }

    private static function windowEnd(Shift $shift): Carbon
    {
        $isClosed = (string) ($shift->getAttribute('status') ?? '') === Shift::STATUS_CLOSED;

        if ($isClosed && $shift->end_time) {
            return Carbon::parse($shift->end_time);
        }

        return now();
    }

    public static function salesQueryForShift(Shift $shift): Builder
    {
        $userId = (int) ($shift->getAttribute('user_id') ?? 0);
        $divisionId = $shift->getAttribute('division_id');
        $start = self::windowStart($shift);
        $end = self::windowEnd($shift);

        return Sale::query()
            ->where('user_id', $userId)
            ->when($divisionId, fn (Builder $query) => $query->where('division_id', $divisionId))
            ->whereBetween('created_at', [$start, $end]);
    }

    public static function tillTransactionsQueryForShift(Shift $shift): Builder
    {
        $userId = (int) ($shift->getAttribute('user_id') ?? 0);
        $start = self::windowStart($shift);
        $end = self::windowEnd($shift);

        return TillTransaction::query()
            ->where('user_id', $userId)
            ->whereBetween('transaction_time', [$start, $end]);
    }

    public static function cashSalesTotalForShift(Shift $shift): float
    {
        $cashPrimary = (float) self::salesQueryForShift($shift)
            ->where('payment1_type', 0)
            ->sum('payment1_amount');

        $cashSecond = (float) self::salesQueryForShift($shift)
            ->where('payment2_type', 0)
            ->sum('payment2_amount');

        return round($cashPrimary + $cashSecond, 2);
    }

    public static function tillTotalsForShift(Shift $shift): array
    {
        $cashIn = (float) self::tillTransactionsQueryForShift($shift)
            ->where('type', TillTransaction::TYPE_CASH_IN)
            ->sum('amount');

        $cashOut = (float) self::tillTransactionsQueryForShift($shift)
            ->where('type', TillTransaction::TYPE_CASH_OUT)
            ->sum('amount');

        return [
            'cash_in_total' => round($cashIn, 2),
            'cash_out_total' => round($cashOut, 2),
        ];
    }

    public static function salesCountForShift(Shift $shift): int
    {
        return (int) self::salesQueryForShift($shift)->count();
    }

    public static function tillTransactionsCountForShift(Shift $shift): int
    {
        return (int) self::tillTransactionsQueryForShift($shift)->count();
    }

    public static function expectedClosingAmount(Shift $shift): float
    {
        $totals = self::tillTotalsForShift($shift);
        $cashSales = self::cashSalesTotalForShift($shift);

        return round(
            (float) $shift->opening_till_amount
                + $cashSales
                + $totals['cash_in_total']
                - $totals['cash_out_total'],
            2
        );
    }

    public static function availableCash(Shift $shift, ?string $rule = null): float
    {
        $rule = $rule ?: config('till.available_cash_rule', 'opening_plus_sales_plus_movements');

        $totals = self::tillTotalsForShift($shift);
        $baseAmount = (float) $shift->opening_till_amount;

        if ($rule === 'opening_plus_movements_only') {
            $available = $baseAmount + $totals['cash_in_total'] - $totals['cash_out_total'];
        } else {
            $cashSales = self::cashSalesTotalForShift($shift);
            $available = $baseAmount + $cashSales + $totals['cash_in_total'] - $totals['cash_out_total'];
        }

        return round(max(0, $available), 2);
    }
}
