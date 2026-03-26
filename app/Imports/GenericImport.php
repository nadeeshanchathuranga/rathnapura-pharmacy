<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;

class GenericImport implements ToCollection
{
    // The module/table name to import data into
    protected $module;
    protected $insertedCount = 0;
    protected $updatedCount = 0;

    public function __construct($module)
    {
        $this->module = $module;
    }
    
    /**
     * This method is called for each uploaded Excel file.
     * It expects the first row to be the header matching the table columns.
     * Each subsequent row is inserted/updated in the database.
     * 
     * Features:
     * - Skips empty rows
     * - Updates existing records with same ID (no duplicates)
     * - Inserts new records with new IDs
     * - Tracks inserted vs updated counts
     *
     * @param Collection $rows
     */

    public function collection(Collection $rows)
    {
        $table = $this->module;
        $header = $rows->first()->toArray(); // Get column names from first row
        $dataRows = $rows->slice(1); // Skip header row

        foreach ($dataRows as $row) {
            // Skip empty rows
            $rowArray = $row->toArray();
            if (empty(array_filter($rowArray))) {
                continue;
            }
            
            $data = array_combine($header, $rowArray); // Map columns to values
            
            // Skip if no ID provided or ID is null
            if (empty($data['id'])) {
                continue;
            }
            
            // Check if record with this ID already exists
            $existingRecord = DB::table($table)->where('id', $data['id'])->first();
            
            if ($existingRecord) {
                // Update existing record
                DB::table($table)->where('id', $data['id'])->update($data);
                $this->updatedCount++;
            } else {
                // Insert new record only if ID doesn't exist
                DB::table($table)->insert($data);
                $this->insertedCount++;
            }
        }
    }
    
    /**
     * Get the count of inserted records
     */
    public function getInsertedCount()
    {
        return $this->insertedCount;
    }
    
    /**
     * Get the count of updated records
     */
    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }
}