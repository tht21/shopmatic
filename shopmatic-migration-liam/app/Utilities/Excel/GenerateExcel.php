<?php

namespace App\Utilities\Excel;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenerateExcel implements WithEvents, WithTitle, WithMultipleSheets
{
    private $sheet;
    private $otherSheet;
    private $title;
    private $headers;
    private $data;
    private $extraSetting;
    private $type;

    /**
     * GenerateExcel constructor.
     * @param string|array $title
     * @param array $headers
     * @param array $data
     * @param array $extraSetting
     * @param null $type 0 => main, 1 => main with option, 2 => option
     * @param null $otherSheet
     */
    public function __construct($title = 'Default Title', $headers = [], $data = [], $extraSetting = [], $type = null, $otherSheet = null)
    {
        $this->sheet = null;
        $this->otherSheet = $otherSheet;
        $this->title = $title;
        $this->headers = $headers;
        $this->data = $data;
        $this->extraSetting = $extraSetting;
        $this->type = $type;

        if (is_null($this->type)) {
            foreach ($this->headers as $header) {
                foreach ($header as $headerRow) {
                    // if there is options available, means this excel will have multiple sheets to store the options
                    if (array_key_exists('options', $headerRow) && !empty($headerRow['options'])) {
                        $this->type = 1;
                        break;
                    }
                }
            }
        }

    }

    /**
     * @inheritDoc
     */
    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $workbook = $event->getWriter()->getDelegate();
                $workbook->getSecurity()->setLockStructure(true);
                $workbook->getSecurity()->setWorkbookPassword('UNPROTECTaTyOuRoWnRIsK');
            },
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // lock all cell, prevent user edit
                $sheet->getProtection()->setPassword('UNPROTECTaTyOuRoWnRIsK');

                $y = 1;
                $headersRowCount = count($this->headers);


                /* setup header row - START */
                if ($headersRowCount > 0) {
                    foreach ($this->headers as $headersRow) {
                        $x = 'A';
                        foreach ($headersRow as $header) {
                            $coordinate = $header['coordinate'] ?? $x.$y;
                            $style = $header['style'] ?? [];

                            // apply global header style on every headers
                            if (array_key_exists('header_style', $this->extraSetting)) {
                                $style = array_merge($style, $this->extraSetting['header_style']);
                            }

                            $this->setCell($sheet, $coordinate, $header['value'], $style);
                            $x++;
                        }
                        $y++;
                    }
                }
                /* setup header row - END */

                /* setup data row - START */
                // prepare space for user to fill in data
                if (empty($this->data)) {

                    // only let user fill in 100 row data
                    while ($y <= 100 + $headersRowCount) {
                        $x = 'A';
                        for ($counter = 0; $counter < count($this->headers[$headersRowCount - 1]); $counter++) {
                            $style = ['protection' => false];
                            // apply global body style on every headers
                            if (array_key_exists('body_style', $this->extraSetting)) {
                                $style = array_merge($style, $this->extraSetting['body_style']);
                            }
                            if (array_key_exists('option_range', $this->headers[1][$counter])) {
                                $this->setDropdown($sheet, $x, $y, $this->headers[1][$counter]['option_range']);
                            }
                            // Check if maximum_length flag is set.
                            if (array_key_exists('maximum_length', $this->headers[1][$counter]) && is_numeric($this->headers[1][$counter]['maximum_length'])) {
                                $this->setCellMaximumLength($sheet, $x, $y, $this->headers[1][$counter]['maximum_length']);
                            }
                            $this->setCell($sheet, $x.$y, '', $style);
                            $x++;
                        }
                        $y++;
                    }
                } elseif (!empty($this->data)) {

                    // fill in data by row or column, default is row
                    $fillInBy = 'row';
                    if (array_key_exists('data_flow_column', $this->extraSetting)) {
                        if ($this->extraSetting['data_flow_column']) {
                            $fillInBy = 'column';
                        }
                    }

                    // starting column or row
                    $xStart = 'A';
                    $yStart = $y;

                    $x = $xStart;
                    foreach ($this->data as $rowOrColumn) {
                        if ($fillInBy === 'column') {
                            $y = $yStart;
                        } else {
                            $x = $xStart;
                        }

                        foreach ($rowOrColumn as $index => $value) {
                            $style = array_key_exists('style', $rowOrColumn) ? array_merge( $rowOrColumn['style'], ['protection' => false]) : ['protection' => false];

                            // apply global body style on body
                            if (array_key_exists('body_style', $this->extraSetting)) {
                                $style = array_merge($style, $this->extraSetting['body_style']);
                            }

                            // apply global column style on body's column
                            if (array_key_exists('column_style', $this->extraSetting) && (array_key_exists($x, $this->extraSetting['column_style']) || array_key_exists($index, $this->extraSetting['column_style']))) {
                                $style = array_merge($style, $this->extraSetting['column_style'][$index] ?? $this->extraSetting['column_style'][$x]);
                            }

                            // check if current cell has/need option range set
                            $xIndex = $this->alphabetToNumber($x);

                            // check if value is an array and has option range set
                            if ($headersRowCount >= 2 && array_key_exists($xIndex, $this->headers[1]) && array_key_exists('option_range', $this->headers[1][$xIndex])) {
                                $this->setDropdown($sheet, $x, $y, $this->headers[1][$xIndex]['option_range']);

                                if (is_array($value) || $value == "[\"true\"]") {
                                    $value = !empty($value['option']) ? $value['option'] : '';
                                }
                            }
                            $this->setCell($sheet, $x.$y, $value, $style);
                            if ($fillInBy === 'column') {
                                $y++;
                            } else {
                                $x++;
                            }
                        }

                        if ($fillInBy === 'column') {
                            $x++;
                        } else {
                            $y++;
                        }
                    }
                }
                /* setup data row - END */

                /* setup default cell style - START */
                $borders = [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    'top'        => ['borderStyle' => Border::BORDER_THICK],
                    'bottom'     => ['borderStyle' => Border::BORDER_THICK],
                    'left'       => ['borderStyle' => Border::BORDER_THICK],
                    'right'      => ['borderStyle' => Border::BORDER_THICK]
                ];

                $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
                    'borders' => $borders
                ]);

                if (array_key_exists('freeze_pane', $this->extraSetting)) {
                    $sheet->freezePane($this->extraSetting['freeze_pane']);
                }
                /* setup default cell style - END */

                // hide sheet if title starts with 'no_read_'
                if (starts_with($this->title, 'no_read_')) {
                    $sheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
                }

                // enable cell password protection
                $sheet->getProtection()->setSheet(true);
                $this->sheet = $sheet;
            }
        ];
    }

    /**
     * @inheritDoc
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function sheets(): array
    {
        if ($this->type === 1) {
            $sheets = [];
            $options = [];

            $x = 'A';
            foreach ($this->headers as $headerIndex => $header) {
                foreach ($header as $headerRowIndex => $headerRow) {
                    if (array_key_exists('options', $headerRow) && !empty($headerRow['options'])) {
                        $options[] = $headerRow['options'];
                        $this->headers[$headerIndex][$headerRowIndex]['option_range'] = '$'.$x.'$1:'.'$'.$x.'$'.count($headerRow['options']);
                        $x++;
                    }
                }
            }

            $sheets[] = new GenerateExcel('no_read_options', [], $options, ['data_flow_column' => true]);
            $sheets[] = new GenerateExcel($this->title, $this->headers, $this->data, $this->extraSetting, 0, $sheets[0]);
            $this->type = 0;
            return $sheets;
        }
        return [$this];
    }

    /**
     * useful for others to get access current sheet from outside
     *
     * @return Worksheet|null
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    private function alphabetToNumber($value)
    {
        $value = strtoupper($value);
        $length = strlen($value);

        if(preg_match("/^[A-Z]+$/",$value) === false) {
            return null;
        }

        $it = 0;
        $result = 0;

        for($i = $length - 1; $i >- 1; $i--) {
            //cumulate letter value
            $result += (ord($value[$i]) - 64 ) * pow(26,$it);

            //simple counter
            $it++;
        }
        return $result - 1;
    }

    /**
     * Set dropdown for selected cell
     *
     * @param $sheet
     * @param $x cell's x-axis
     * @param $y cell's y-axis
     * @param $optionRange
     * @throws Exception
     */
    public function setDropdown(&$sheet, $x, $y, $optionRange)
    {
        // format: NamedRange(dropdown_unique_name, sheet, cell_range)
        $nameRange = $sheet->getParent()->getNamedRange('option_column_' . $x);
        if (empty($nameRange)) {
            // format: NamedRange(dropdown_unique_name, sheet, cell_range)
            $sheet->getParent()->addNamedRange(new NamedRange('option_column_'.$x, $this->otherSheet->getSheet(), $optionRange));
            $nameRange = $sheet->getParent()->getNamedRange('option_column_' . $x);
        }

        // setup dropdown
        $objValidation = $sheet->getCell($x.$y)->getDataValidation();
        $objValidation->setType(DataValidation::TYPE_LIST);
        $objValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $objValidation->setAllowBlank(false);
        $objValidation->setShowInputMessage(true);
        $objValidation->setShowErrorMessage(true);
        $objValidation->setShowDropDown(true);
        $objValidation->setErrorTitle('Input error');
        $objValidation->setError('Value is not in list.');
        $objValidation->setPromptTitle('Pick from list');
        $objValidation->setPrompt('Please pick a value from the drop-down list.');
        $objValidation->setFormula1($nameRange->getName());
    }

    /**
     * Set cell value and styling
     *
     * @param Worksheet $sheet
     * @param string $coordinate
     * @param string $value
     * @param array $style
     * @throws Exception
     */
    public function setCell(Worksheet &$sheet, string $coordinate,  $value, $style = [])
    {
        $sheet->setCellValue($coordinate, $value);

        // extra cell's style
        if (count($style) > 0) {
            if (array_key_exists('alignment', $style) && is_string($style['alignment'])) {
                $sheet->getStyle($coordinate)->getAlignment()->setHorizontal($style['alignment']);
            }
            if (array_key_exists('vertical_alignment', $style) && is_string($style['vertical_alignment'])) {
                $sheet->getStyle($coordinate)->getAlignment()->setVertical($style['vertical_alignment']);
            }
            if (array_key_exists('bold', $style) && is_bool($style['bold'])) {
                $sheet->getStyle($coordinate)->getFont()->setBold($style['bold']);
            }
            if (array_key_exists('background', $style) && is_string($style['background'])) {
                if (!array_key_exists('fill_type', $style) || !is_string($style['fill_type'])) {
                    $style['fill_type'] = Fill::FILL_SOLID;
                }
                $sheet->getStyle($coordinate)->getFill()->setFillType($style['fill_type'])->getStartColor()->setARGB($style['background']);
            }
            if (array_key_exists('range', $style) && is_string($style['range'])) {
                $sheet->mergeCells($style['range']);
            }
            if (array_key_exists('width', $style) && is_integer($style['width'])) {
                $matches = [];
                if (preg_match('/^([A-Z]+)([0-9]+)$/i', $coordinate, $matches)) {
                    $sheet->getColumnDimension($matches[1])->setWidth($style['width']);
                }
            } elseif (array_key_exists('auto_size', $style) && is_bool($style['auto_size'])) {
                $matches = [];
                if (preg_match('/^([A-Z]+)([0-9]+)$/i', $coordinate, $matches)) {
                    $sheet->getColumnDimension($matches[1])->setAutoSize($style['auto_size']);
                }
            }
            if (array_key_exists('warp_text', $style) && is_bool($style['warp_text'])) {
                $sheet->getStyle($coordinate)->getAlignment()->setWrapText($style['warp_text']);
            }
            if (array_key_exists('protection', $style) && is_bool($style['protection'])) {
                if ($style['protection']) {
                    $sheet->getStyle($coordinate)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                } else {
                    $sheet->getStyle($coordinate)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
                }
            }
        }
    }

    /**
     * Set maximum lenght for the specified cell
     *
     * @param $sheet
     * @param $x cell's x-axis
     * @param $y cell's y-axis
     * @param $length
     * @throws Exception
     */
    public function setCellMaximumLength(&$sheet, $x, $y, $length = 255)
    {
        $objValidation = $sheet->getCell($x.$y)->getDataValidation();
        $objValidation->setType(DataValidation::TYPE_TEXTLENGTH);
        $objValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $objValidation->setAllowBlank(true);
        $objValidation->setShowInputMessage(true);
        $objValidation->setShowErrorMessage(true);
        $objValidation->setErrorTitle('Input error.');
        $objValidation->setError('Text exceeds maximum length.');
        $objValidation->setPromptTitle('Allowed input');
        $objValidation->setPrompt('Maximum text length is '.$length.' characters.');
        $objValidation->setFormula1($length);
    }
}
