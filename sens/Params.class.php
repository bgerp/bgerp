<?php



/**
 * Мениджър за параметрите на сензорите
 *
 *
 * @category  all
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens_Params extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_Created, plg_RowTools, sens_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Параметри, поддържани от сензорите';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'sens, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('unit', 'varchar(16)', 'caption=Означение, mandatory');
        $this->FLD('param', 'varchar(255)', 'caption=Параметър, mandatory');
        $this->FLD('details', 'varchar(255)', 'caption=Детайли');
    }
    
    
    /**
     * Добавяме означението за съответната мерна величина
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->details = "<div style='float: right;'>{$row->details}</div>";
    }
    
    
    /**
     * Връща id-то под което е заведена мерната величина
     *
     * @param $param
     */
    static function getIdByUnit($param)
    {
        $query = self::getQuery();
        $query->where('#unit="' . $param . '"');
        
        $res = $query->fetch();
        
        return $res->id;
    }
    
    
    /**
     * Ако няма дефинирани параметри, дефинира такива при инсталиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // В случай, че няма данни в таблицата, зареждаме от CSV файл.
        if (!$mvc->fetch('1=1')) {
            // Прочитаме CSV файла 
            $csvFile = dirname (__FILE__) . "/data/Params.csv";
            
            $Csv = cls::get('csv_lib');
            $nAffected = $Csv->loadDataFromCsv($mvc, $csvFile);
            
            $res .= "<li>Добавени са {$nAffected} параметри за сензорите.</li>";
        }
    }
}