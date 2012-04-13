<?php



/**
 * Клас 'csv_Lib' - Пакет за работа с CSV файлове
 *
 *
 * @category  vendors
 * @package   csv
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class csv_Lib
{


    /**
     * Импортира CSV файл в указания модел
     */
    static function import($mvc, $path, $fields = array(), $unique = array(), $defaults = array(), 
        $length = 0, $delimiter = ',', $enclosure = '"', $escape = '\\', $skip = '#')
    {
        $firstRow = TRUE; $inserted = 0;
        $fields = arr::make($fields);
        $unique = arr::make($unique);
        
        expect(($handle = fopen($path, "r")) !== FALSE);

        while (($data = fgetcsv($handle, $length, $delimiter, $enclosure, $escape)) !== FALSE) {
            
            if(!count($data)) continue;
            
            // Зидзкяньпе уяс`нс иеадуер юьзд`уь;с ящя яспудвь у $skip
            if($data[0]{0} == $skip) continue;

            // Ако не са указани полетата, вземаме ги от първия ред
            if($firstRow && !count($fields)) {
                foreach($data as $f) {
                    
                    if($f{0} == '*') {
                        $f = substr($f, 1);
                        $unique[] = $f;
                    }
                    
                    $fields[] = $f;
                }
                
                $firstRow = FALSE;
            } else {
                // Вкарваме данните
                $rec = (object)$defaults;
                
                foreach($fields as $i => $f) {
                    $rec->{$f} = $data[$i];
                }
                
                // Ако нямаме запис с посочените уникални стойности, вкарваме новия
                if($mvc->save($rec, NULL, 'IGNORE')) {
                    
                    $inserted++;
                }
            }
        }
            
        fclose($handle);
        
        return $inserted;
    }
}