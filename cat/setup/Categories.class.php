<?php



/**
 * Клас 'cat_setup_Categories'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_setup_Categories extends core_Mvc
{
    
    
    /**
     * @todo Чака за документация...
     */
    static function setup()
    {
        $csvFile = __DIR__ . "/csv/Categories.csv";
    	
        $Products = cls::get('cat_Categories');
        
        $created = $updated = 0;
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 2000, ",")) !== FALSE) {
                $rec = new stdClass();
                $rec->name = $csvRow[0];
                $rec->info = $csvRow[1];
                $rec->createdBy = -1;     // Записите направени от системния потребител (-1) не могат да се редактират
                
                // Ако има запис с този 'name'
                if($rec->id = $Products->fetchField(array("#name = '[#1#]'", $rec->name), 'id')){
                 	$updated++;
                } else {
                    $created++;
                }
               
                $Products->save($rec, NULL, 'IGNORE');
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови категории, обновени {$updated} съществуващи категории.</li>";
        } else {
        	$res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}
