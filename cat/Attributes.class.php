<?php 

/**
 * Класа менаджира аттрибутите на продуктите
 */
class cat_Attributes extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Атрибути на продуктите";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_State2, csv_Lib';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,cat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = 'admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(int, double, varchar)', 'caption=Тип');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Ако няма дефинирани атрибути, дефинира 2 атрибута при инсталиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        // Импортираме началните данни от CSV файл 
        $dataCsv = dirname (__FILE__) . "/data/Attributes.csv";
        
        $nAffected = csv_Lib::import($mvc, $dataCsv);
        
        if($nAffected) {
            $res .= "<li style='color:green'>Добавени са {$nAffected} продуктови атрибута.</li>";
        }
    }
}