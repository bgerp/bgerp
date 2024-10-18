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
    public $listFields = 'id, title, formId, categories, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By,state';


    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'title,place,formId,categories,description';


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
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Наименование, mandatory');
        $this->FLD('series', 'key(mvc=eventhub_Series,select=title)', 'caption=Поредица, mandatory');
        $this->FLD('categories', 'keylist(mvc=eventhub_Categories, select=title)', 'caption=Категория, mandatory');
        $this->FLD('formId', 'key(mvc=eventhub_Forms, select=title, allowEmpty)', 'caption=Формат, mandatory');
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
        $this->FLD('magnitude', 'enum(1=локално, 2=регионално, 3=национално, 4=международно)', 'caption=Магнитуд, mamdatory');
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
            $size = isset($fields['-list']) ? 100 : 300;
            $thumb = new thumb_Img(array($rec->poster, $size, $size, 'fileman', 'isAbsolute' => true));
            $row->poster = $thumb->createImg();
        } else {
            $row->poster = 'Няма налични постери';
        }
        if (!empty($rec->tickets)) {
            $row->tickets = ht::createBtn('билети', $rec->tickets);
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if ($form->rec->startDate) {
                if ($form->rec->startDate < dt::today()) {
                    $form->setError('startDate', 'Невалидна начална дата: Събитието не може да започва в миналото!');
                }
            }

            if ($form->rec->openingTime && $form->rec->startTime) {
                $openingTime = strtotime($form->rec->openingTime);
                $startTime = strtotime($form->rec->startTime);

                if ($openingTime > $startTime) {
                    $form->setError('openingTime, startTime', 'Часът на отваряне трябва да бъде преди часа на започване!');
                }
            }

            if ($form->rec->duration < 0) {
                $form->setError('duration', 'Продължителността не може да бъде негативно число!');
            }
        }
    }


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        $form->view = 'horizontal';
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->showFields = 'search,startDate,formId';

        $form->input();

        if (isset($form->rec)) {
            if ($form->rec->formId) {
                $data->query->where("#formId = {$form->rec->formId}");
            }
            if ($form->rec->startDate) {
                $data->query->where("#startDate >= '{$form->rec->startDate}'");
            }
        }
    }

}
