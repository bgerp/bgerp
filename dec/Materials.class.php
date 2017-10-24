<?php 


/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_Materials extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Материали";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Материал";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Декларации";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'sales_Wrapper, plg_Created, plg_RowTools2, plg_State2, plg_Printing, plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, dec';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo, dec';
    
    
    /**
     * Кои полета ще виждаме в листовия изглед
     */
    var $listFields = 'id, title, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar', 'caption=Заглавие, width=100%');
        $this->FLD('text', 'richtext(bucket=Notes)', 'caption=Текст');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        
        // Подготвяме пътя до файла с данните 
        $file = "dec/data/Materials.csv";
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => "title",
            1 => "text",
        
        );
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно 
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL, TRUE);
        
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
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
    	$mvc->currentTab = "Декларации->Материали";
    	$mvc->menuPage = "Търговия:Продажби";
    }
}