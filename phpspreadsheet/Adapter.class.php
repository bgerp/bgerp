<?php


use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

/**
 * XLS от подготвения CSV, с изрични типове на клетките
 *
 * @category  vendors
 * @package   phpspreadsheet
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class phpspreadsheet_Adapter extends core_BaseClass
{
    public $interfaces = 'export_XlsConverterIntf';


    public $title = 'PhpSpreadsheet';


    /**
     * Не изпълнява Composer команди при експорт
     */
    public static function isAvailable()
    {
        return core_Packs::isInstalled('phpspreadsheet') && core_Composer::isInUse()
            && class_exists(Spreadsheet::class) && class_exists(Xls::class);
    }


    /**
     * @param string   $fileHnd
     * @param stdClass $csvData
     * @return string
     */
    public static function convertToXls($fileHnd, $csvData = null)
    {
        $csv = fileman::getContent($fileHnd);
        expect($csv !== false, 'Не може да се прочете CSV файлът');
        $tempDir = fileman::getTempPath();

        try {
            $name = fileman_Files::getFileNameWithoutExt($fileHnd) . '.xls';
            $path = $tempDir . '/' . $name;
            static::writeXls($csv, $path, $csvData->fieldSet ?? null, $csvData->listFields ?? null, $csvData->params ?? array());

            return fileman::absorb($path, 'exportFiles');
        } finally {
            core_Os::deleteDir($tempDir);
        }
    }


    /**
     * Запазва колоните, редовете и вербализацията на съществуващия CSV експорт
     *
     * @param string        $csv
     * @param string        $path
     * @param core_FieldSet|null $fieldSet - без описание се разпознават само обикновени числа
     * @param array|null    $listFields
     * @param array         $params
     */
    public static function writeXls($csv, $path, core_FieldSet $fieldSet = null, $listFields = null, $params = array())
    {
        $encoding = $params['encoding'] ?? 'UTF-8';
        $csv = mb_convert_encoding($csv, 'UTF-8', $encoding);
        if (substr($csv, 0, 3) === "\xEF\xBB\xBF") {
            $csv = substr($csv, 3);
        }
        $delimiter = $params['delimiter'] ?? csv_Setup::get('DELIMITER');
        $delimiter = str_replace(array('&comma;', 'semicolon', 'colon', '&vert;', '&Tab;', 'comma', 'vertical'), array(',', ';', ':', '|', "\t", ',', '|'), $delimiter);
        $delimiter = html_entity_decode($delimiter, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $enclosure = $params['enclosure'] ?? '"';
        if (strlen($delimiter) != 1 || strlen($enclosure) != 1) {
            throw new InvalidArgumentException('Неподдържан CSV разделител или ограждане');
        }

        $fields = $listFields === null ? ($fieldSet ? $fieldSet->selectFields() : array()) : arr::make($listFields, true);
        $names = array_keys($fields);
        $hasHeader = ($params['columns'] ?? null) != 'none';
        $stream = fopen('php://temp', 'w+');
        $spreadsheet = null;
        try {
            fwrite($stream, $csv);
            rewind($stream);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $row = 0;
            // Без escape символ: csv_Lib удвоява ограждането, а запазва обратните наклонени черти.
            while (($values = fgetcsv($stream, 0, $delimiter, $enclosure, '')) !== false) {
                if (++$row > 65536 || count($values) > 256) {
                    throw new LengthException('Данните надвишават ограниченията на XLS');
                }
                foreach ($values as $col => $value) {
                    $value = $value ?? '';
                    $name = $names[$col] ?? null;
                    $field = $fieldSet->fields[$name] ?? null;
                    $number = ($hasHeader && $row == 1) ? null : static::getNumber($value, $field, $fieldSet === null, $params);
                    $cell = Coordinate::stringFromColumnIndex($col + 1) . $row;
                    if ($number !== null) {
                        $sheet->setCellValueExplicit($cell, $number, DataType::TYPE_NUMERIC);
                        $type = $field->type ?? null;
                        if ($type instanceof type_Percent) {
                            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0.00%');
                        }
                    } else {
                        // И кодовете, и текстовете, започващи с "=", остават текст.
                        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
                    }
                }
            }
            $writer = new Xls($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($path);
        } finally {
            fclose($stream);
            if ($spreadsheet) {
                $spreadsheet->disconnectWorksheets();
            }
        }
    }


    /**
     * Разпознава числа само в числови полета; не гадае по съдържанието на кодовете
     */
    protected static function getNumber($value, $field, $autoDetect = false, $params = array())
    {
        $type = $field->type ?? null;
        $rawNumber = !empty($field->exportNumeric);
        if (!$autoDetect && !$rawNumber && (!($type instanceof type_Double || $type instanceof type_Int)
            || $type instanceof type_Key || $type instanceof type_Key2 || $type instanceof fileman_FileSize)) {
            return null;
        }

        $number = trim($value);
        if ($autoDetect) {
            $decPoint = html_entity_decode($params['decPoint'] ?? csv_Setup::get('DEC_POINT'), ENT_COMPAT | ENT_HTML401, 'UTF-8');
            if ($decPoint !== '' && $decPoint !== '.') {
                $number = str_replace($decPoint, '.', $number);
            }
            // Водещи нули, знак "+" и записи като "1E10" може да са кодове.
            if (!preg_match('/^-?(?:0|[1-9]\d*)(?:\.\d+)?$/D', $number)) {
                return null;
            }
        }
        $percent = $type instanceof type_Percent && substr($number, -1) === '%';
        if ($percent) {
            $number = rtrim(substr($number, 0, -1));
        }
        if (!$rawNumber && $type instanceof type_Double) {
            $thousandsSep = html_entity_decode($type->params['thousandsSep'] ?? '', ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $decPoint = html_entity_decode($type->params['decPoint'] ?? '.', ENT_COMPAT | ENT_HTML401, 'UTF-8');
            if ($thousandsSep !== '') {
                $number = str_replace($thousandsSep, '', $number);
            }
            if ($decPoint !== '' && $decPoint !== '.') {
                $number = str_replace($decPoint, '.', $number);
            }
        }
        if (!preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/D', $number) || !is_finite((float) $number)) {
            return null;
        }
        // Excel пази до 15 значещи цифри; дългите цели числа не бива да се закръглят.
        if (preg_match('/^[+-]?\d{16,}(?:\.|$)/D', $number)) {
            return null;
        }

        return $percent ? (float) $number / 100 : (float) $number;
    }
}
