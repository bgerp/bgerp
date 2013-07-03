<?php



/**
 * Клас 'salecond_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_DeliveryTerms extends core_Master
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, salecond_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, codeName, term';
    
    var $rowToolsSingleField = 'codeName';

    /**
     * Полетата, които ще се показват в единичния изглед
     */
    var $singleFields = 'id, term, codeName, forSeller, forBuyer, transport';
    
    /**
     * @todo Чака за документация...
     */
    var $canSingle = 'user';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'ceo,salecond';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo,salecond';
    
    
    /**
     * Кой може да добавя
     */
    var $canAdd = 'ceo,salecond';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'trans_DeliveryTerms';
    
    
    /**
     * Заглавие
     */
    var $title = 'Условия на доставка';
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Условие на доставка";
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/delivery.png';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'salecond/tpl/SingleDeliveryTerms.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('codeName', 'varchar', 'caption=Код');
        $this->FLD('term', 'text', 'caption=Обяснение');
        $this->FLD('forSeller', 'text', 'caption=За продавача');
        $this->FLD('forBuyer', 'text', 'caption=За купувача');
        $this->FLD('transport', 'text', 'caption=Транспорт');
        
        $this->setDbUnique('codeName');
    }
    
    
    /**
     * Условия на доставка по подразбиране според клиента
     * 
     * @see doc_ContragentDataIntf
     * @param stdClass $contragentInfo
     * @return int key(mvc=salecond_DeliveryTerms) 
     */
    public static function getDefault($contragentInfo)
    {
        // @TODO
        return static::fetchField("#codeName = 'EXW'", 'id'); // за тест
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
 		// Изтриваме съдържанието й
		$mvc->db->query("TRUNCATE TABLE  `{$mvc->dbTableName}`");
		
    	$res .= static::loadData();
       
    }
    
    
    /**
     * Зареждане на началните празници в базата данни
     */
    static function loadData()
    {
    	
        $csvFile = __DIR__ . "/csv/DeliveryTerms.csv";
        
        $created = $updated = 0;
        
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         
            while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
               
                $rec = new stdClass();
              
               
                $rec->term = $csvRow[0];
               
                $rec->codeName = $csvRow[1];
                
                $rec->forSeller = $csvRow[2]; 
                
                $rec->forBuyer = $csvRow[3];
              
                $rec->transport = $csvRow[4];
                            
                
                static::save($rec);

                $ins++;
            }
            
            fclose($handle);
            
            $res .= "<li style='color:green;'>Създадени са записи за {$ins} транспортни условия</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}