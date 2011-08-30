<?php

/**
 * Клас 'acc_setup_Accounts'
 *
 * @category   Experta Framework
 * @package    acc
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_setup_Accounts
{
    function loadData()
    {
        $csvFile = __DIR__ . "/csv/Accounts.csv";
        
        $created = $updated = 0;

        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->num      = $csvRow[0];
                $rec->title    = $csvRow[1];
                $rec->type     = $csvRow[2];
                $rec->strategy = $csvRow[3];
                $rec->groupId1 = acc_Lists::fetchField(array("#systemId = '[#1#]'", $csvRow[4]), 'id');
                $rec->groupId2 = acc_Lists::fetchField(array("#systemId = '[#1#]'", $csvRow[5]), 'id');
                $rec->groupId3 = acc_Lists::fetchField(array("#systemId = '[#1#]'", $csvRow[6]), 'id');
                $rec->systemId = $csvRow[7];
                $rec->createdBy = -1;
                
                // Ако има запис с този 'num'
                if ($rec->id = acc_Accounts::fetchField(array("#systemId = '[#1#]'", $rec->systemId), 'id')) {
                    $updated++;    
                } elseif ($rec->id = acc_Accounts::fetchField(array("#num = '[#1#]'", $rec->num), 'id')) {
                    $updated++;
                } else {    
                	$created++;
                }
                        
            	acc_Accounts::save($rec);                
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови сметки, обновени {$updated} съществуващи сметки.</li>";

        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }

        
        return $res;
    }
}