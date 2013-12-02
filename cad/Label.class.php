<?php

/**
 * Чертае закръглен ъгъл
 */
class cad_RoundTo {
    
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

        $form->FLD('Cx', 'float', 'caption=Cx');
        $form->FLD('Cy', 'float', 'caption=Cy');
      
        
        
        $form->FLD('r', 'float', 'caption=R');

        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят');
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

        $A = new cad_Vector($Ax, $Ay);
        $B = new cad_Vector($Bx, $By);
        $C = new cad_Vector($Cx, $Cy);

        $AB = $B->add($A->neg());
        $BC = $C->add($B->neg());
        $BA = $AB->neg();
        
        $m = abs($r * tan((-$AB->a + $BC->a)/2));
 
        $M = $B->add(new cad_Vector($BA->a, $m, 'polar'));
        $N = $B->add(new cad_Vector($BC->a, $m, 'polar'));
        
        $c = 4/3*(M_SQRT2-1);
        
        $MB = $B->add($M->neg());
        
        $Mc = $M->add( new cad_Vector($MB->a, $MB->r * $c, 'polar'));

        $NB = $B->add($N->neg());
        $Nc = $N->add( new cad_Vector($NB->a, $NB->r * $c, 'polar'));

// bp($A, $M, $BA, new cad_Vector($BA->a, $m, 'polar'), $N, $C);
        

        $svg->moveTo($A->x, $A->y, TRUE);
        if(round($A->x, 5) != round($M->x, 5) || round($A->y, 5) != round($M->y, 5)) {
            $svg->lineTo($M->x, $M->y, TRUE);
        }
        $svg->curveTo($Mc->x, $Mc->y, $Nc->x, $Nc->y, $N->x, $N->y, TRUE);
        
        if(round($C->x, 5) != round($N->x, 5) || round($C->y, 5) != round($N->y, 5)) {
            $svg->lineTo($C->x, $C->y, TRUE);
        }
        

        return;

//bp($A, $B, $C, $AB, $BC, $M, $N);



        

        $svg->curveTo($r * $c, 0, $r,  $r - $c * $r,  $r,  $r);
        
        $svg->curveTo(0, $r * $c, $r * $c - $r,  $r,  -$r, $r);
        
        $svg->curveTo(-$r * $c, 0, -$r,  $r * $c - $r,  -$r, -$r);
        
        $svg->curveTo( 0, -$r * $c, -$c * $r + $r,  -$r,  $r, -$r);

    }
    
}