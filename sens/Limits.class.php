<?php


/**
 * Клас 'sens_Limits' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    sens
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class sens_Limits extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, sens_Wrapper, Params=sens_Params';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Лимити за стойностите на сензорите';
    
    
    /**
     *  @todo Чака за документация...
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
        $this->FLD('sensorId', 'key(mvc=sens_Sensors,select=title)', 'caption=Сензор');
        $this->FLD('paramId', 'key(mvc=sens_Params,select=param)', 'caption=Параметър');
        $this->FLD('type', 'enum(min=Минимална,max=Максимална)', 'caption=Тип');
        $this->FLD('value', 'double(decimals=2)', 'caption=Стойност');
        $this->FLD('statusText', 'varchar(255)', 'caption=Съобщение');
        $this->FLD('statusAlert', 'enum(0=no,1=low,2=moderate,3=high)', 'caption=Alert');
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
        $paramRec = $this->Params->fetch($rec->paramId);
        $row->value = "<div style='width: 20px; float: right;'>{$paramRec->details}</div>
                       <div style='float: right;'>{$row->value}</div>";
    }
}