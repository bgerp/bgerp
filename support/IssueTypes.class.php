<?php 


/**
 * Модел, който представлява множество от различните типове сигнали.
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_IssueTypes extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'issue_Types';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Типове сигнали';
    
    
    
    public $singleTitle = 'Тип на сигнала';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin, support';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin, support';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, support';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, support';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, support';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'support_Wrapper, plg_RowTools2, plg_State2';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('type', 'varchar', 'caption=Тип, width=100%');
        
        $this->setDbUnique('type');
    }
    
    
    /**
     * Създаваме типовете сиганли
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        $file = 'support/csv/IssueTypes.csv';
        $fields = array(0 => 'type');
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
}
