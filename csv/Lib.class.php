<?php



/**
 * Клас 'csv_Lib' -
 *
 *
 * @category  all
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
     * Прочита от CSV файл данни за инициализация и ги записва в модела
     *
     * @param core_Mvc $mvc
     * @param string $csvFile
     * @return int $nAffected
     */
    function loadDataFromCsv($mvc, $csvFile)
    {
        // Почитаме CSV файла
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($rowCsv = fgetcsv($handle, 0, ",")) !== FALSE) {
                $arrCsv[] = $rowCsv;
            }
        }
        
        $nAffected = 0;
        $isFirstRow = TRUE;
        
        // Записваме всеки ред от CSV файла (след 1-вия с имената на полетата) в базата
        foreach($arrCsv as $rowCsv) {
            if($isFirstRow) {
                $row0 = $rowCsv;
            } else {
                $rec = NULL;
                
                // За всяко поле задаваме стойност за реда
                foreach($row0 as $id => $fieldName) {
                    if(!$rowCsv[$id]) continue;
                    $rec->{$fieldName} = $rowCsv[$id];
                }
                
                // Записваме подготвения ред
                $mvc->save($rec);
                $nAffected++;
                
                // if($nAffected > 1000) break;
            }
            
            $isFirstRow = FALSE;
        }
        
        return $nAffected;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function import($mvc, $path, $fields = array(), $unique = array(), $defaults = array())
    {
        $firstRow = TRUE; $inserted = 0;
        $fields = arr::make($fields);
        $unique = arr::make($unique);
        
        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                if(!count($data)) continue;
                
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
        }
        
        return $inserted;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function explode($file, $newLine = "\n", $delim = ',', $qual = "\"", $comment = 'none')
    {
        $lines = explode($newLine, $file);
        
        foreach ($lines as $i => $l) {
            if (mb_substr($l, 0, 1) != $comment) {
                $arr[$i] = csv_Lib::rowExplode($l, $delim, $qual);
            }
        }
        
        return $arr;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function rowExplode($str, $delim = ',', $qual = "\"")
    {
        $len = mb_strlen($str);
        $inside = FALSE;
        $word = '';
        
        for ($i = 0; $i < $len; ++$i) {
            if (mb_substr($str, $i, 1) == $delim && !$inside) {
                $out[] = $word;
                $word = '';
            } elseif ($inside && mb_substr($str, $i, 1) == $qual && ($i < $len && mb_substr($str, $i + 1, 1) == $qual)) {
                $word .= $qual;
                ++$i;
            } elseif (mb_substr($str, $i, 1) == $qual) {
                $inside = !$inside;
            } else {
                $word .= mb_substr($str, $i, 1);
            }
        }
        
        $out[] = $word;
        
        return $out;
    }
}