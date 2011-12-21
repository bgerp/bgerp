<?php

/**
 * Клас 'drdata_Mvr'
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class drdata_Mvr extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, drdata_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, city, account, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'МВР по страната';
    

    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
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