<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductStockExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Product::select('id', 'name','qty', 'retail_price', 'wholesale_price')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            
            'Product Name',
            
            'Current Stock',
            'Retail Price (Rs.)',
            'Wholesale Price (Rs.)',
            'Status',
        ];
    }

    public function map($row): array
    {
        $status = $row->qty == 0 ? 'Out of Stock' : ($row->qty < 10 ? 'Low Stock' : 'In Stock');
        
        return [
            
            $row->name, 
            $row->qty,
            number_format($row->retail_price, 2),
            number_format($row->wholesale_price, 2),
            $status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
