<?php 

/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class dec_Statements extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Твърдения';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Твърдение';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Декларации';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_Created, plg_RowTools2, plg_State2, plg_Printing, plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, dec';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, dec';
    
    
    /**
     * Кои полета ще виждаме в листовия изглед
     */
    public $listFields = 'id, title, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(64)', 'caption=Заглавие, width=100%');
        $this->FLD('shortText', 'text(rows=2)', 'caption=Кратък текст');
        $this->FLD('text', 'richtext(bucket=Notes)', 'caption=Текст');
        cls::get('core_Lg');
        
        $this->FLD('lg', 'enum(' . EF_LANGUAGES . ')', 'caption=Език,changable');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        // Подготвяме пътя до файла с данните
        $file = 'dec/data/Statements.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'title',
            1 => 'text',
            2 => 'lg',
            3 => 'shortText',
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields, null, null);
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }
    
    
    /**
     * Краткият текст под заглавието в списъка
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list']) && !empty($rec->shortText)) {
            $row->title .= "<div class='small quiet'>" . $mvc->getVerbal($rec, 'shortText') . '</div>';
        }
    }


    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $mvc->currentTab = 'Декларации->Твърдения';
        $mvc->menuPage = 'Търговия:Продажби';
    }
}
