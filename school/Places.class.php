<?php


/**
 * class school_ReqDocuments
 *
 * Изискуеми документи
 *
 *
 * @category  bgerp
 * @package   edu
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class school_Places extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Зали';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Зала';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting, plg_Printing,plg_State2,plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,address,photo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, edu';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, edu';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, edu';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, edu';
    
    
    /**
     * Шаблон за единичния изглед
     */
    // public $singleLayoutFile = '';
    

    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'name';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Наименование,mandatory');
        $this->FLD('address', 'text', 'caption=Адрес,smartCenter');
        $this->FLD('gpsCoords', 'location_Type(geolocation=mobile)', 'caption=Координати');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Изглед,smartCenter');
    }
    
}
