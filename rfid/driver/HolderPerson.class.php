<?php


/**
 * Интерфейс за IP RFID рийдър
 *
 *
 * @category  bgerp
 * @package   rfid
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Драйвер на RFID четец
 */
class rfid_driver_HolderPerson extends core_BaseClass
{
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    public $interfaces = 'rfid_HolderIntf';
    
    
    /**
     * Наименование на фигурата
     */
    public $title = 'Лица картодържатели';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('holderId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'caption=Лице,mandatory,silent,after=tag');
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
        $row->driverClass = crm_Persons::getLinkToSingle($rec->holderId, 'name');
    }

}
