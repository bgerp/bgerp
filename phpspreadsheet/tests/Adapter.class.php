<?php


use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

/**
 * Проверки на типовете след запис и прочит на реален XLS файл
 *
 * @category  bgerp
 * @package   phpspreadsheet
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class phpspreadsheet_tests_Adapter extends unit_Class
{
    public static function test_roundTrip()
    {
        expect(phpspreadsheet_Adapter::isAvailable(), 'PhpSpreadsheet не е инсталиран');
        $fields = new core_FieldSet();
        $fields->FLD('code', 'varchar', 'caption=Код');
        $fields->FLD('quantity', 'double', 'caption=Количество');
        $fields->FLD('price', 'varchar', 'caption=Цена,exportNumeric');
        $fields->FLD('discount', 'percent', 'caption=Отстъпка');
        $fields->FLD('count', 'int', 'caption=Брой');
        $fields->FLD('notes', 'varchar', 'caption=Бележки');
        $fields->FLD('large', 'bigint', 'caption=Голям номер');
        $fields->FLD('date', 'date', 'caption=Дата');
        $fields->FLD('numericCode', 'varchar', 'caption=Числов код');
        $recs = array(
            (object) array('code' => '00123', 'quantity' => 1234.5, 'price' => '12.34567', 'discount' => 0.2,
                'count' => 0, 'notes' => "Ред 1, \"цитат\"\nРед 2\\", 'large' => '123456789012345678',
                'date' => '2026-01-02', 'numericCode' => '12345'),
            (object) array('code' => '=1+1', 'quantity' => -2.75, 'price' => 0, 'discount' => 0,
                'count' => -3, 'notes' => '+SUM(A1:A2)'),
            (object) array('code' => 'empty'),
        );
        $params = array('delimiter' => ';', 'enclosure' => '"', 'decPoint' => ',', 'thousandsSep' => ' ', 'decimals' => 2);
        $book = self::roundTrip($recs, $fields, null, $params);
        try {
            $sheet = $book->getActiveSheet();
            self::checkCell($sheet, 'A1', tr('Код'), DataType::TYPE_STRING);
            self::checkCell($sheet, 'A2', '00123', DataType::TYPE_STRING);
            self::checkCell($sheet, 'B2', 1234.5, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'C2', 12.34567, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'D2', 0.2, DataType::TYPE_NUMERIC);
            expect($sheet->getStyle('D2')->getNumberFormat()->getFormatCode() === '0.00%');
            self::checkCell($sheet, 'E2', 0, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'F2', $recs[0]->notes ?? '', DataType::TYPE_STRING);
            self::checkCell($sheet, 'G2', '123456789012345678', DataType::TYPE_STRING);
            self::checkCell($sheet, 'H2', '02.01.2026', DataType::TYPE_STRING);
            self::checkCell($sheet, 'I2', '12345', DataType::TYPE_STRING);
            self::checkCell($sheet, 'A3', '=1+1', DataType::TYPE_STRING);
            self::checkCell($sheet, 'B3', -2.75, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'C3', 0, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'D3', 0, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'E3', -3, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'F3', '+SUM(A1:A2)', DataType::TYPE_STRING);
            expect(!$sheet->getCell('B4')->getValue(), 'Празната стойност трябва да остане празна');
            expect($sheet->getHighestRow() == 4);
        } finally {
            $book->disconnectWorksheets();
        }
    }


    public static function test_selectedColumnsAndEncoding()
    {
        $fields = new core_FieldSet();
        $fields->FLD('code', 'varchar');
        $fields->FLD('quantity', 'double');
        $fields->FLD('title', 'varchar');
        $recs = array((object) array('code' => '001', 'quantity' => 3.5, 'title' => "Текст 'с цитат'"));
        $selected = array('title' => 'Заглавие', 'quantity' => 'Количество', 'code' => 'Код');
        $params = array('delimiter' => '|', 'enclosure' => "'", 'decPoint' => '.', 'thousandsSep' => '',
            'columns' => 'none', 'encoding' => 'cp1251');
        $book = self::roundTrip($recs, $fields, $selected, $params);
        try {
            $sheet = $book->getActiveSheet();
            self::checkCell($sheet, 'A1', "Текст 'с цитат'", DataType::TYPE_STRING);
            self::checkCell($sheet, 'B1', 3.5, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'C1', '001', DataType::TYPE_STRING);
            expect($sheet->getHighestRow() == 1);
        } finally {
            $book->disconnectWorksheets();
        }
    }


    public static function test_withoutSchema()
    {
        $path = tempnam(sys_get_temp_dir(), 'xls-test-');
        $book = null;
        try {
            phpspreadsheet_Adapter::writeXls("Code;Number;Text;Long;Date;Negative;Scientific\n00123;12,50;=1+1;123456789012345678;01.02.2026;-2,75;1E10", $path,
                null, null, array('delimiter' => ';', 'decPoint' => ','));
            $book = (new Xls())->load($path);
            $sheet = $book->getActiveSheet();
            self::checkCell($sheet, 'A2', '00123', DataType::TYPE_STRING);
            self::checkCell($sheet, 'B2', 12.5, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'C2', '=1+1', DataType::TYPE_STRING);
            self::checkCell($sheet, 'D2', '123456789012345678', DataType::TYPE_STRING);
            self::checkCell($sheet, 'E2', '01.02.2026', DataType::TYPE_STRING);
            self::checkCell($sheet, 'F2', -2.75, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'G2', '1E10', DataType::TYPE_STRING);
        } finally {
            if ($book) $book->disconnectWorksheets();
            unlink($path);
        }
    }


    public static function test_csvUnchanged()
    {
        $fields = new core_FieldSet();
        $fields->FLD('price', 'varchar', 'caption=Price');
        $recs = array((object) array('price' => '12.34567'));
        $csv = csv_Lib::createCsv($recs, $fields);
        $fields->setField('price', 'exportNumeric');
        expect($csv === csv_Lib::createCsv($recs, $fields), 'Метаданните за XLS не трябва да променят CSV');
    }


    public static function test_productListFields()
    {
        $fields = new core_FieldSet();
        deals_Helper::getExportCsvProductFieldset((object) array('productFld' => 'productId'), $fields);
        $recs = array((object) array('code' => '00123', 'packQuantity' => '2.5', 'packPrice' => '3.45678',
            'discount' => '0.15', 'vatPercent' => 0.2));
        $selected = array('code' => 'Code', 'packQuantity' => 'Quantity', 'packPrice' => 'Price',
            'discount' => 'Discount', 'vatPercent' => 'VAT');
        $book = self::roundTrip($recs, $fields, $selected, array('decPoint' => '.', 'thousandsSep' => ''));
        try {
            $sheet = $book->getActiveSheet();
            self::checkCell($sheet, 'A2', '00123', DataType::TYPE_STRING);
            self::checkCell($sheet, 'B2', 2.5, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'C2', 3.45678, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'D2', 0.15, DataType::TYPE_NUMERIC);
            self::checkCell($sheet, 'E2', 0.2, DataType::TYPE_NUMERIC);
        } finally {
            $book->disconnectWorksheets();
        }
    }


    public static function test_limits()
    {
        $path = tempnam(sys_get_temp_dir(), 'xls-test-');
        try {
            $failed = false;
            try {
                phpspreadsheet_Adapter::writeXls(implode(',', array_fill(0, 257, 'value')), $path,
                    new core_FieldSet(), null, array('delimiter' => ','));
            } catch (LengthException $e) {
                $failed = true;
            }
            expect($failed, 'Над 256 колони не трябва да се отрязват мълчаливо');
        } finally {
            unlink($path);
        }
    }


    private static function roundTrip($recs, $fields, $selected, $params)
    {
        $params['dateFormat'] = 'd.m.Y';
        $csv = csv_Lib::createCsv($recs, $fields, $selected, $params);
        $csv = mb_convert_encoding($csv, $params['encoding'] ?? 'UTF-8', 'UTF-8');
        $path = tempnam(sys_get_temp_dir(), 'xls-test-');
        try {
            phpspreadsheet_Adapter::writeXls($csv, $path, $fields, $selected, $params);

            return (new Xls())->load($path);
        } finally {
            unlink($path);
        }
    }


    private static function checkCell($sheet, $address, $value, $type)
    {
        $cell = $sheet->getCell($address);
        expect($cell->getDataType() === $type, "Грешен тип в {$address}");
        expect($cell->getValue() == $value, "Грешна стойност в {$address}");
    }


    public function cli_Run()
    {
        try {
            foreach (get_class_methods($this) as $method) {
                if (strpos($method, 'test_') === 0) {
                    self::$method();
                    echo $method . ": OK\n";
                }
            }
        } catch (Throwable $e) {
            echo $method . ': FAILED - ' . $e->getMessage() . "\n";

            return 1;
        }
    }
}
