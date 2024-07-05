<?php


/**
 * Събития
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
class eventhub_Events extends core_Master
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Събития';


    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, plg_State2, plg_Printing, 
        plg_Search, plg_Created, plg_Modified, plg_Sorting,eventhub_Wrapper';


    /**
     * Полета за листов изглед
     */
    public $listFields = 'id, title, format, description, poster, startDate, startTime, duration, place, participants, organizer, tickets, magnitude, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By,state';


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
    public $singleTitle = 'Събития';

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('series', 'key(mvc=eventhub_Series,select=title)', 'caption=Серии, mandatory');
        $this->FLD('categories', 'keylist(mvc=eventhub_Categories, select=title)', 'caption=Категории, mandatory');

        $this->FLD('formId', 'key(mvc=eventhub_Forms,select=title)', 'caption=Формат, mandatory');

        $this->FLD('description', 'richtext', 'caption=Описание на събитието');

        $this->FLD('poster', 'fileman_FileType(bucket=pictures)', 'caption=Плакат');

        $this->FLD('startDate', 'date', 'caption=Дата на събитието, mandatory');

        $this->FLD('openingTime', 'time', 'caption=час на отваряне, mandatory');

        $this->FLD('startTime', 'time', 'caption=час на започване, mandatory');

        $this->FLD('duration', 'time', 'caption=очаквана продължителност');

        $this->FLD('place', 'key(mvc=eventhub_Venues,select=title)', 'caption=връзка към модела за места, mandatory');

        $this->FLD('participants', 'keylist(mvc=crm_Persons,select=name)', 'caption=връзка към Persons');

        $this->FLD('organizers', 'keylist(mvc=crm_Persons,select=name)', 'caption=връзка към организатора на събития, mandatory');

        $this->FLD('tickets', 'type_Urls', 'caption=URL адреси за закупуване на билети, mandatory');

        $this->FLD('magnitude', 'enum(1=локално, 2=регионално, 3=национално, 4=международно)', 'caption=значимостта на събитията');
    }
}