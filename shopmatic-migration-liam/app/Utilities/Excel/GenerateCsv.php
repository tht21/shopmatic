<?php

namespace App\Utilities\Excel;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class GenerateCsv implements WithEvents
{

    private $data;
    private $headers;

    /**
     * GenerateCsv constructor.
     * @param array $headers
     * @param array $data
     */
    public function __construct($headers = [], $data = [])
    {
        $this->data = $data;
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->headers as $y => $headersRow) {
                    $x = 'A';

                    foreach ($headersRow as $header) {
                        $coordinate = $x . ($y + 1);
                        $sheet->setCellValue($coordinate, $header);
                        $x++;
                    }
                }

                foreach ($this->data as $y => $dataRow) {
                    $x = 'A';

                    foreach ($dataRow as $data) {
                        $coordinate = $x . ($y + 1 + count($this->headers));
                        $sheet->setCellValue($coordinate, $data);
                        $x++;
                    }
                }
            }
        ];
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
        return collect($this->data);
    }

}
