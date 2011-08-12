<?php

/**
 * Клас 'acc_setup_Accounts'
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_setup_Accounts extends core_Mvc
{
    function act_Default()
    {
        $Accounts = cls::get('acc_Accounts');
        
        if (($handle = fopen(__DIR__ . "/csv/Accounts.csv", "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->num      = $csvRow[0];
                $rec->title    = $csvRow[1];
                $rec->type     = $csvRow[2];
                $rec->strategy = $csvRow[3];
                $rec->groupId1 = $this->getListsId($csvRow[4]);
                $rec->groupId2 = $this->getListsId($csvRow[5]);
                $rec->groupId3 = $this->getListsId($csvRow[6]);
                
                // Ако има запис с този 'num'
                $rec->id = $Accounts->fetchField(array("#num = '[#1#]'", $rec->num), 'id'); /* escape data! */
                        
                $Accounts->save($rec);                
            }
            
            fclose($handle);
        }
        
        return new Redirect(array('acc_Accounts'));
    }
    
    
    /* Връща 'id' от acc_Lists по подаден стринг, от който се взема 'num'
     * 
     * @param string $string
     * @return int $idLists
     */
    function getListsId($string)
    {
    	/* parse $string and get 'num' field for Lists */
    	$string = strip_tags($string);
    	$string = trim($string);
    	
    	$startPos = strpos($string, '(');
        $endPos   = strpos($string, ')');
        
        if ($startPos && $endPos && ($endPos > $startPos)) {
            $num = substr($string, $startPos + 1, $endPos - $startPos - 1);
            $num = str_replace(' ', '', $num);
            $num = (int) $num;
        } else {
            return NULL;
        }
        /* END parse $string and get 'num' field for Lists */
        
        /* Find for this $num the 'id' in acc_Lists */
        $Lists = cls::get('acc_Lists');
        
        if ($idLists = $Lists->fetchField("num={$num}", 'id')) {
            return $idLists; 
        } else {
            // error
            bp('В Acc.csv има номер на номенклатура, която не е открита в acc_Lists');
        }
        /* END Find for this $num the 'id' in acc_Lists */
    }

}