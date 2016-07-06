<?php

/**
 * Чертае Елипса
 */
class cad2_Ellipse  extends cad2_Shape {
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    var $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Елементи » Елипса';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    static function addFields(&$form)
    {
        $form->FLD('x', 'float', 'caption=X');
        $form->FLD('y', 'float', 'caption=Y');
        $form->FLD('r1', 'float', 'caption=R1');
        $form->FLD('r2', 'float', 'caption=R2');

        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят');
        $form->FLD('strokeWidth', 'float', 'caption=Молив->Размер,suggestions=0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');

        $form->FLD('fill', 'color_Type', 'caption=Запълване->Цвят');
        $form->FLD('opacity', 'float', 'caption=Запълване->Плътност,suggestions=0|0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    function render($svg, $p = array())
    { 
        extract($p);
        
        if(!$notStartNewPath) {
            $svg->startPath(
                array(
                'stroke' => $stroke,
                'fill' => $fill, 
                'stroke-width' => $strokeWidth, 
                'fill-opacity' => $opacity)
                );
        }

        $svg->moveTo($x, $y - $r1, TRUE);

        self::draw($svg, $x, $y, $r1, $r1);
    }


    /**
     * Метод за изчертаване на окръжност в текущата точка, с радиус $r
     */
    public static function draw($svg, $x, $y, $r1, $r2)
    {
        $rRatio = $r1/$r2;

        for($angle=0;  $angle < 2*pi();  $angle+=0.01) {
            $x1 = $x + $r1 * cos($angle);
            $y1 = $y - $rRatio * $r1 *sin($angle);

            $svg->lineTo($x1, $y1, TRUE);
        }
    }
}