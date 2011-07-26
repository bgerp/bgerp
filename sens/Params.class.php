<?php

/**
 * Мениджър за параметрите на сензорите
 */
class sens_Params extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, sens_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Параметри, поддържани от сензорите';
    
    
    /**
     * Права
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'sens, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('unit', 'varchar(3)', 'caption=Означение, mandatory');
        $this->FLD('param', 'varchar(255)', 'caption=Параметър, mandatory');
        $this->FLD('details', 'varchar(255)', 'caption=Детайли');
    }
    
    
    /**
     * Добавяме % или C в зависимост дали показваме влажност/температура
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->details = "<div style='float: right;'>{$row->details}</div>";
    }
    
    
    /**
     * Ако няма дефинирани параметри, дефинира такива при инсталиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
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