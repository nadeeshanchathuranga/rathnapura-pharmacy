<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\TillTransaction;
use App\Models\User;
use App\Support\ShiftCashCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TillManagementController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();

        $activeShift = Shift::open()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        $transactions = null;
        $totals = [
            'opening_cash' => 0,
            'cash_in_total' => 0,
            'cash_out_total' => 0,
            'cash_sales_total' => 0,
            'balance' => 0,
            'available_cash' => 0,
            'available_cash_rule' => config('till.available_cash_rule', 'opening_plus_sales_plus_movements'),
        ];

        if ($activeShift) {
            $transactions = ShiftCashCalculator::tillTransactionsQueryForShift($activeShift)
                ->with('user:id,name')
                ->orderByDesc('transaction_time')
                ->paginate(15)
                ->through(function (TillTransaction $transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => (float) $transaction->amount,
                        'note' => $transaction->note,
                        'transaction_time' => optional($transaction->transaction_time)?->format('Y-m-d H:i:s'),
                        'user_name' => $transaction->user?->name,
                    ];
                });

            $tillTotals = ShiftCashCalculator::tillTotalsForShift($activeShift);
            $cashSales = ShiftCashCalculator::cashSalesTotalForShift($activeShift);
            $balance = round(
                (float) $activeShift->opening_till_amount + $tillTotals['cash_in_total'] - $tillTotals['cash_out_total'],
                2
            );

            $totals = [
                'opening_cash' => (float) $activeShift->opening_till_amount,
                'cash_in_total' => $tillTotals['cash_in_total'],
                'cash_out_total' => $tillTotals['cash_out_total'],
                'cash_sales_total' => $cashSales,
                'balance' => $balance,
                'available_cash' => ShiftCashCalculator::availableCash($activeShift),
                'available_cash_rule' => config('till.available_cash_rule', 'opening_plus_sales_plus_movements'),
            ];
        }

        return Inertia::render('Till/Index', [
            'activeShift' => $activeShift ? [
                'id' => $activeShift->id,
                'start_time' => optional($activeShift->start_time)?->format('Y-m-d H:i:s'),
                'opening_till_amount' => (float) $activeShift->opening_till_amount,
            ] : null,
            'totals' => $totals,
            'transactions' => $transactions ?: [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0,
                'links' => [],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();

        $activeShift = Shift::open()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        if (!$activeShift) {
            return back()->with('error', 'No active shift found. Start a shift before managing till transactions.');
        }

        $amount = (float) $validated['amount'];

        if ($validated['type'] === TillTransaction::TYPE_CASH_OUT) {
            $availableCash = ShiftCashCalculator::availableCash($activeShift);

            if ($amount > $availableCash) {
                return back()->withErrors([
                    'amount' => 'Cash out exceeds available cash for the active rule. Available cash: ' . number_format($availableCash, 2),
                ]);
            }
        }

        TillTransaction::create([
            'shift_id' => $activeShift->id,
            'user_id' => $userId,
            'type' => $validated['type'],
            'amount' => $amount,
            'note' => $validated['note'] ?? null,
            'transaction_time' => now(),
        ]);

        return redirect()->route('till-management.index')->with('success', 'Till transaction recorded successfully.');
    }
}
