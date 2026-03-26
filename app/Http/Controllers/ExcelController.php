<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GenericImport;
use App\Models\CompanyInformation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelController extends Controller
{
    public function upload(Request $request, $module)
    {
        try {
            \Log::info('Upload started for module: ' . $module);
            
            // Validate file exists and is proper format
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls'
            ]);

            \Log::info('File validation passed');

            // Sanitize module name to prevent SQL injection
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $module)) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ Invalid module name."
                ], 422);
            }

            // Check if table exists first
            try {
                $result = DB::select("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", 
                    [env('DB_DATABASE'), $module]);
                
                if (empty($result)) {
                    return response()->json([
                        'success' => false,
                        'message' => "❌ Module table '{$module}' not found in database."
                    ], 422);
                }
            } catch (\Exception $e) {
                \Log::error('Table check failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => "❌ Could not verify module table. Error: " . $e->getMessage()
                ], 422);
            }

            \Log::info('Table exists: ' . $module);

            // Validate Excel headers match table structure
            try {
                $excelHeaders = Excel::toArray(new GenericImport($module), $request->file('file'));
            } catch (\Exception $e) {
                \Log::error('Excel read failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => "❌ Failed to read Excel file: " . $e->getMessage()
                ], 422);
            }

            if (empty($excelHeaders) || empty($excelHeaders[0])) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ The uploaded file is empty or has no headers."
                ], 422);
            }

            // Get headers from the first row of the Excel file
            $fileHeaders = array_map('trim', array_map('strtolower', $excelHeaders[0][0] ?? []));

            \Log::info('File headers: ' . json_encode($fileHeaders));

            // Get actual table columns from database
            try {
                $tableColumns = DB::select("DESCRIBE {$module}");
            } catch (\Exception $e) {
                \Log::error('Describe table failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => "❌ Could not read table structure: " . $e->getMessage()
                ], 422);
            }

            $expectedHeaders = array_map(function($col) {
                return strtolower($col->Field);
            }, $tableColumns);

            \Log::info('Expected headers: ' . json_encode($expectedHeaders));

            // Compare headers (file headers should match table columns)
            $missingHeaders = array_diff($expectedHeaders, $fileHeaders);
            $extraHeaders = array_diff($fileHeaders, $expectedHeaders);

            if (!empty($missingHeaders)) {
                \Log::warning('Missing headers: ' . json_encode($missingHeaders));
                return response()->json([
                    'success' => false,
                    'message' => "❌ Missing columns: " . implode(', ', $missingHeaders)
                ], 422);
            }

            if (!empty($extraHeaders)) {
                \Log::warning('Extra headers: ' . json_encode($extraHeaders));
                return response()->json([
                    'success' => false,
                    'message' => "❌ Extra columns not in table: " . implode(', ', $extraHeaders)
                ], 422);
            }

            \Log::info('Headers match - starting import');

            // Create import instance to track insert/update counts
            $import = new GenericImport($module);
            Excel::import($import, $request->file('file'));

            // Build response message with counts
            $insertedCount = $import->getInsertedCount();
            $updatedCount = $import->getUpdatedCount();
            $totalCount = $insertedCount + $updatedCount;

            \Log::info('Import complete - inserted: ' . $insertedCount . ', updated: ' . $updatedCount);

            if ($totalCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "⚠️ No valid data found in the file. Please ensure the file has data rows (not just headers).",
                    'inserted' => 0,
                    'updated' => 0,
                    'total' => 0
                ], 422);
            }

            $message = "✅ " . ucfirst($module) . " data imported successfully!";

            return response()->json([
                'success' => true,
                'message' => $message,
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'total' => $totalCount
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Handle Excel validation errors
            $failures = $e->failures();
            $errorMessage = "❌ Excel validation error on rows: ";

            $rowErrors = [];
            foreach ($failures as $failure) {
                $rowErrors[] = "Row {$failure->row()}: {$failure->errors()[0]}";
            }

            $errorMessage .= implode(" | ", array_slice($rowErrors, 0, 3));
            if (count($rowErrors) > 3) {
                $errorMessage .= " ... and " . (count($rowErrors) - 3) . " more";
            }

            \Log::error('Excel validation error: ' . $errorMessage);

            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 422);
        } catch (\Exception $e) {
            // Generic error handling - ensure we always return JSON
            \Log::error('Upload exception: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(), ['trace' => $e->getTraceAsString()]);
            
            $errorMessage = $e->getMessage();

            // Provide more user-friendly error messages
            if (str_contains($errorMessage, 'Undefined array key')) {
                $errorMessage = "❌ Column mismatch. File headers don't match table columns.";
            } elseif (str_contains($errorMessage, 'SQLSTATE')) {
                $dbError = preg_replace('/SQLSTATE.*?:\s*/', '', $errorMessage);
                $errorMessage = "❌ Database error: " . substr($dbError, 0, 100);
            } elseif (str_contains($errorMessage, 'table') || str_contains($errorMessage, 'not found')) {
                $errorMessage = "❌ Database table not found.";
            } elseif (empty($errorMessage)) {
                $errorMessage = "❌ Unknown error. Check browser console and server logs.";
            } else {
                $errorMessage = "❌ " . substr($errorMessage, 0, 150);
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }

    public function export($module)
    {
        try {
            // Serve the template file from public/excel folder
            $filename = $module . '.xlsx';
            $filePath = public_path('excel/' . $filename);

            // Check if the template file exists
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => "Template file '{$filename}' not found in excel folder"
                ], 404);
            }

            // Return the file for download
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportData($module)
    {
        try {
            // Get all data from the specified table
            $data = DB::table($module)->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No data found in {$module} table"
                ], 404);
            }

            // Convert to array for export
            $exportData = $data->toArray();

            // Get currency symbol from CompanyInformation
            $currencySymbol = '';
            try {
                $company = CompanyInformation::first();
                $currencySymbol = $company ? ($company->currency ?? '') : '';
            } catch (\Exception $e) {
                // Currency symbol will remain empty if there's any error
            }

            // Transform data based on module type
            $exportData = $this->transformExportData($exportData, $module, $currencySymbol);

            // Create filename with timestamp
            $filename = $module . '_data_' . date('Y-m-d_His') . '.xlsx';

            // Create Excel export using PhpSpreadsheet
            return response()->streamDownload(function() use ($exportData) {
                // Create a new Spreadsheet object
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Add headers (column names from first row)
                if (!empty($exportData)) {
                    $headers = array_keys((array)$exportData[0]);
                    $sheet->fromArray($headers, null, 'A1');

                    // Style header row
                    $headerStyle = [
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
                    ];
                    $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

                    // Add data rows
                    $rowIndex = 2;
                    foreach ($exportData as $row) {
                        $sheet->fromArray(array_values((array)$row), null, 'A' . $rowIndex);
                        $rowIndex++;
                    }

                    // Auto-size columns
                    foreach (range('A', $sheet->getHighestColumn()) as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                }

                // Write to output
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');

            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function transformExportData($data, $module, $currencySymbol)
    {
        // Pre-load lookup data for efficient transformation
        $lookupData = $this->prepareLookupData($module);

        // Transform data based on the module
        return array_map(function($row) use ($module, $currencySymbol, $lookupData) {
            $row = (array)$row;

            // (No status transformation: keep raw value 0/1/2)

            // (No type transformation: keep raw numeric value 0/1)

            return $row;
        }, $data);
    }

    /**
     * Prepare lookup data (foreign key mappings) for efficient transformations
     */
    private function prepareLookupData($module)
    {
        $lookupData = [];

        // Always load categories for parent_id and category_id resolution
        $lookupData['categories'] = DB::table('categories')
            ->select('id', 'name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        // Load product-related lookups if exporting products
        if ($module === 'products') {
            $lookupData['brands'] = DB::table('brands')
                ->select('id', 'name')
                ->get()
                ->pluck('name', 'id')
                ->toArray();

            $lookupData['types'] = DB::table('types')
                ->select('id', 'name')
                ->get()
                ->pluck('name', 'id')
                ->toArray();

            $lookupData['discounts'] = DB::table('discounts')
                ->select('id', 'name')
                ->get()
                ->pluck('name', 'id')
                ->toArray();

            $lookupData['taxes'] = DB::table('taxes')
                ->select('id', 'name')
                ->get()
                ->pluck('name', 'id')
                ->toArray();

            $lookupData['measurement_units'] = DB::table('measurement_units')
                ->select('id', 'symbol')
                ->get()
                ->pluck('symbol', 'id')
                ->toArray();
        }

        return $lookupData;
    }

    /**
     * Resolve foreign key ID to its display name/label
     */
    private function resolveForeignKey($id, $lookupMap)
    {
        // Handle null or empty values
        if (!$id) {
            return '-';
        }

        // Return the mapped value or 'Unknown' if not found
        return $lookupMap[$id] ?? 'Unknown';
    }

    public function exportHeaders($module)
    {
        try {
            // Get table structure (column names) from MySQL
            $columns = DB::select("DESCRIBE {$module}");

            if (empty($columns)) {
                return response()->json([
                    'success' => false,
                    'message' => "Table {$module} not found or has no columns"
                ], 404);
            }

            // Extract column names
            $headers = array_map(function($col) {
                return $col->Field;
            }, $columns);

            // Create filename with timestamp
            $filename = $module . '_headers_' . date('Y-m-d_His') . '.xlsx';

            // Create Excel export with headers only
            return response()->streamDownload(function() use ($headers) {
                // Create a new Spreadsheet object
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Add headers (column names)
                $sheet->fromArray($headers, null, 'A1');

                // Style header row
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFA500']]
                ];
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

                // Auto-size columns
                foreach (range('A', $sheet->getHighestColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Write to output
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');

            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Header export failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
