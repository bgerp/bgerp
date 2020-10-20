<?php


/**
 * class school_Courses
 *
 * Програми за обучение
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
class school_Courses extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Учебни курсове||Education courses';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Курс||Course';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting, plg_Printing, plg_Rejected, plg_State2,plg_GroupByField, plg_SaveAndNew';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,title,specialityId,stageId';
    
    /**
     * Детайли за зареждане
     */
    public $details = 'school_CourseDetails';

    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, edu, teacher';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, edu, teacher';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, edu, teacher';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, edu';
    
    
    /**
     * Шаблон за единичния изглед
     */
    // public $singleLayoutFile = '';


    /**
     * Хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'title';
    

    /**
     * Поле за групиране
     */
    public $groupByField = 'specialityId';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FNC('code', 'varchar', 'caption=Код,smartCenter');
        $this->FNC('title', 'varchar', 'caption=Заглавие');
        $this->FLD('specialityId', 'key(mvc=school_Specialties,select=name,allowEmpty)', 'caption=Специалност,mandatory');
        $this->FLD('stageId', 'key(mvc=school_Stages,select=name,allowEmpty)', 'caption=Етап');

        $this->EXT('specialityCode', 'school_Specialties', array('onCond' => "#school_Specialties.id = #school_Courses.specialityId", 'join' => 'RIGHT', 'externalName' => 'code', 'single' => 'none'));
        $this->EXT('stageCode', 'school_Stages', array('onCond' => "#school_Stages.id = #school_Courses.stageId", 'join' => 'RIGHT', 'externalName' => 'code', 'single' => 'none'));

        $this->FLD('description', 'richtext', 'caption=Описание,column=none');
        $this->FLD('reqDocuments', 'keylist(mvc=school_ReqDocuments,select=name,select2MinItems=20)', 'caption=Изискуеми документи->Документи,column=none');

        $this->setDbUnique('specialityId,stageId');
    }


    /**
     * Изчисляване на `title`
     */
    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = $mvc->getVerbal($rec, 'specialityId') . ($rec->stageId ? ', ' . $mvc->getVerbal($rec, 'stageId') : '');
    }


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#specialityCode,#stageCode');
    }


    /**
     * Изчисляване на `title`
     */
    public function on_CalcCode($mvc, $rec)
    {
        $rec->code = $mvc->getVerbal($rec, 'specialityCode') . ($rec->stageId ? '-' . $mvc->getVerbal($rec, 'stageCode') : '') ;
    }
    
}
