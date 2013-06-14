<?php



/**
 * Клас 'salecond_Others' - Други условия на доставка
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_Others extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, salecond_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Други условия на продажба';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Други условия на продажба";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(double=Число, int=Цяло число, varchar=Текст, color=Цвят, date=Дата)', 'caption=Тип');
        $this->FLD('default', 'varchar(64)', 'caption=Дефолт');
        $this->FLD('sysId', 'varchar(32)', 'caption=Sys Id');
        
        $this->setDbUnique('name');
        $this->setDbUnique("sysId");
    }
    
    
    /**
     * Начални данни за инициализация
     */
    public static function setup()
    {
    	$csvFile = __DIR__ . "/csv/Others.csv";
        $created = $updated = 0;
        if(($handle = @fopen($csvFile, "r")) !== FALSE) {
          while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
              $rec = new stdClass();
              $rec->name = $csvRow[0];
              $rec->type = $csvRow[1];
              $rec->sysId = $csvRow[2];
              $rec->default = $csvRow[3];
              if($rec->id = static::fetchField("#sysId = '{$rec->sysId}'", 'id')){
              	$updated++;
           } else {
                $created++;
           }
         
           static::save($rec);
		}
            
        fclose($handle);
           $res .= "<li style='color:green;'>Създадени са записи за {$created} други условия за продажба. Обновени {$updated}</li>";
        } else {
           $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}