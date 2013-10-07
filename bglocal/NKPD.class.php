<?php 


/**
 * Типове договори
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_NKPD extends core_Master
{
    
    /**
     * Заглавие
     */
    var $title = "Национална класификация на професиите и длъжностите";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "НКПД";
  
    
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
     * Изпълнява се преид импортирването на запис
     */
    static function on_BeforeSave($mvc, $res, $rec)
    {
    	if(isset($rec->csv_key)){
    		$rec->key = $rec->csv_key.$rec->csv_title;
    		$rec->title = $rec->key. " " .$rec->csv_position;
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
 		$file = "bglocal/data/nkpd.csv";
    	$fields = array(0 => "csv_key", 1 => "csv_title", 2 => "csv_position");
    	$cntObj = csv_Lib::importOnceFromZero($mvc, $file, $fields);
    	$res .= $cntObj->html;
    }
}