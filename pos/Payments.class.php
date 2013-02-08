<?php



/**
 * Мениджър за "Методи на Плащане" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Payments extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = "Методи на Плащане";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, plg_State, pos_Wrapper';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Метод на плащане";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, tools=Пулт, title, show';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'admin, pos';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin, pos';
    

    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar(255)', 'caption=Наименование');
    	$this->FLD('show', 'enum(yes=Да,no=Не)', 'maxRadio=4,maxColumns=1,caption=Показване,value=yes');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$csvFile = __DIR__ . "/csv/PaymentMethods.csv";
        $created = $updated = 0;
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec = new stdClass();
                $rec->id = $csvRow [0];
                $rec->title = $csvRow [1];
                $rec->show = $csvRow [2];
               	if ($rec->id = static::fetchField(array("#title = '[#1#]'", $rec->title), 'id')) {
                    $updated++;
                } else {
                    $created++;
                }
                
                static::save($rec, NULL, 'replace');
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            
            $res .= "Създадени {$created} нови Платежни метода, обновени {$updated} съществуващи метода.</li>";
        } else {
            
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}