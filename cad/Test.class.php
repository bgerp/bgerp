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
        $form->FLD('g', 'float', 'caption=Фалт');

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
        
        if(!$g) {
            $g = round($w * 2/3.5) / 2 - 10;
        }

        $r = 20;

        $canvas->moveTo($x, $y, TRUE);
        
        $canvas->lineTo($g, 0);

        $canvas->lineTo(0, 150 - $r);
        $canvas->roundTo(0, $r, $r, $r, $r);

        $canvas->lineTo($w - 2 * $g - $r, 0);
        $canvas->lineTo(0, -150);
        

        $canvas->lineTo($g, 0);
        $canvas->lineTo(0, $h);
        $canvas->lineTo(-$w, 0);
        $canvas->lineTo(0, -$h);

    }
    
}