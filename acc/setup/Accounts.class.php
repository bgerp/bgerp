<?php



/**
 * Клас 'acc_setup_Accounts'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_setup_Accounts
{
    
    
    /**
     * Зареждане на началния сметкоплан в базата данни
     */
    static function loadData()
    {
        $csvFile = self::getCsvFile();
        
        $created = $updated = 0;
        
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                $rec = new stdClass();
                $rec->num = $csvRow[0];
                $rec->title = $csvRow[1];
                $rec->type = $csvRow[2];
                $rec->strategy = $csvRow[3];
                $rec->groupId1 = self::getListsId($csvRow[4]);
                $rec->groupId2 = self::getListsId($csvRow[5]);
                $rec->groupId3 = self::getListsId($csvRow[6]);
                $rec->systemId = $csvRow[7];
                $rec->state = $csvRow[8];
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
    
    
    /**
     * Връща 'id' от acc_Lists по подаден стринг, от който се взема 'num'
     *
     * @param string стринг от вида `име на номенклатура (код)`
     * @return int ид на номенклатура
     */
    static private function getListsId($string)
    {
        $string = strip_tags($string);
        $string = trim($string);
        
        if (empty($string)) {
            // Няма разбивка
            return NULL;
        }
        
        if (!preg_match('/\((\d+)\)\s*$/', $string, $matches)) {
            bp('Некоректно форматирано име на номенклатура, очаква се `Име (код)`', $string);
        }
        
        $num = (int)$matches[1];
        
        if (! ($listId = acc_Lists::fetchField("#num={$num}", 'id'))) {
            // Проблем: парсиран е код, но не е намерена номенклатура с този код
            bp('В ' . self::getCsvFile() . ' има номер на номенклатура, която не е открита в acc_Lists', $num, $string);
        }
        
        return $listId;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static private function getCsvFile()
    {
        return __DIR__ . "/csv/Accounts.csv";
    }
}