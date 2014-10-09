<?php

/**
 * Чертае Тестова фигура
 */
class cad_Test {
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    var $interfaces = 'cad_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Тест';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    static function addParams(&$form)
    {
        $form->FLD('x', 'float', 'caption=X');
        $form->FLD('y', 'float', 'caption=Y');
        $form->FLD('w', 'float', 'caption=Широчина');
        $form->FLD('h', 'float', 'caption=Височина');

        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят');
        $form->FLD('strokeWidth', 'float', 'caption=Молив->Размер,suggestions=0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');

        $form->FLD('fill', 'color_Type', 'caption=Запълване->Цвят');
        $form->FLD('opacity', 'float', 'caption=Запълване->Прозрачност,suggestions=0|0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    static function draw(&$canvas, $p = array())
    { 
        extract($p);

        $canvas->startPath(
            array(
            'stroke' => $stroke,
            'fill' => $fill, 
            'stroke-width' => $strokeWidth, 
            'fill-opacity' => $opacity)
            );

        $canvas->moveTo($x, $y, TRUE);
        
        cad_Rectangle::draw($canvas, $p);
        $p['r'] = min($p['w'], $p['h']) / 4;
        $p['fill'] = '#cfd';
        //$p['notStartNewPath'] = TRUE;
        cad_Circle::draw($canvas, $p);
    }
    
}