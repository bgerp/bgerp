<?php

/**
 * Интерфейс за IP RFID рийдър
 */
interface intf_IpRfid
{
    
    
    /**
     * Връща запис с IP четеца или база данни
     */
    function getData($date);
    
    
    /**
     * Задава параметрите за свръзка
     */
    function init($params);
}