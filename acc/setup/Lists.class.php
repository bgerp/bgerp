<?php

/**
 * Клас 'acc_setup_Lists'
 *
 * @category   Experta Framework
 * @package    acc
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_setup_Lists
{
    function loadData()
    {
        $csvFile = __DIR__ . "/csv/Lists.csv";
        
        $created = $updated = 0;

        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->num            = $csvRow [0];
                $rec->name           = $csvRow [1];
                $rec->regInterfaceId = $csvRow [2];
                $rec->dimensional    = $csvRow [3];
                $rec->itemsCnt       = $csvRow [4];
                $rec->itemMaxNum     = $csvRow [5];
                $rec->state          = $csvRow [6];
                
                // Ако има запис с този 'num'
                $rec->id = acc_Lists::fetchField(array("#num = '[#1#]'", $rec->num), 'id');
                
                if(!$rec->id)  {
                    $rec->id = acc_Lists::fetchField(array("#name = '[#1#]'", $rec->name), 'id');
                }

                if($rec->id) {
                    $updated++;
                } else {
                    $created++;
                }

                acc_Lists::save($rec);                
            }
            
            fclose($handle);

            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";

            $res .= "Създадени {$created} нови номенклатури, обновени {$updated} съществуващи номенклатури.</li>";
        } else {

            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }

        return $res;
    }

}