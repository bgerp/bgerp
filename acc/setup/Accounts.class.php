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
                $rec->groupId1 = self::getListsId($csvRow[4]);
                $rec->groupId2 = self::getListsId($csvRow[5]);
                $rec->groupId3 = self::getListsId($csvRow[6]);
                $rec->systemId = self::getListsId($csvRow[7]);
                $rec->createdBy = -1;

                // Ако има запис с този 'num'
                if ($rec->id = acc_Accounts::fetchField(array("#num = '[#1#]'", $rec->num), 'id')) {
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
        
        if ($idLists = $Lists->fetchField("#num={$num}", 'id')) {
            return $idLists; 
        } else {
            // error
            bp('В Acc.csv има номер на номенклатура, която не е открита в acc_Lists', $num);
        }
        /* END Find for this $num the 'id' in acc_Lists */
    }

}