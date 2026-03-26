<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return Sale::select(
                'type',
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(total_amount) as gross_total'),
                DB::raw('SUM(discount) as total_discount'),
                DB::raw('SUM(net_amount) as net_total'),
                DB::raw('SUM(balance) as total_balance')
            )
            ->whereBetween('sale_date', [$this->startDate, $this->endDate])
            ->groupBy('type')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Type',
            'Sales Count',
            'Gross Total (Rs.)',
            'Discount (Rs.)',
            'Net Total (Rs.)',
            'Balance (Rs.)',
        ];
    }

    public function map($row): array
    {
        $types = [1 => 'Retail', 2 => 'Wholesale'];
        
        return [
            $types[$row->type] ?? 'Unknown',
            $row->total_sales,
            number_format($row->gross_total, 2),
            number_format($row->total_discount, 2),
            number_format($row->net_total, 2),
            number_format($row->total_balance, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
