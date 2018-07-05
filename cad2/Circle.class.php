<?php

/**
 * Чертае Окръжност
 */
class cad2_Circle extends cad2_Shape
{
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    public $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    public $title = 'Елементи » Окръжност';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('x', 'float', 'caption=X');
        $form->FLD('y', 'float', 'caption=Y');
        $form->FLD('r', 'float', 'caption=R');

        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят');
        $form->FLD('strokeWidth', 'float', 'caption=Молив->Размер,suggestions=0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');

        $form->FLD('fill', 'color_Type', 'caption=Запълване->Цвят');
        $form->FLD('opacity', 'float', 'caption=Запълване->Плътност,suggestions=0|0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    public function render($svg, $p = array())
    {
        extract($p);
        
        if (!$notStartNewPath) {
            $svg->startPath(
                array(
                'stroke' => $stroke,
                'fill' => $fill,
                'stroke-width' => $strokeWidth,
                'fill-opacity' => $opacity)
                );
        }

        $svg->moveTo($x, $y - $r, true);
        
        self::draw($svg, $r);
    }


    /**
     * Метод за изчертаване на окръжност в текущата точка, с радиус $r
     */
    public static function draw($svg, $r)
    {
        $svg->roundTo($r, 0, $r, $r, $r);
        $svg->roundTo(0, $r, -$r, $r, $r);
        $svg->roundTo(-$r, 0, -$r, -$r, $r);
        $svg->roundTo(0, -$r, $r, -$r, $r);
    }
}
