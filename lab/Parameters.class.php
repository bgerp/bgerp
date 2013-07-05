<?php



/**
 * Мениджър за параметрите в лабораторията
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_Parameters extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Параметри за лабораторни тестове";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2,
                             plg_RowTools, plg_Printing, lab_Wrapper,
                             plg_Sorting, fileman_Files';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'lab,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'lab,ceo';
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
    var $singleFields = 'id,name,type,dimension,
                             precision,description,state';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Параметър";
    
         
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/pipette.png';
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    //var $singleLayoutFile = 'lab/tpl/SingleLayoutParameters.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Параметър');
        $this->FLD('type', 'enum(number=Числов,bool=Да/Не,text=Текстов)', 'caption=Тип');
        $this->FLD('dimension', 'varchar(16)', 'caption=Размерност,notSorting,oldFieldName=dimention');
        $this->FLD('precision', 'int', 'caption=Прецизност,notSorting');
        $this->FLD('description', 'richtext(bucket=Notes)', 'caption=Описание,notSorting');
        
        $this->setDbUnique('name,dimension');
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Сортиране на записите по name
        $data->query->orderBy('name=ASC');
    }
    
    
    /**
     * Линкове към single
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = Ht::createLink($row->name, array($mvc, 'single', $rec->id));
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
    	
        $csvFile = __DIR__ . "/csv/lab_Parameters.csv";
        
        $created = $updated = 0;
        
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         
            while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
               
                $rec = new stdClass();
              
               
                $rec->name = $csvRow[0];
               
                $rec->type = $csvRow[1];
                
                $rec->dimension = $csvRow[2]; 
                
                $rec->precision = $csvRow[3];
              
                $rec->description = $csvRow[4];
                            
                
                static::save($rec);

                $ins++;
            }
            
            fclose($handle);
            
            $res .= "<li style='color:green;'>Създадени са записи за {$ins} лабораторни параметри</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}