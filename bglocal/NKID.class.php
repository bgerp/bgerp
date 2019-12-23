<?php 

/**
 * НКИД-Национална класификация на икономическите дейности
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_NKID extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Национална класификация на икономическите дейности';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'НКИД';
    
    
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
        $this->FLD('key', 'varchar', 'caption=Код, mandatory');
        $this->FLD('title', 'text', 'caption=Наименование');
        
        $this->setDbUnique('key');
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
        if (isset($rec->title)) {
            $rec->title = $rec->key . ' ' . $rec->title;
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'bglocal/data/nkid.csv';
        $fields = array(0 => 'key', 1 => 'title');
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
}
