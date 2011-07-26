<?php

/**
 * Интерфейс за IP камера
 */
interface intf_IpCamera
{
    
    /**
     * Записва видео в указания файл с продължителност $duration
     */
    function captureVideo($savePath, $duration);
    
    
    /**
     * Записва снимка от камерата в указания файл;
     */
    function getPicture();
    
    
    /**
     * Задава параметрите на камерата
     * $params[0] e ip
     * $params[1] са останалите параметри
     */
    function init($params);
}