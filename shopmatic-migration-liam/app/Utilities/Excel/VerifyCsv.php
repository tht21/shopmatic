<?php

namespace App\Utilities\Excel;

use App\Constants\ExcelType;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\HeadingRowImport;

class VerifyCsv implements WithEvents
{
    private $file;
    private $type;

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetNames[] = $event->sheet->getDelegate()->getTitle();

                $fileType = [
                    'CREATE_INVENTORY' => ['Create Inventory' => true],
                ];

                $excelType = ExcelType::toArray();

                $headings = (new HeadingRowImport(1))->toArray($this->file, 'excel', \Maatwebsite\Excel\Excel::CSV)[0][0];
                $arrayTypeHaveProductName = ['product_name', 'sku', 'stock'];
                $arrayType = ['sku', 'stock'];

                if($headings == $arrayTypeHaveProductName || $headings == $arrayType) {
                    $this->type = $excelType['UPDATE_INVENTORY'];
                }
            }
        ];
    }

    public function getSheetNames()
    {
        return $this->sheetNames;
    }

    public function getType()
    {
        return $this->type;
    }
}
