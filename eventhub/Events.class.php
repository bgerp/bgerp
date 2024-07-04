<?php


/**
 * Категории на събития
 *
 *
 * @category  bgerp
 * @package   eventhub
 *
 * @author    Svetlozar Trifonov <svetlozartrifonov60@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eventhub_Events extends core_Manager
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Събития';


    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, plg_State2, plg_Printing, 
        plg_Search, plg_Created, plg_Modified, plg_Sorting';


    /**
     * Полета за листов изглед
     */
    public $listFields = 'id, title, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By,state';


    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'title, parentId';


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
    public $singleTitle = 'Събития';

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('series', 'key(mvc=eventhub_Series)', 'caption=Серии, mandatory');
        $this->FLD('categoryId', 'keylist(mvc=eventhub_Categories)', 'caption=Подкатегория, mandatory');
        $this->setDbUnique('serii');
        $this->setDbUnique('categoryId');

        $this->FLD('formId', 'key(mvc=eventhub_Formats)', 'caption=Формат, mandatory');
        $this->setDbUnique('formId');

        $this->FLD('description', 'richtext', 'caption=Описание на събитието');
        $this->setDbUnique('desctiption');

        $this->FLD('poster', 'fileman_FyleType(bucket=eshopImages', 'caption=Плакат');
        $this->setDbUnique('poster');

        $this->FLD('startDate', 'date', 'caption=Дата на събитието, mandatory');
        $this->setDbUnique('startDate');

        $this->FLD('openingTime', 'time', 'caption=час на отваряне, mandatory');
        $this->setDbUnique('openingTime');

        $this->FLD('startTime', 'time', 'caption=час на започване, mandatory');
        $this->setDbUnique('startTime');

        $this->FLD('duration', 'time', 'caption=очаквана продължителност');
        $this->setDbUnique('duration');

        $this->FLD('place', 'key(mvc=eventhub_Venues)', 'caption=връзка към модела за места, mandatory');
        $this->setDbUnique('place');

        $this->FLD('participants', 'keylist(mvc=crm_Persons,select=name,allowEmpty)', 'caption=връзка към Persons');
        $this->setDbUnique('participants');

        $this->FLD('organizer', 'keylist(mvc=crm_Persons,select=name,allowEmpty)', 'caption=връзка към организатора на събития, mandatory');
        $this->setDbUnique('organizer');

        $this->FLD('tickets', 'type_Urls', 'caption=URL адреси за закупуване на билети, mandatory');
        $this->setDbUnique('tickets');

        $this->FLD('magnitude', 'enum(1=локално, 2=регионално, 3=национално, 4=международно)', 'caption=значимостта на събитията');
        $this->setDbUnique('magnitude');
    }
}