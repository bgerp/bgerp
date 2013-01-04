<?php 


/**
 * Mодел, който представлява множество от различните типове сигнали.
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_IssueTypes extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'issue_Types';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Типове сигнали';
    
    
    /**
     * 
     */
    var $singleTitle = 'Тип на сигнала';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, support';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, support';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, support';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, support';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, support';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, plg_RowTools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('type', 'varchar', "caption=Тип, width=100%");
        
        $this->setDbUnique('type');
    }
    
    
	/**
     * Зареждане на стирнговете, за сигналите по подразбиране
     */
    static function loadData()
    {
        //Пътя до CSV файла
        $csvFile = static::getCsvFile();
        
        //Коко записа са създадени
        $created = 0;
        
        //Ако не може да се намери файла или нямаме права за работа с него
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            
            //обхождаме целия файл
            while (($csvRow = fgetcsv($handle, 1000, "|")) !== FALSE) {
                
                //Създаваме обект, който ще записваме в БД
                $rec = new stdClass();
                
                // Типа
                $rec->type = $csvRow[0];
                
                //Ако запишем успешно, добава единица в общия брой записи
                if (static::save($rec, NULL, 'IGNORE')) {
                    $created++;    
                }
            }
            
            //Затваряме файла
            fclose($handle);
            
            //Съобщението което ще се показва след като приключим
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови типове сигнали.</li>";
        } else {
            
            //Ако има проблем при отварянето на файла
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
    
    
    /**
     * Връща пътя до CSV файла
     */
    static private function getCsvFile()
    {
        
        return __DIR__ . "/csv/IssueTypes.csv";
    }
}