<?php

/**
 * Клас 'acc_setup_Lists'
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_setup_Lists extends core_Mvc
{
    function act_Default()
    {
        $Lists = cls::get('acc_Lists');
        
        if (($handle = fopen(__DIR__ . "/csv/Lists.csv", "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->num            = $csvRow [0];
                $rec->name           = $csvRow [1];
                $rec->regInterfaceId = $csvRow [2];
                $rec->dimensional    = $csvRow [3];
                $rec->itemsCnt       = $csvRow [4];
                $rec->itemMaxNum     = $csvRow [5];
                $rec->state          = $csvRow [6];
                
                // Ако има запис с този 'num'
                $rec->id = $Lists->fetchField(array("#num = '[#1#]'", $rec->num), 'id'); /* escape data! */
                        
                $Lists->save($rec);                
            }
            
            fclose($handle);
        }
        
        return new Redirect(array('acc_Lists'));
    }

}