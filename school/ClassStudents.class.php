<?php


/**
 * class school_ClassStudents
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
class school_ClassStudents extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Обучаеми студенти/ученици';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Обучаеми';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'studentId,saleAbbr,note';
    
    
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
        $this->FLD('classId', 'key(mvc=school_Classes,select=productId)', 'caption=Клас');
        $this->FLD('studentId', 'key(mvc=crm_Persons,select=name)', 'caption=Обучаем');
        $this->FLD('saleAbbr', 'varchar(16)', 'caption=Продажба,smartCenter');
        $this->FLD('note', 'text', 'caption=Забележка');

    }
    
}
