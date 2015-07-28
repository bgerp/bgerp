<?php

/**
 * Чертае измервателни линии
 */
class cad_MeasureLine {
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    var $interfaces = 'cad_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Измерителни линии';
    
    
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
        $form->FLD('dist', 'float', 'caption=Разстояние');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    static function draw(&$canvas, $p = array())
    { 
        extract($p);
        
        //дебелина на линията
        $strokeWidth = 0.1;
        //цвят на линията
        $lineColor = 'blue';
        // разстояние след линията
        $offset = 8;
        
        $canvas->openGroup();
        
        if(!$notStartNewPath) {
            
        	cad_Lib::getMeasureLine($canvas, $p);
        }
        
        $A = new cad_Vector($Ax, $Ay);
        $B = new cad_Vector($Bx, $By);
        
        $AB = new cad_Vector($B->x - $A->x, $B->y -$A->y);
        
        //ъгъла на линията
        $vectorAngle = $AB->a;
        
        //перпендикулярния на $vectorAngle
        $normalAngle = $vectorAngle - pi()/2;
        
        $A1 = $A->add(new cad_Vector($normalAngle, $dist, 'polar'));
        $B1 = $B->add(new cad_Vector($normalAngle, $dist, 'polar'));
       
        $A2 = $A1->add(new cad_Vector($normalAngle, $offset, 'polar'));
        $B2 = $B1->add(new cad_Vector($normalAngle, $offset,  'polar'));
        
        //A - A2
        $canvas->moveTo($A->x, $A->y, TRUE);
        $canvas->lineTo($A2->x, $A2->y, TRUE);
        
       
        cad_Lib::getMeasureLine($canvas, $p);
        
        //B - B2
        $canvas->moveTo($B->x, $B->y, TRUE);
        $canvas->lineTo($B2->x, $B2->y, TRUE);
        
        
        cad_Lib::getMeasureLine($canvas, $p);
        
       //A1 - B1
        $canvas->moveTo($A1->x, $A1->y, TRUE);
        $canvas->lineTo($B1->x, $B1->y, TRUE);
        
        cad_Lib::getMeasureLine($canvas, $p);
        
        //генериране на едната стрелка
        $arrow = new cad_Vector($vectorAngle - deg2rad(30), 5, 'polar');
        $arrow2 = new cad_Vector($vectorAngle + deg2rad(30), 5, 'polar');
        
        $Ar1 = $A1->add($arrow);
        $Ar2 = $A1->add($arrow2);
        
        $canvas->moveTo($A1->x, $A1->y, TRUE);
        $canvas->lineTo($Ar1->x, $Ar1->y, TRUE);
        
        $canvas->moveTo($A1->x, $A1->y, TRUE);
        $canvas->lineTo($Ar2->x, $Ar2->y, TRUE);
        
        //генериране на другата стрелка, обърната на обратно
        $arrow = new cad_Vector($vectorAngle + pi() - deg2rad(30), 5, 'polar');
        $arrow2 = new cad_Vector($vectorAngle + pi() + deg2rad(30), 5, 'polar');
        
        $Ar1 = $B1->add($arrow);
        $Ar2 = $B1->add($arrow2);
        
        $canvas->moveTo($B1->x, $B1->y, TRUE);
        $canvas->lineTo($Ar1->x, $Ar1->y, TRUE);
        
        $canvas->moveTo($B1->x, $B1->y, TRUE);
        $canvas->lineTo($Ar2->x, $Ar2->y, TRUE);
       
        $canvas->closeGroup();
  
    }
}