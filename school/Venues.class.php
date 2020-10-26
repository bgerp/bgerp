<?php


/**
 * class school_Venues
 *
 * Места за провеждане на обучения
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
class school_Venues extends core_Master
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
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting, plg_Printing,plg_State2,plg_Rejected, plg_SaveAndNew';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,isFor,capacity';
    
    
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
        $this->FLD('type', 'enum(real=Физическа,virtual=Вируална)', 'caption=Тип,refreshForm,silent');
        $this->FLD('name', 'varchar(128)', 'caption=Наименование,mandatory,smartCenter');
        $this->FLD('isFor', 'keylist(mvc=school_Formats,select=name,select2MinItems=20)', 'caption=Използване за,smartCenter');
        $this->FLD('capacity', 'int(min=1)', 'caption=Капацитет,smartCenter,unit=души');
        $this->FLD('place', 'varchar(64)', 'caption=Място,smartCenter');
        $this->FLD('url', 'varchar(128)', 'caption=URL,smartCenter,input=none');
        $this->FLD('address', 'varchar(128)', 'caption=Адрес,smartCenter');
        $this->FLD('gpsCoords', 'location_Type(geolocation=mobile)', 'caption=Координати');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Изглед,smartCenter');

        $this->setDbUnique('name');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $form->input(null, 'silent');
        $rec  = $form->rec;
 
        if($rec->type == 'virtual') {
            $form->setField('place,address,gpsCoords,photo', 'input=none');
            $form->setField('url', 'input');
        }
    }
    
}
