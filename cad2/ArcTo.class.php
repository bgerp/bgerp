<?php


/**
 * Чертае част от окръжност
 */
class cad2_ArcTo extends cad2_Shape
{
    /**
     * Задължителен интерфейс за всички фигури
     */
    public $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    public $title = 'Елементи » Закръглен ъгъл';
    
    
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
        
        
        $form->FLD('r', 'float', 'caption=R');
        
        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят,value=#333333');
        $form->FLD('strokeWidth', 'float', 'caption=Молив->Размер,suggestions=0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');
    }
    
    
    /**
     * Интерфейсен метод за изчертаване на фигурата
     */
    public function render(&$svg, $p = array())
    {
        extract($p);
        
        $svg->startPath(
            array(
                'stroke' => $stroke,
                'fill' => 'none',
                'stroke-width' => $strokeWidth
            )
            );
        
        $svg->moveTo($Ax, $Ay, true);
        
        self::draw($svg, $Bx, $By, $r, true);
    }
    
    
    /**
     * Библиотечен метод за изчертаване на фигурата
     */
    public static function draw($svg, $x1, $y1, $r, $absolute)
    {
        // Вземаме абсолютните координати на началната
        list($x0, $y0) = $svg->getCP();
        
        // Правим координатите абсолютни
        if (!$absolute) {
            $x1 += $x0;
            $y1 += $y0;
        }
        
        $A = new cad2_Vector($x0, $y0);
        $B = new cad2_Vector($x1, $y1);
        $AB = $B->add($A->neg());
        
        $M = $A->add($svg->p($AB->a, $AB->r / 2));
        
        $dist = $r * $r - $AB->r / 2 * $AB->r / 2;
        
        if ($dist < 0) {
            $m = 0;
            $r = ($AB->r / 2) * ($r < 0?-1:1);
        } else {
            $m = sqrt($dist);
        }
        
        $reverse = ($r < 0?-1:1) * 0.00001;
        
        $C = $M->add($svg->p($AB->a - pi() / 2 + ($r < 0 ? pi() : 0), $m));
        
        $CA = $A->add($C->neg());
        $CB = $B->add($C->neg());
        
        
        $a = $CA->a;
        $b = $CB->a;
        
        $d = pi() / (48 * round(max(log(abs($r)), 1)));
        
        if ($a > $b) {
            if ($a - $b + $reverse > pi()) {
                for ($i = $a; $i <= $b + 2 * pi(); $i += $d) {
                    $X = $C->add($svg->p($i, abs($r)));
                    $svg->lineTo($X->x, $X->y, true);
                }
            } else {
                for ($i = $a; $i >= $b; $i -= $d) {
                    $X = $C->add($svg->p($i, abs($r)));
                    $svg->lineTo($X->x, $X->y, true);
                }
            }
        } else {
            if ($CB->a - $CA->a + $reverse > pi()) {
                for ($a = $CA->a + 2 * pi(); $a >= $CB->a; $a -= $d) {
                    $X = $C->add($svg->p($a, abs($r)));
                    $svg->lineTo($X->x, $X->y, true);
                }
            } else {
                for ($a = $CA->a; $a <= $CB->a; $a += $d) {
                    $X = $C->add($svg->p($a, abs($r)));
                    
                    $svg->lineTo($X->x, $X->y, true);
                }
            }
        }
        $svg->lineTo($x1, $y1, true);
    }
}
