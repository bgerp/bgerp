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
 * @title     Драйвер на RFID четец
 */
class rfid_driver_WebReader extends embed_DriverIntf
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
        // $form->FLD('holderId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'caption=Лице,mandatory,silent,after=tag');
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
        // $row->driverClass = crm_Persons::getLinkToSingle($rec->holderId, 'name');
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
        
        return false;
    }

    /**
     * Чете данни от WEB
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function getData()
    {
        //         if (!isset($userId)) {
        //             $userId = core_Users::getCurrent();
        //         }
        
        return false;
    }
    
}
