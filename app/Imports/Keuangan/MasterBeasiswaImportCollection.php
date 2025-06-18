<?php

namespace App\Imports\Keuangan;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class MasterBeasiswaImportCollection implements ToCollection, WithHeadingRow, WithStartRow
{
    public $rows;

    public function startRow(): int
    {
        return 2; // start reading from the 3rd row
    }

    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }
}
