<?php


/**
 * class school_CourseDetails
 *
 * Програми за обучение - дисциплини
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
class school_CourseDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Курс за обучение - дисциплини';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Дисциплина';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'subjectId';
    
    
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
    public $masterKey = 'courseId';
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('courseId', 'key(mvc=school_Courses)', 'caption=Курс');
        $this->FLD('subjectId', 'key(mvc=school_Subjects,select=title)', 'caption=Дисциплини');
       
      
        $this->setDbUnique('courseId,subjectId');
    }

 
}
