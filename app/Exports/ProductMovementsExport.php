<?php

namespace App\Exports;

use App\Models\ProductMovement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ProductMovementsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        return ProductMovement::with('product:id,name,barcode')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Product Name',
            'Barcode',
            'Movement Type',
            'Quantity',
            'Reference',
        ];
    }

    public function map($movement): array
    {
        $movementTypes = [
            0 => 'Purchase',
            1 => 'Purchase Return',
            2 => 'Transfer',
            3 => 'Sale',
            4 => 'Sale Return',
        ];

        $movementType = isset($movementTypes[$movement->movement_type]) 
            ? $movementTypes[$movement->movement_type] 
            : 'Unknown';

        return [
            Carbon::parse($movement->created_at)->format('M d, Y H:i'),
            $movement->product->name ?? 'N/A',
            $movement->product->barcode ?? 'N/A',
            $movementType,
            number_format($movement->quantity, 2),
            $movement->reference,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
