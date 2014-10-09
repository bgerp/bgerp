<?php

/**
 * Интерфейс за рисувач на фигура
 */
class cad_ShapeIntf {

    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    function addParams(&$form)
    {
        return $this->class->setForm($form);
    }


    /**
     * Метод за изрисуване на фигурата
     */
    function draw(&$svg, $params = array())
    {
        return $this->class->draw($svg, $params);
    }
    
}