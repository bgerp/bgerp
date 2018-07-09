<?php


/**
 * Клас за тестване на'csv_Lib' - Пакет за работа с CSV файлове
 *
 *
 * @category  bgerp
 * @package   csv
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class csv_tests_Lib extends unit_Class
{
    /**
     * Импортира CSV файл в указания модел
     */
    public static function test_import($mvc)
    {
        $mvc = cls::get('cal_Holidays');
        $file = '/csv/tests/Tests.csv';
        
        $res = csv_Lib::import($mvc, $file);
        $res->html = trim($res->html);
        
        $expect = new stdClass();
        $expect->created = 4;
        $expect->updated = 0;
        $expect->skipped = 0;
        $expect->html = "<li style='color:green;'>Създадени са 4 записа в cal_Holidays</li>";
        
        ut::expectEqual($expect, $res);
    }
    
    
    /**
     * Създава csv
     */
    public static function test_createCsv($mvc)
    {
        $format['length'] = 0;
        $format['delimiter'] = ',';
        $format['enclosure'] = '"';
        $format['escape'] = '\\';
        $format['skip'] = '#';
        
        $file = '/csv/tests/Tests.csv';
        $path = getFullPath($file);
        
        expect(($handle = fopen($path, 'r')) !== false);
        
        $resArr = array();
        
        while (($data = fgetcsv($handle, $format['length'], $format['delimiter'], $format['enclosure'], $format['escape'])) !== false) {
            $allRows[] = $data;
            
            foreach ($allRows as $id => $rec) {
                foreach ($rec as $i => $d) {
                    if (!array_key_exists($id, $resArr)) {
                        $resArr[$id] = (object) array("f{$i}" => $d);
                    } else {
                        $obj = &$resArr[$id];
                        $obj->{"f{$i}"} = $d;
                    }
                }
            }
        }
        
        $caption = array_slice($resArr, 0, 1);
        $restData = array_slice($resArr, 1, count($resArr) - 1);
        
        $listFields = array();
        
        foreach ($caption as $header) {
            foreach (range(0, 9) as $i) {
                if (!empty($header->{"f{$i}"})) {
                    $listFields["f{$i}"] = $header->{"f{$i}"};
                }
            }
        }
        
        // Кои полета ще се показват
        $fieldSet = new core_FieldSet;
        $fieldSet->FLD('f0', 'varchar');
        $fieldSet->FLD('f1', 'richtext');
        $fieldSet->FLD('f2', 'int');
        $fieldSet->FLD('f3', 'date');
        $fieldSet->FLD('f4', 'double');
        $fieldSet->FLD('f5', 'percent');
        
        $res = csv_Lib::createCsv($restData, $fieldSet, $listFields, $format);
        $trimRes = trim($res);
        $expect = "Тип на документа,Автор,Създадени документи (бр.)\nЗадачи,\"| <a class=\"\"\"\" profile ceo inactive\"\"\"\" title=\"\"\"\"Йордан Бонев\"\"\"\" href=\"\"\"\"/crm_Profiles/single/1OGU/\"\"\"\">Bachko</a>|\",7\nЗадачи,\"<a class=\"\" profile ceo active\"\" title=\"\"Gabriela Petrova\"\" href=\"\"/crm_Profiles/single/27NmR/\"\">Gaby</a>\",1\nВарниш (DS-09454 a),кг,95\n#Ч-та LD вън.др 50х40 - ГЕПАРД,\"<a href=\"\"/sales_Invoices/single/5739YjN/\"\" class=\"\" linkWithIcon\"\" style=\"\"background-image:url(&#039;/sbf/bgerp/img/16/invoice_0722143638.png&#039;);\"\">0000005642</a>\",15 800\nЧ-та LD вън.др 50х40 - ГЕПАРД - 3986652,#Sal5664,4 000.0000\n##Ч-та LD вън.др 50х40 - ГЕПАРД - 3986652,\||Sal5664|,4 000 0000\n";
        $trimExp = trim($expect);
        
        ut::expectEqual($trimExp, $trimRes);
    }
}
