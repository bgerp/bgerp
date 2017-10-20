<?php

/**
 * Чертае Оразмеряване на ъгъл
 */
class cad2_MeasureAngle  extends cad2_Shape {
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    var $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Елементи » Оразмеряване на ъгъл';
    
    
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
        $form->FLD('Cx', 'float', 'caption=Bx');
        $form->FLD('Cy', 'float', 'caption=By');
    }


    /**
     * Метод за изрисуване на фигурата
     */
    static function render($svg, $p = array())
    { 
        extract($p);
        self::draw($svg, $Ax, $Ay, $Bx, $By, $Cx, $Cy);
    }


    /**
     * Метод за debug на ъгъл ABC
     */
    public static function draw($svg, $Ax, $Ay, $Bx, $By, $Cx, $Cy)
    {
        $svg->openGroup();

        $AB = new cad2_Vector($Bx - $Ax, $By - $Ay);
        $CB = new cad2_Vector($Bx - $Cx,  $By - $Cy);

        //ъгъла на линията
        $vectorAngle = abs($AB->a - $CB->a);
        $angleGrad = rad2deg($vectorAngle);

        expect(FALSE, $angleGrad);
    }
}