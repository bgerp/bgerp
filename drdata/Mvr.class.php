<?php



/**
 * Клас 'drdata_Mvr'
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Mvr extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, drdata_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, city, account, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Заглавие
     */
    var $title = 'МВР по страната';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, common';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, common';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, common';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('city',    'varchar', 'caption=Град, mandatory, export');
        $this->FLD('account', 'varchar', 'caption=Сметка, input=none, export');
        $this->setDbUnique('city');
    }
    
    
    /**
     * Сортиране по city
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#city');
    }
    
    
    /**
     * След подготовката на модела, инициализира началните данни
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $filePath = __DIR__ . "/data/Mvr.csv";
        
        // Нулираме броячите
        $updCnt = $newCnt = 0;
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                $rec = new stdClass();
                $rec->city    = $csvRow[0];
                $rec->account = $csvRow[1];
                
                // Ако има запис с това 'city'
                $rec->id = $mvc->fetchField(array("#city = '[#1#]'", $rec->city), 'id');
                
                if($rec->id) {
                    $updCnt++;
                } else {
                    $newCnt++;
                }
                
                $mvc->save($rec);
            }
            
            fclose($handle);
            
            $res .= "<li> Добавени данни за МВР - {$newCnt} нови, {$updCnt} съществуващи.</li>";
        } else {
            $res .= "<li> Не може да бъде прочетен файла {$filePath}</li>";
        }
    }
}