<?php

namespace App\Utilities\Excel;

use App\Constants\ExcelType;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class VerifyExcel implements WithEvents
{
    private $sheetNames;
    private $type;

    public function __construct()
    {
        $this->sheetNames = [];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                $this->sheetNames[] = $event->sheet->getDelegate()->getTitle();

                $fileType = [
                    'CREATE_PRODUCTS' => ['Create Products' => true],
                ];

                $excelType = ExcelType::toArray();

                foreach ($this->sheetNames as $sheetName) {
                    // remove all extra data save in sheet name (exp: category id)
                    $sheetName = explode(',', $sheetName)[0];

                    foreach ($fileType as $typeName => $typeList) {
                        if (isset($typeList[$sheetName])) {
                            unset($fileType[$typeName][$sheetName]);
                        }
                    }
                }

                foreach ($fileType as $typeName => $typeList) {
                    if (empty($typeList)) {
                        $this->type = $excelType[$typeName];
                        break;
                    }
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
