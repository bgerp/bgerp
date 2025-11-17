<?php 

/**
 * Типове договори
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_HScode extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Комбинирана митническа номенклатура';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'HS';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, bglocal_Wrapper, plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin,hr';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('key', 'varchar', 'caption=Код');
        $this->FLD('title', 'text', 'caption=Наименование');
    }
    
    
    /**
     * Изпълнява се преид импортирването на запис
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {

    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'bglocal/data/HScode.csv';
        $fields = array(0 => 'key', 1 => 'title');
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
    
    
    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        
    }
}
