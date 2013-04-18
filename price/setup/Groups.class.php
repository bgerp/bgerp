<?php



/**
 * Клас 'price_setup_Groups'
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class price_setup_Groups extends core_Mvc
{
    
    
    /**
     * @todo Чака за документация...
     */
    static function setup()
    {
        $csvFile = __DIR__ . "/csv/Groups.csv";
    	
        $Groups = cls::get('price_Groups');
        
        $created = $updated = 0;
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 2000, ",")) !== FALSE) {
                $rec = new stdClass();
                $rec->title = $csvRow[0];
                $rec->description = $csvRow[1];
                $rec->createdBy = -1;     // Записите направени от системния потребител (-1) не могат да се редактират
                
                // Ако има запис с този 'name'
                if($rec->id = $Groups->fetchField(array("#title = '[#1#]'", $rec->title), 'id')){
                 	$updated++;
                } else {
                    $created++;
                }
               
                $Groups->save($rec, NULL, 'IGNORE');
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови ценови групи, обновени {$updated} съществуващи ценови групи.</li>";
        } else {
        	$res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}
