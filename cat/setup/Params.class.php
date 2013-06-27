<?php



/**
 * Клас 'cat_setup_Params'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_setup_Params extends core_Mvc
{
    
    
    /**
     * @todo Чака за документация...
     */
    static function setup()
    {
        $csvFile = __DIR__ . "/csv/Params.csv";
    	
        $Params = cls::get('cat_Params');
        
        $created = $updated = 0;
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 2000, ",")) !== FALSE) {
                $rec = new stdClass();
                $rec->name = $csvRow[0];
                $rec->type = $csvRow[1];
                $rec->suffix = $csvRow[2];
                $rec->sysId = $csvRow[3];
                $rec->createdBy = -1;     // Записите направени от системния потребител (-1) не могат да се редактират
                
                // Ако има запис с този 'name'
                if($rec->id = $Params->fetchField("#name = '{$rec->name}' AND #suffix = '{$rec->suffix}'", 'id')){
                 	$updated++;
                } else {
                    $created++;
                }
               
                $Params->save($rec, NULL, 'REPLACE');
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови параметри, обновени {$updated} съществуващи параметри.</li>";
        } else {
        	$res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}