<?php



/**
 * Клас 'cat_setup_UoM'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_setup_UoM extends core_Mvc
{
    
    
    /**
     * @todo Чака за документация...
     */
    static function setup()
    {
        $csvFile = __DIR__ . "/csv/UoM.csv";
    	
        $Units = cls::get('cat_UoM');
        
        $created = $updated = 0;
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 2000, ",")) !== FALSE) {
                $rec = new stdClass();
                $rec->name = $csvRow[0];
                $rec->shortName = $csvRow[1];
                $rec->baseUnitId = $Units->fetchField(array("#name = '[#1#]'", $csvRow[2]), 'id');     /* escape data! */
                $rec->baseUnitRatio = $csvRow[3];
                $rec->state = $csvRow[4];
                $rec->createdBy = -1;     // Записите направени от системния потребител (-1) не могат да се редактират
                // Ако има запис с този 'name'
                if($rec->id = $Units->fetchField(array("#name = '[#1#]'", $csvRow[0]), 'id')){
                 	$updated++;
                } else {
                    $created++;
                }

                $Units->save($rec, NULL, 'IGNORE');
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови мерни еденици, обновени {$updated} съществуващи мерни еденици.</li>";
        } else {
        	$res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}
