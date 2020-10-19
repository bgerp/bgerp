<?php


/**
 * class school_ClassSchedules
 *
 * Разписание на обученията по класове
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
class school_ClassSchedules extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Разписание на обученията по класове';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Занятие';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'subjectId,teacher,place,activity,start,hours,end';
    
    
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
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'classId';
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('classId', 'key(mvc=school_Classes,select=productId)', 'caption=Клас,silent');
        $this->FLD('subjectId', 'key(mvc=school_Subjects,select=title,allowEmpty)', 'caption=Дисциплина,mandatory,refreshForm,silent');
        $this->FLD('activity', 'key(mvc=school_SubjectDetails,select=title)', 'caption=Съдържание,smartCenter,input=hidden');
        $this->FLD('teacher', 'user(roles=teacher,rolesForAll=officer)', 'caption=Преподавател,mandatory,input=hidden');
        $this->FLD('place', 'key(mvc=school_Venues,select=name)', 'caption=Място,mandatory,smartCenter');

        $this->FLD('start', 'datetime', 'caption=Начало,mandatory');
        $this->FLD('hours', 'float(smartRound,desimals=2)', 'caption=Часове');
        $this->FLD('end', 'datetime', 'caption=Край');

        $this->setDbUnique('start');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec  = $form->rec;
        $form->input(null, 'silent');
 
        if($rec->subjectId) {
            // Добавя опции за активност
            $opt = school_SubjectDetails::makeArray4Select('title', "#subjectId = {$rec->subjectId}");
            if(count($opt)) {
                $form->setOptions('activity', $opt);
                $form->setField('activity', 'input');
                
            }

            $tList = school_Subjects::fetch($rec->subjectId)->teachers;
            if($tList) {
                $tList = trim(str_replace('|', ',', $tList), ',');
                $opt = core_Users::makeArray4Select('names', "#id IN ({$tList})");
                $form->setOptions('teacher', $opt);
                $form->setField('teacher', 'input');
            }
        }
    }
}
