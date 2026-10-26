<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class GenericImport implements ToCollection, WithHeadingRow
{
    protected $modelClass;
    protected $extraData;

    public function __construct($modelClass, $extraData = [])
    {
        $this->modelClass = $modelClass;
        $this->extraData = $extraData;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Check if row is entirely empty
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $data = $this->extraData;
            foreach ($row as $key => $value) {
                // Skip empty headers
                if (empty($key)) continue;
                $data[$key] = $value;
            }

            try {
                $this->modelClass::create($data);
            } catch (\Exception $e) {
                Log::error("Import error for {$this->modelClass}: " . $e->getMessage());
                // Silently skip or throw, for now just skip invalid rows
            }
        }
    }
}
