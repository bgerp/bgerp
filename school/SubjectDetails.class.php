<?php


/**
 * class school_SubjectDetails
 *
 * Детайли на учебната програма
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
class school_SubjectDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на учебната програма';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Учебен сеанс';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'format,theme,hours';
    
    
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
    public $masterKey = 'subjectId';
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FNC('title', 'varchar', 'caption=Заглавие');
        $this->FLD('subjectId', 'key(mvc=school_Subjects,select=title)', 'caption=Дисциплина');
        $this->FLD('format', 'key(mvc=school_Formats,select=name)', 'caption=Форма,smartCenter');
        $this->FLD('theme', 'varchar(256)', 'caption=Тема,mandatory');
        $this->FLD('hours', 'int', 'caption=Часове');
      
        $this->setDbUnique('subjectId,theme');
    }


    /**
     * Заглавие на активността
     */
    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = "{$rec->format} \"{$rec->theme}\"";
    }

 
}
