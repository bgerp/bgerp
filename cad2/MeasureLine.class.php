<?php


/**
 * Чертае оразмерителна линия
 */
class cad2_MeasureLine extends cad2_Shape
{
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    public $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    public $title = 'Елементи » Оразмерителна линия';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
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
    public static function render($svg, $p = array())
    {
        extract($p);
        
        $svg->setAttr('stroke', '#0000fe');
        $svg->setAttr('stroke-width', 0.1);
        
        self::draw($svg, $Ax, $Ay, $Bx, $By, $dist);
    }
    
    
    /**
     * Метод за изрисуване на оразмерителна линия
     */
    public static function draw($svg, $Ax, $Ay, $Bx, $By, $dist, $measureText = null, $lineMultiplier = 1)
    {
        // разстояние след линията
        $offset = 2;
        
        if ($dist < 0) {
            $Mx = $Ax;
            $My = $Ay;
            $Ax = $Bx;
            $Ay = $By;
            $Bx = $Mx;
            $By = $My;
            $dist = abs($dist);
        }
        
        $svg->openGroup();
        $svg->setAttr('stroke-width', 0.15 * $lineMultiplier);
        
        $A = new cad2_Vector($Ax, $Ay);
        $B = new cad2_Vector($Bx, $By);
        
        $AB = new cad2_Vector($B->x - $A->x, $B->y - $A->y);
        
        //ъгъла на линията
        $vectorAngle = $AB->a;
        
        //перпендикулярния на $vectorAngle
        $normalAngle = $vectorAngle - pi() / 2;
        
        $A1 = $A->add($svg->p($normalAngle, $dist));
        $B1 = $B->add($svg->p($normalAngle, $dist));
        
        $A2 = $A1->add($svg->p($normalAngle, $offset));
        $B2 = $B1->add($svg->p($normalAngle, $offset));
        
        // A - A2
        $svg->startPath();
        $svg->moveTo($A->x, $A->y, true);
        $svg->lineTo($A2->x, $A2->y, true);
        
        
        $svg->startPath();
        
        // B - B2
        $svg->moveTo($B->x, $B->y, true);
        $svg->lineTo($B2->x, $B2->y, true);
        
        // A1 - B1
        $svg->startPath();
        $svg->moveTo($A1->x, $A1->y, true);
        list($xL, $yL) = $svg->getCP() ;
        $svg->lineTo($B1->x, $B1->y, true);
        
        // Текст
        $ab = new cad2_Vector($B1->x - $A1->x, $B1->y - $A1->y);
        $text = $measureText ? $measureText . ' mm' : round($ab->r, 1). ' mm';
        
        $width = 0.3 * strlen($text) * ($svg->getAttr('font-size') / 10);
        
        $ab1 = $svg->p($ab->a, -$width);
        $td = $svg->p($ab->a + pi() / 2, -2.5);
        if (rad2deg($ab->a) > (90 - 0.001) && rad2deg($ab->a) < (270 - 0.001)) {
            $svg->writeText(($B1->x + $A1->x) / 2 - $ab1->x - $td->x, ($B1->y + $A1->y) / 2 - $ab1->y - $td->y, $text, rad2deg($ab->a - pi()), true);
        } else {
            $svg->writeText(($B1->x + $A1->x) / 2 + $ab1->x + $td->x, ($B1->y + $A1->y) / 2 + $ab1->y + $td->y, $text, rad2deg($ab->a), true);
        }
        
        // Генериране на едната стрелка
        $svg->startPath();
        $arrow = $svg->p($vectorAngle - deg2rad(20), 3);
        $arrow2 = $svg->p($vectorAngle + deg2rad(20), 3);
        
        $Ar1 = $A1->add($arrow);
        $Ar2 = $A1->add($arrow2);
        
        $svg->moveTo($A1->x, $A1->y, true);
        $svg->lineTo($Ar1->x, $Ar1->y, true);
        
        $svg->moveTo($A1->x, $A1->y, true);
        $svg->lineTo($Ar2->x, $Ar2->y, true);
        
        // Генериране на другата стрелка, обърната на обратно
        $arrow = $svg->p($vectorAngle + pi() - deg2rad(20), 3);
        $arrow2 = $svg->p($vectorAngle + pi() + deg2rad(20), 3);
        
        $Ar1 = $B1->add($arrow);
        $Ar2 = $B1->add($arrow2);
        
        $svg->moveTo($B1->x, $B1->y, true);
        $svg->lineTo($Ar1->x, $Ar1->y, true);
        
        $svg->moveTo($B1->x, $B1->y, true);
        $svg->lineTo($Ar2->x, $Ar2->y, true);
        
        //$svg->moveTo(80, 80, TRUE);
        
        
        $svg->closeGroup();
    }
}
