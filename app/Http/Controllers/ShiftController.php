<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use App\Support\ShiftCashCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();
        $userRole = (int) ($user->getAttribute('role') ?? -1);

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');
        $onlyMyShifts = $request->boolean('only_my_shifts');

        $query = Shift::with('user:id,name')
            ->latest('id');

        if (in_array($status, [Shift::STATUS_OPEN, Shift::STATUS_CLOSED], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($onlyMyShifts || $userRole === 2) {
            $query->where('user_id', $userId);
        }

        $shifts = $query->paginate(15)->withQueryString();

        $shifts->getCollection()->transform(function (Shift $shift) {
            $shiftId = (int) $shift->getAttribute('id');
            $shiftUserId = (int) ($shift->getAttribute('user_id') ?? 0);
            $tillTotals = ShiftCashCalculator::tillTotalsForShift($shift);
            $cashSales = ShiftCashCalculator::cashSalesTotalForShift($shift);
            $expected = ShiftCashCalculator::expectedClosingAmount($shift);
            return [
                'id' => $shiftId,
                'user_name' => $shift->user?->name ?? '-',
                'user_id' => $shiftUserId,
                'start_time' => optional($shift->start_time)?->format('d M Y, H:i'),
                'end_time' => optional($shift->end_time)?->format('d M Y, H:i'),
                'status' => (string) $shift->getAttribute('status'),
                'opening_till_amount' => (float) $shift->opening_till_amount,
                'start_note' => $shift->getAttribute('start_note'),
                'closing_cash_amount' => (float) ($shift->closing_cash_amount ?? 0),
                'end_note' => $shift->getAttribute('end_note'),
                'expected_closing_amount' => $expected,
                'total_sales' => $cashSales,
                'variance_amount' => (float) ($shift->variance_amount ?? 0),
                'cash_in_total' => $tillTotals['cash_in_total'],
                'cash_out_total' => $tillTotals['cash_out_total'],
                'transactions_count' => ShiftCashCalculator::tillTransactionsCountForShift($shift),
                'sales_count' => ShiftCashCalculator::salesCountForShift($shift),
            ];
        });

        $activeShift = Shift::with('user:id,name')
            ->open()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        $activeShiftData = null;
        if ($activeShift) {
            $totals = ShiftCashCalculator::tillTotalsForShift($activeShift);
            $cashSales = ShiftCashCalculator::cashSalesTotalForShift($activeShift);
            $expected = ShiftCashCalculator::expectedClosingAmount($activeShift);

            $activeShiftData = [
                'id' => $activeShift->id,
                'start_time' => optional($activeShift->start_time)?->format('Y-m-d H:i:s'),
                'opening_till_amount' => (float) $activeShift->opening_till_amount,
                'cash_sales_total' => $cashSales,
                'cash_in_total' => $totals['cash_in_total'],
                'cash_out_total' => $totals['cash_out_total'],
                'expected_closing_amount' => $expected,
                'available_cash' => ShiftCashCalculator::availableCash($activeShift),
            ];
        }

        return Inertia::render('Shifts/Index', [
            'activeShift' => $activeShiftData,
            'shifts' => $shifts,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'only_my_shifts' => $onlyMyShifts,
            ],
        ]);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'opening_till_amount' => 'required|numeric|min:0',
            'start_note' => 'nullable|string|max:500',
            'redirect_to' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();
        $userDivisionId = $user->getAttribute('division_id');

        $existing = Shift::open()
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return back()->with('error', 'You already have an active shift. Please end it first.');
        }

        $shift = Shift::create([
            'user_id' => $userId,
            'division_id' => $userDivisionId,
            'start_time' => now(),
            'status' => Shift::STATUS_OPEN,
            'opening_till_amount' => $validated['opening_till_amount'],
            'start_note' => $validated['start_note'] ?? null,
        ]);

        $allowedRedirectRoutes = [
            'shift-management.index',
            'sales.index',
        ];

        $redirectRoute = in_array($validated['redirect_to'] ?? null, $allowedRedirectRoutes, true)
            ? $validated['redirect_to']
            : 'shift-management.index';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Shift started successfully.')
            ->with('shift_event', [
                'name' => 'shift:started',
                'shift_id' => $shift->id,
            ]);
    }

    public function endPage()
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();

        $shift = Shift::open()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        if (!$shift) {
            return redirect()->route('shift-management.index')->with('error', 'No active shift found to close.');
        }

        $totals = ShiftCashCalculator::tillTotalsForShift($shift);
        $cashSales = ShiftCashCalculator::cashSalesTotalForShift($shift);
        $expected = ShiftCashCalculator::expectedClosingAmount($shift);

        return Inertia::render('Shifts/End', [
            'shift' => [
                'id' => $shift->id,
                'start_time' => optional($shift->start_time)?->format('Y-m-d H:i:s'),
                'opening_till_amount' => (float) $shift->opening_till_amount,
                'sales_total' => $cashSales,
                'cash_in_total' => $totals['cash_in_total'],
                'cash_out_total' => $totals['cash_out_total'],
                'expected_closing_amount' => $expected,
            ],
        ]);
    }

    public function end(Request $request)
    {
        $validated = $request->validate([
            'closing_cash_amount' => 'required|numeric|min:0',
            'end_note' => 'nullable|string|max:500',
            'redirect_to' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();

        $shift = Shift::open()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        if (!$shift) {
            return back()->with('error', 'No active shift found to close.');
        }

        $expected = ShiftCashCalculator::expectedClosingAmount($shift);
        $cashSales = ShiftCashCalculator::cashSalesTotalForShift($shift);
        $closing = (float) $validated['closing_cash_amount'];
        $variance = round($closing - $expected, 2);

        $shift->update([
            'end_time' => now(),
            'status' => Shift::STATUS_CLOSED,
            'closing_cash_amount' => $closing,
            'expected_closing_amount' => $expected,
            'total_sales' => $cashSales,
            'end_note' => $validated['end_note'] ?? null,
            'variance_amount' => $variance,
        ]);

        $allowedRedirectRoutes = [
            'shift-management.index',
            'sales.index',
        ];

        $redirectRoute = in_array($validated['redirect_to'] ?? null, $allowedRedirectRoutes, true)
            ? $validated['redirect_to']
            : 'shift-management.index';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Shift ended successfully.')
            ->with('shift_event', [
                'name' => 'shift:ended',
                'shift_id' => $shift->id,
            ]);
    }

    public function show(Shift $shift)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();
        $userRole = (int) ($user->getAttribute('role') ?? -1);
        $shiftUserId = (int) ($shift->getAttribute('user_id') ?? 0);

        if ($userRole === 2 && $shiftUserId !== $userId) {
            abort(403, 'Unauthorized shift action.');
        }

        $shift->load('user:id,name');

        $totals = ShiftCashCalculator::tillTotalsForShift($shift);
        $cashSales = ShiftCashCalculator::cashSalesTotalForShift($shift);
        $expected = ShiftCashCalculator::expectedClosingAmount($shift);

        $sales = ShiftCashCalculator::salesQueryForShift($shift)
            ->select('id', 'invoice_no', 'sale_date', 'net_amount', 'paid_amount', 'balance')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($sale) => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'sale_date' => optional($sale->sale_date)?->format('Y-m-d') ?? (string) $sale->sale_date,
                'net_amount' => (float) $sale->net_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance' => (float) $sale->balance,
            ]);

        $transactions = ShiftCashCalculator::tillTransactionsQueryForShift($shift)
            ->with('user:id,name')
            ->orderByDesc('transaction_time')
            ->limit(100)
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => (float) $transaction->amount,
                'note' => $transaction->note,
                'transaction_time' => optional($transaction->transaction_time)?->format('Y-m-d H:i:s'),
                'user_name' => $transaction->user?->name,
            ]);

        return response()->json([
            'id' => $shift->id,
            'user_name' => $shift->user?->name ?? '-',
            'status' => $shift->status,
            'start_time' => optional($shift->start_time)?->format('d M Y, H:i'),
            'end_time' => optional($shift->end_time)?->format('d M Y, H:i'),
            'opening_till_amount' => (float) $shift->opening_till_amount,
            'start_note' => $shift->start_note,
            'closing_cash_amount' => (float) ($shift->closing_cash_amount ?? 0),
            'end_note' => $shift->end_note,
            'expected_closing_amount' => (float) ($shift->expected_closing_amount ?? $expected),
            'total_sales' => (float) ($shift->total_sales ?? $cashSales),
            'variance_amount' => (float) ($shift->variance_amount ?? 0),
            'cash_sales_total' => $cashSales,
            'cash_in_total' => $totals['cash_in_total'],
            'cash_out_total' => $totals['cash_out_total'],
            'transaction_count' => $transactions->count(),
            'sales_count' => $sales->count(),
            'sales' => $sales,
            'transactions' => $transactions,
        ]);
    }

    public function destroy(Shift $shift)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();
        $userRole = (int) ($user->getAttribute('role') ?? -1);
        $shiftUserId = (int) ($shift->getAttribute('user_id') ?? 0);

        if ($userRole === 2 && $shiftUserId !== $userId) {
            abort(403, 'Unauthorized shift action.');
        }

        if ((string) $shift->getAttribute('status') === Shift::STATUS_OPEN) {
            return back()->with('error', 'Open shift cannot be deleted. End it first.');
        }

        $shift->delete();

        return back()->with('success', 'Shift deleted successfully.');
    }
}
