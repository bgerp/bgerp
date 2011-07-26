<?php

/**
 * Интерфейс за IP сензор
 */
interface intf_IpSensor
{
    
    /**
     * Задава параметрите на камерата
     */
    function init($params);
    
    
    /**
     * Връща измерените параметри
     */
    function getData();
    
    
    /**
     * По входна стойност от $rec връща HTML
     */
    function renderHtml();
}