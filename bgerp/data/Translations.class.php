<?php


/**
 * Клас 'bgerp_data_Translations'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_data_Translations
{
    
    
    /**
     * Зареждане на стирнговете, които ще се превеждат
     */
    static function loadData()
    {
        //Пътя до CSV файла
        $csvFile = self::getCsvFile();
        
        //Коко записа са създадени
        $created = 0;
        
        //Ако не може да се намери файла или нямаме права за работа с него
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            
            //обхождаме целия файл
            while (($csvRow = fgetcsv($handle, 1000, "|")) !== FALSE) {
                
                //Създаваме обект, който ще записваме в БД
                $rec = new stdClass();
                
                //Езика на който ще се превежда
                $rec->lg = $csvRow[0];
                
                //Стринга, който ще се превежда
                $rec->kstring = $csvRow[1];
                
                //Преведения стринг
                $rec->translated = $csvRow[2];
                
                //Създаден от системата
                $rec->createdBy = -1;

                //Ако запишем успешно, добава единица в общия брой записи
                if (core_Lg::save($rec, NULL, 'REPLACE')) {
                    $created++;    
                }
            }
            
            //Затваряме файла
            fclose($handle);
            
            //Съобщението което ще се показва след като приключим
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови превода.</li>";
        } else {
            //Ако има проблем при отварянето на файла
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
    
    
    /**
     * Връща пътя до CSV файла
     */
    static private function getCsvFile()
    {
        
        return __DIR__ . "/csv/Translations.csv";
    }
}