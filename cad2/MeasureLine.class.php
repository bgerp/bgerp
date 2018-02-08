<?php

/**
 * Чертае оразмерителна линия
 */
class cad2_MeasureLine  extends cad2_Shape {
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    var $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Елементи » Оразмерителна линия';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    static function addFields(&$form)
    {
        $form->FLD('Ax', 'float', 'caption=Ax');
        $form->FLD('Ay', 'float', 'caption=Ay');
        $form->FLD('Bx', 'float', 'caption=Bx');
        $form->FLD('By', 'float', 'caption=By');
        $form->FLD('dist', 'float', 'caption=Отстояние');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    static function render($svg, $p = array())
    { 
        extract($p);
        
        $svg->setAttr('stroke', "#0000fe");
        $svg->setAttr('stroke-width', 0.1);

        self::draw($svg, $Ax, $Ay, $Bx, $By, $dist);
    }


    /**
     * Метод за изрисуване на оразмерителна линия
     */
    public static function draw($svg, $Ax, $Ay, $Bx, $By, $dist, $measureText = NULL)
    {
        // разстояние след линията
        $offset = 2;

        if($dist < 0) {
            $Mx = $Ax;
            $My = $Ay;
            $Ax = $Bx;
            $Ay = $By;
            $Bx = $Mx;
            $By = $My;
            $dist = abs($dist);
        }

        $svg->openGroup();
        $svg->setAttr('stroke-width', 0.2);

        $A = new cad2_Vector($Ax, $Ay);
        $B = new cad2_Vector($Bx, $By);
        
        $AB = new cad2_Vector($B->x - $A->x, $B->y -$A->y);
        
        //ъгъла на линията
        $vectorAngle = $AB->a;
        
        //перпендикулярния на $vectorAngle
        $normalAngle = $vectorAngle - pi()/2;
        
        $A1 = $A->add($svg->p($normalAngle, $dist));
        $B1 = $B->add($svg->p($normalAngle, $dist));
       
        $A2 = $A1->add($svg->p($normalAngle, $offset));
        $B2 = $B1->add($svg->p($normalAngle, $offset));
      
        // A - A2
        $svg->startPath();
        $svg->moveTo($A->x, $A->y, TRUE);
        $svg->lineTo($A2->x, $A2->y, TRUE);
        
        
        $svg->startPath();
        
        // B - B2
        $svg->moveTo($B->x, $B->y, TRUE);
        $svg->lineTo($B2->x, $B2->y, TRUE);
        
        // A1 - B1
        $svg->startPath();
        $svg->moveTo($A1->x, $A1->y, TRUE);
        list($xL, $yL) =  $svg->getCP() ;
        $svg->lineTo($B1->x, $B1->y, TRUE);
        
        // Текст
        $ab = new cad2_Vector($B1->x - $A1->x, $B1->y - $A1->y);
        $text = $measureText ? $measureText . ' mm' : round($ab->r). ' mm';

        $width = 0.3 * strlen($text) * ($svg->getAttr('font-size') / 10);

        $ab1 = $svg->p($ab->a, -$width);
        $td = $svg->p($ab->a + pi()/2, -2);
        if(rad2deg($ab->a) > (90 - 0.001) && rad2deg($ab->a) < (270 - 0.001)) {
            $svg->writeText(($B1->x + $A1->x)/2 - $ab1->x - $td->x,  ($B1->y + $A1->y)/2 - $ab1->y - $td->y, $text, rad2deg($ab->a - pi()), TRUE);
        } else {
            $svg->writeText(($B1->x + $A1->x)/2 + $ab1->x + $td->x,  ($B1->y + $A1->y)/2 + $ab1->y + $td->y, $text, rad2deg($ab->a), TRUE);
        }
        
        // Генериране на едната стрелка
        $svg->startPath();
        $arrow = $svg->p($vectorAngle - deg2rad(20), 3);
        $arrow2 = $svg->p($vectorAngle + deg2rad(20), 3);
        
        $Ar1 = $A1->add($arrow);
        $Ar2 = $A1->add($arrow2);
        
        $svg->moveTo($A1->x, $A1->y, TRUE);
        $svg->lineTo($Ar1->x, $Ar1->y, TRUE);
        
        $svg->moveTo($A1->x, $A1->y, TRUE);
        $svg->lineTo($Ar2->x, $Ar2->y, TRUE);
        
        // Генериране на другата стрелка, обърната на обратно
        $arrow = $svg->p($vectorAngle + pi() - deg2rad(20), 3);
        $arrow2 = $svg->p($vectorAngle + pi() + deg2rad(20), 3);
        
        $Ar1 = $B1->add($arrow);
        $Ar2 = $B1->add($arrow2);
        
        $svg->moveTo($B1->x, $B1->y, TRUE);
        $svg->lineTo($Ar1->x, $Ar1->y, TRUE);
        
        $svg->moveTo($B1->x, $B1->y, TRUE);
        $svg->lineTo($Ar2->x, $Ar2->y, TRUE);
        
        //$svg->moveTo(80, 80, TRUE);


        $svg->closeGroup();
    }
}