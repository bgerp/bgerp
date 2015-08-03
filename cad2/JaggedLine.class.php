<?php

/**
 * Чертае назъбена линия
 */
class cad2_JaggedLine  extends cad2_Shape {
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    var $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Елементи » Назъбена линия';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    static function addFields(&$form)
    {
        $form->FLD('x0', 'float', 'caption=Начало->X0,mandatory');
        $form->FLD('y0', 'float', 'caption=Начало->Y0,mandatory');
        $form->FLD('x1', 'float', 'caption=Край->X1,mandatory');
        $form->FLD('y1', 'float', 'caption=Край->Y1,mandatory');
        
        $form->FLD('md', 'float', 'caption=Назъбване->Широчина,mandatory');
        $form->FLD('td', 'float', 'caption=Назъбване->Височина');
        $form->FLD('spacer', 'float', 'caption=Назъбване->Разредка');

        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят');
        $form->FLD('strokeWidth', 'float', 'caption=Молив->Размер,suggestions=0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    function render($svg, $p = array())
    { 
        extract($p);
        
        $svg->setAttr('stroke', $stroke);
        $svg->setAttr('stroke-width', $strokeWidth);

        $svg->startPath();
        $svg->moveTo($x0, $y0, TRUE);

        self::draw($svg, $x1, $y1, $md, $td, $spacer, TRUE);
    }


    /**
     * Изчертава назъбена линия до съответната точка
     */
    public static function draw($svg, $x1, $y1, $md, $td = NULL, $spacer = 0, $absolute = FALSE)
    {
        if($absolute) {
            // Вземаме абсолютните координати на началната
            list($x0, $y0)  = $svg->getCP();
            $ab = new cad2_Vector($x1-$x0, $y1-$y0);
        } else {
            $ab = new cad2_Vector($x1, $y1);
        }
        
        if(!$td) $td = $md/2;

        $nb = round($ab->r / ($md * 2)) * 2;
        
        $spacer = min(0.4 * $md, $spacer);

        for($i = 1; $i <= $nb; $i++) {

            if($spacer > 0) {
                $m = $svg->p($ab->a, $spacer);
                $svg->lineTo($m->x, $m->y);
            }

            // Малко
            $m = $svg->p($ab->a, $ab->r / ($nb*2) - $spacer);
            $n = $svg->p($ab->a - pi()/2, $td);
            $svg->lineTo($m->x + $n->x, $m->y + $n->y); 
            
            $m = $svg->p($ab->a, $ab->r / ($nb*2) - $spacer);
            $n = $svg->p($ab->a + pi()/2, $td);
            $svg->lineTo($m->x + $n->x, $m->y + $n->y);
            
            if($spacer > 0) {
                $m = $svg->p($ab->a, $spacer);
                $svg->lineTo($m->x, $m->y);
            }
        }
    }

    
}