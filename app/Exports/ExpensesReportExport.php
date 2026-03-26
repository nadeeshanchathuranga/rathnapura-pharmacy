<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $supplierId;

    public function __construct($startDate, $endDate, $supplierId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->supplierId = $supplierId;
    }

    public function collection()
    {
        return Expense::with(['user:id,name', 'supplier:id,name'])
            ->select('id', 'title', 'amount', 'remark', 'expense_date', 'payment_type', 'card_type', 'user_id', 'supplier_id', 'reference')
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->when($this->supplierId, function($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->orderBy('expense_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'Date' => $item->expense_date,
                    'Title' => $item->title,
                    'Supplier' => $item->supplier->name ?? 'N/A',
                    'Amount' => number_format($item->amount, 2),
                    'Payment Type' => $this->getExpensePaymentTypeName($item->payment_type, $item->card_type),
                    'Reference' => $item->reference ?? '-',
                    'Remark' => $item->remark ?? '-',
                    'User' => $item->user->name ?? 'N/A',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Title',
            'Supplier',
            'Amount',
            'Payment Type',
            'Reference',
            'Remark',
            'User',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getFill()->setFillType('solid');
        $sheet->getStyle('1')->getFill()->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }

    private function getExpensePaymentTypeName($paymentType, $cardType = null): string
    {
        $paymentType = (int) $paymentType;

        if ($paymentType === 1) {
            $resolvedCardType = strtolower((string) $cardType);

            if ($resolvedCardType === 'visa') {
                return 'Card (Visa)';
            }

            if ($resolvedCardType === 'mastercard') {
                return 'Card (MasterCard)';
            }

            return 'Card';
        }

        $paymentTypes = [
            0 => 'Cash',
            2 => 'Cheque',
            3 => 'Bank Transfer',
        ];

        return $paymentTypes[$paymentType] ?? 'Unknown';
    }
}
