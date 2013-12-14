<?php

/**
 * Чертае част от окръжност
 */
class cad_ArcTo {
    
    /**
     * Задължителен интерфейс за всички фигури
     */
    var $interfaces = 'cad_ShapeIntf';
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Закръглен ъгъл';


    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    static function addParams(&$form)
    {
        $form->FLD('Ax', 'float', 'caption=Ax');
        $form->FLD('Ay', 'float', 'caption=Ay');
        
        $form->FLD('Bx', 'float', 'caption=Bx');
        $form->FLD('By', 'float', 'caption=By');

        
        $form->FLD('r', 'float', 'caption=R');

        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят,value=#333333');
        $form->FLD('strokeWidth', 'float', 'caption=Молив->Размер,suggestions=0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    static function draw(&$svg, $p = array())
    { 
        extract($p);

        $svg->startPath(
            array(
            'stroke' => $stroke,
            'fill' => 'none', 
            'stroke-width' => $strokeWidth
            )
            );

        $svg->moveTo($Ax, $Ay, TRUE);

        $svg->arcTo($Bx, $By, $r, TRUE);        

        return;
    }
    
}