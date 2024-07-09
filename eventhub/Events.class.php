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
        plg_Search, plg_Created, plg_Modified, plg_Sorting, eventhub_Wrapper';


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
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'eventhub/tpl/SingleLayoutEvents.shtml';

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Наименование, mandatory');

        $this->FLD('series', 'key(mvc=eventhub_Series,select=title)', 'caption=Поредица, mandatory');

        $this->FLD('categories', 'keylist(mvc=eventhub_Categories, select=title)', 'caption=Категория, mandatory');

        $this->FLD('formId', 'key(mvc=eventhub_Forms, select=title)', 'caption=Формат, mandatory');

        $this->FLD('description', 'richtext', 'caption=Описание');

        $this->FLD('poster', 'fileman_FileType(bucket=pictures)', 'caption=Плакат');

        $this->FLD('startDate', 'date', 'caption=Начало, mandatory');

        $this->FLD('openingTime', 'hour', 'caption=Отваряне, mandatory');

        $this->FLD('startTime', 'hour', 'caption=Започва, mandatory');

        $this->FLD('duration', 'time', 'caption=Продължителност');

        $this->FLD('place', 'key(mvc=eventhub_Venues,select=title)', 'caption=Място, mandatory');

        $this->FLD('participants', 'keylist(mvc=crm_Persons,select=name, allowEmpty)', 'caption=Участват');

        $this->FLD('organizers', 'keylist(mvc=crm_Persons,select=name, allowEmpty)', 'caption=Организатор, mandatory');

        $this->FLD('tickets', 'type_Urls', 'caption=Билети');

        $this->FLD('magnitude', 'enum(1=локално, 2=регионално, 3=национално, 4=международно)', 'caption=Магнитуд');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!empty($rec->poster)) {
            $thumb = new thumb_Img(array($rec->poster, 300, 300, 'fileman', 'isAbsolute' => true));
            $row->poster = $thumb->createImg();
        } else {

            $row->poster = 'Няма налични постери';
        }
    }

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {

        /**
         * проверява дали 'startdate' е записана и не е в миналото
         */
        if ($form->isSubmitted()) {
            if ($form->rec->startDate) {
                if ($form->rec->startDate < dt::today()) {
                    $form->setError('startDate', 'Невалидна начална дата: Събитието не може да започва в миналото!');
                }
            }


            /**
             * проверява дали обявеното време, в което отваря, не е преди часът за започване на събитието
             */
            if ($form->rec->openingTime && $form->rec->startTime) {
                $openingTime = strtotime($form->rec->openingTime);
                $startTime = strtotime($form->rec->startTime);

                if ($openingTime > $startTime) {
                    $form->setError('openingTime, startTime', 'Часът на отваряне трябва да бъде преди часа на започване!');
                }
            }

            /**
             * Продължителността не е задължително поле за попълване, но ако бъде попълнено не може да е <= 0
             */
            if($form->rec->duration<0){
                $form->setError('duration', 'Продъжлителността не може да бъде негативно число!');
            }
        }
    }
}