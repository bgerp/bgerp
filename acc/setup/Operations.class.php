<?php



/**
 * Клас 'acc_setup_Operations'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_setup_Operations
{
    
    
    /**
     * Зарежда първоначалните данни за сч. Операциите от csv файл
     */
    static function loadData()
    {
        $csvFile = __DIR__ . "/csv/Operations.csv";
        
        $created = $updated = 0;
        
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                $rec = new stdClass();
                $rec->name = $csvRow [0];
               	$classId = core_Classes::fetchIdByName($csvRow [1]);
               	$rec->documentSrc = $classId;
                $rec->debitAccount = $csvRow [2];
                $rec->creditAccount = $csvRow [3];
                $rec->systemId = $csvRow [4];
                
             	if ($rec->id = acc_Operations::fetchField(array("#systemId = '[#1#]'", $rec->systemId), 'id')) {
                    $updated++;
                } else {
                    $created++;
                }
                
                acc_Operations::save($rec);
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            
            $res .= "Създадени {$created} нови операции, обновени {$updated} съществуващи операции.</li>";
        } else {
            
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}