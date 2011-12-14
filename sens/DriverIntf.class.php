<?php

/**
 * Интерфейс данни от за IP сензор
 *
 * Инициализация и получаване на данни
 *
 * @category   bgERP 2.0
 * @package    sens
 * @title:     Драйвер на IP сензор
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class sens_DriverIntf
{
    /**
     * Връща измерените параметри
     */
    function loadState() 
    {
       return $this->class->loadState();
    }

    /**
     * Връща html ( ET ) който да представи състоянието на
     * драйвера в "Общ изглед" - план на завода, цеха ... 
     * Този html може да използва красиво визуално оформление,
     * използвайки vendors/jsgauge
     */
    function getBlock() 
    {
       return $this->class->getBlock();
    }
    

    
    /**
     * По подадени параметри връща HTML блок, показващ вербално състоянието на сензора
     */
    function renderHtml($params = NULL)
    {
        return $this->class->renderHtml($params);
    }
}