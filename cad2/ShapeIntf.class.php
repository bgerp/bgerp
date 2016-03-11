<?php

/**
 * Интерфейс за рисувач на фигура
 */
class cad2_ShapeIntf extends embed_DriverIntf {

    /**
     * Метод за изрисуване на фигурата
     */
    function draw($svg, $params = array())
    {
        return $this->class->draw($svg, $params);
    }
    
}