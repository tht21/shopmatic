<?php

namespace App\Utilities\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeSheet;

class ExtractExcel implements ToCollection, WithHeadingRow, WithEvents
{
    use Importable;

    private $headersLastRow;
    private $sheetNames;
    private $sheetsData;

    /**
     * ExtractExcel constructor.
     * @param int $headersLastRow
     */
    public function __construct(int $headersLastRow)
    {
        $this->headersLastRow = $headersLastRow;
        $this->sheetNames = [];
        $this->sheetsData = [];
    }


    /**
     * @inheritDoc
     */
    public function collection(Collection $collection)
    {
        // skip processing hidden sheet (hidden sheet title start with 'no_read_')
        if (!starts_with(last($this->sheetNames), 'no_read_')) {
            $this->sheetsData[] = array_filter($collection->toArray(), function ($row) {
                // filter empty row
                return !is_array_empty($row);
            });;
        } else {
            array_pop($this->sheetNames);
        }
    }

    /**
     * use selected row as header
     * @return int
     */
    public function headingRow(): int
    {
        return $this->headersLastRow;
    }

    /**
     * @inheritDoc
     */
    public function registerEvents(): array
    {
        return [
            // references code for spreadsheet access
//            BeforeImport::class => function(BeforeImport $event) {
//                $spreadsheet = $event->reader->getDelegate();
//                // remove sheets that contain specific name
//                if ($sheet = $spreadsheet->getSheetByName('no_read_options')) {
//                    $sheetIndex = $spreadsheet->getIndex($sheet);
//                    $spreadsheet->removeSheetByIndex($sheetIndex);
//
//                    // temporary hotfix for unfixable bug, remove this part code to generate the bug
//                    $emptySheet = new Worksheet($spreadsheet, '');
//                    $spreadsheet->addSheet($emptySheet);
////                    $spreadsheet->createSheet();
//                }
//            },
            BeforeSheet::class => function(BeforeSheet $event) {
                $this->sheetNames[] = $event->sheet->getDelegate()->getTitle();
            }
        ];
    }

    /**
     * Get sheet names
     * @return array
     */
    public function getSheetNames(): array
    {
        return $this->sheetNames;
    }

    /**
     * Get rows data
     * @return array
     */
    public function getSheetsData(): array
    {
        return $this->sheetsData;
    }
}
