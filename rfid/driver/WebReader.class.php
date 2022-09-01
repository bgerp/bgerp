<?php


/**
 * Интерфейс за WEB RFID рийдър
 *
 *
 * @category  bgerp
 * @package   rfid
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     WEB RFID четец 
 */
class rfid_driver_WebReader extends core_Mvc
{
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    public $interfaces = 'rfid_ReaderIntf';
    
    
    /**
     * Наименование на фигурата
     */
    public $title = 'Получава данни от четец по ВЕБ';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        //$form->FLD('readerId', 'key(mvc=rfid_Readers, select=name, allowEmpty)', 'caption=Четец,mandatory,silent,after=tag');
    }

    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal($Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $row->driverClass = rfid_Readers::getLinkToSingle($rec->id, 'title'); //bp($Driver);
    }
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        
        return true;
    }

 
    
    public static function on_BeforeAction($Driver, embed_Manager $Embedder, &$res, $action)
    {
        if($action == 'addevent') {
        }
    }
}
