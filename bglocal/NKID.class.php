<?php 


/**
 * НКИД-Национална класификация на икономическите дейности
 *
 *
 * @category  bgerp
 * @package   bglocal
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_NKID extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Национална класификация на икономическите дейности";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "НКИД";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, bglocal_Wrapper, plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,hr';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('key', 'varchar', 'caption=Код, mandatory');
        $this->FLD('title', 'text', "caption=Наименование");
        
        $this->setDbUnique('key');
    }
    
    
    /**
     * Преди запис
     */
    static function on_BeforeSave($mvc, $res, $rec)
    {
        if(isset($rec->csv_title)){
            $rec->title = $rec->key . " " . $rec->csv_title;
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = "bglocal/data/nkid.csv";
        $fields = array(0 => "key", 1 => "csv_title");
        $cntObj = csv_Lib::importOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
}