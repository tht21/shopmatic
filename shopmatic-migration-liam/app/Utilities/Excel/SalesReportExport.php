<?php

namespace App\Utilities\Excel;

use App\Models\Report;
use Maatwebsite\Excel\Concerns\FromCollection;

class SalesReportExport implements FromCollection
{
    public function collection()
    {
        return Report::all();
    }
}
