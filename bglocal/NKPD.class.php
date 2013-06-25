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
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
 	
		
    	$res .= static::loadData();
        
    }
    
    
    /**
     * Зареждане на началните празници в базата данни
     */
    static function loadData()
    {
    	
        $csvFile = __DIR__ . "/data/nkpd.csv";
        
        $created = $updated = 0;
        
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         
            while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
               
                $rec = new stdClass();
              
                $rec->key = $csvRow[0].$csvRow[1]; 
                $rec->title = $csvRow[0].$csvRow[1]. " ". $csvRow[2];
                
                $exRec = self::fetch("#key = '{$rec->key}'");
                
                if($exRec) {
                	$rec->id = $exRec->id;
                	$updated++;
                } else {
                	$created++;
                }
                   
                static::save($rec);

    
            }
            
            fclose($handle);
            
            $res .= "<li style='color:green;'>Създадени са записи за {$created} кода по НКПД. Обновени са {$updated} кода по НКПД.</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}