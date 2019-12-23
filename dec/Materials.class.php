<?php 

/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class dec_Materials extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Материали';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Материал';
    
    
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
        $this->FLD('title', 'varchar', 'caption=Заглавие, width=100%');
        $this->FLD('text', 'richtext(bucket=Notes)', 'caption=Текст');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        // Подготвяме пътя до файла с данните
        $file = 'dec/data/Materials.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'title',
            1 => 'text',
        
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields, null, null, true);
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $mvc->currentTab = 'Декларации->Материали';
        $mvc->menuPage = 'Търговия:Продажби';
    }
}
