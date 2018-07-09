<?php


/**
 * Интерфейс данни от за IP сензор
 *
 * Инициализация и получаване на данни
 *
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс на драйвер на IP сензор
 */
class sens_DriverIntf
{
    /**
     * Връща измерените параметри
     */
    public function loadState()
    {
        return $this->class->loadState();
    }
    
    
    /**
     * Връща html ( ET ) който да представи състоянието на
     * драйвера в "Общ изглед" - план на завода, цеха ...
     * Този html може да използва красиво визуално оформление,
     * използвайки vendors/jsgauge
     */
    public function getBlock()
    {
        return $this->class->getBlock();
    }
    
    
    /**
     * По подадени параметри връща HTML блок, показващ вербално състоянието на сензора
     */
    public function renderHtml($params = null)
    {
        return $this->class->renderHtml($params);
    }
}
