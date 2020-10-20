<?php


/**
 * class school_Classes
 *
 * Класове за обучение
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
class school_Classes extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Групи за обучение';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Група';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting, plg_Printing, plg_State, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,courseId,start,end,state';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, edu';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    /**
     * Кой може да го оттегли?
     */
    public $canReject = 'no_one';
  

    /**
     * Шаблон за единичния изглед
     */
    // public $singleLayoutFile = '';
    
    /**
     * Детайли за зареждане
     */
    public $details = 'school_ClassStudents,school_ClassSchedules';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'productId';
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Група');
        $this->FLD('courseId', 'key(mvc=school_Courses,select=title)', 'caption=Курс||Course');
        $this->FLD('start', 'combodate', 'caption=Начало,autohide');
        $this->FLD('end', 'combodate', 'caption=Край,autohide');
    }
    
}
