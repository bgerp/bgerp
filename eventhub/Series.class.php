<?php


/**
 * Поредица от събития
 *
 *
 * @category  bgerp
 * @package   eventhub
 *
 * @author    Ивета Мошева <ivetamosheva@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eventhub_Series extends core_Manager
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Поредица от събития';


    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, plg_State2, plg_Printing, 
        plg_Search, plg_Created, plg_Modified, plg_Sorting,eventhub_Wrapper,plg_SaveAndNew';


    /**
     * Полета за листов изглед
     */
    public $listFields = 'id, title, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, 
    modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By,state';


    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'title';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin';


    /**
     * Кой може да добявя,редактира или изтрива статия
     */
    public $canWrite = 'ceo, admin';


    /**
     * Единично заглавие на документа
     */
    public $singleTitle = 'Поредица от събития';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(190)', 'caption=Наименование, mandatory');
        $this->setDbUnique('title');
    }
}