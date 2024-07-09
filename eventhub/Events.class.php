<?php

class eventhub_Events extends core_Master
{
    public $title = 'Събития';

    public $loadList = 'plg_RowTools2, plg_State2, plg_Printing, 
        plg_Search, plg_Created, plg_Modified, plg_Sorting, eventhub_Wrapper';

    public $listFields = 'id, title, formId, description, poster, startDate, startTime, duration, place, participants, organizers, tickets, magnitude, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By,state';

    public $searchFields = 'title';

    public $canList = 'ceo, admin';

    public $canSingle = 'ceo, admin';

    public $canWrite = 'ceo, admin';

    public $singleTitle = 'Събития';

    public $singleLayoutFile = 'eventhub/tpl/SingleLayoutEvents.shtml';

    public $rowToolsSingleField = 'title';

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

    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!empty($rec->poster)) {
            $size = isset($fields['-list']) ? 50 : 300;
            $thumb = new thumb_Img(array($rec->poster, $size, $size, 'fileman', 'isAbsolute' => true));
            $row->poster = $thumb->createImg();
        } else {
            $row->poster = 'Няма налични постери';
        }
    }

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

}
