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
        $form->FLD('A0x', 'float', 'caption=Ax');
        $form->FLD('A0y', 'float', 'caption=Ay');
        $form->FLD('B0x', 'float', 'caption=Bx');
        $form->FLD('B0y', 'float', 'caption=By');
        $form->FLD('dist', 'float', 'caption=Разстояние');
        $form->FLD('direction', 'enum(N=Север,S=Юг,E=Изток,W=Запад)', 'caption=Позиция');
        $form->FLD('stroke', 'color_Type', 'caption=Молив->Цвят');
        $form->FLD('strokeWidth', 'float', 'caption=Молив->Размер,suggestions=0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1');

    }


    /**
     * Метод за изрисуване на фигурата
     */
    static function draw(&$canvas, $p = array())
    { 
        extract($p);
        
        if(!$notStartNewPath) {
            $canvas->startPath(
                array(
                'stroke' => $stroke,
                'stroke-width' => $strokeWidth)
                );
        }
       
        //A0 и Б0 - нач. точки
        //А1 и Б1 - точки, при кои правим линията по дължината
        //А2 и Б2 - точки за отстъпа след линията
        
        //примерен отстъп
        $offset = 6;
        
        if($direction == 'S'){
            //ако ще показваме под нач. точки
            $A1y = $A0y + $dist;
            $B1y = $B0y + $dist;
            
            $A1x = $A2x = $A0x;
            $B1x = $B2x = $B0x;
            
            $A2y = $A1y + $offset;
            $B2y = $B1y + $offset;
         
        } else if($direction == 'N'){
            //ако ще показваме над нач. точки
            $A1y = $A0y - $dist;
            $B1y = $B0y - $dist;
            
            $A1x = $A2x = $A0x;
            $B1x = $B2x = $B0x;
            
            $A2y = $A1y - $offset;
            $B2y = $B1y - $offset;
        
        } else if($direction == 'W'){
            //ако ще показваме в ляво от нач. точки
            $A1x = $A0x - $dist;
            $B1x = $B0x - $dist;
            $A1y = $A2y = $A0y;
            $B1y = $B2y = $B0y;
            $A2x = $A1x - $offset;
            $B2x = $B1x - $offset;
        
        } else{
            //ако ще показваме в дясно от нач. точки
            $A1x = $A0x + $dist;
            $B1x = $B0x + $dist;
            
            $A1y = $A2y = $A0y;
            $B1y = $B2y = $B0y;
            
            $A2x = $A1x + $offset;
            $B2x = $B1x + $offset;
        }
        
        //черта между А1 и Б1
        $canvas->moveTo($A1x, $A1y, TRUE);
        $canvas->lineTo($B1x, $B1y, TRUE);
        
        //черта от А0 до А2 
        $canvas->moveTo($A0x, $A0y, TRUE);
        $canvas->lineTo($A2x, $A2y, TRUE);
      
        //черта от Б0 до Б2
        $canvas->moveTo($B0x, $B0y, TRUE);
        $canvas->lineTo($B2x, $B2y ,TRUE);
        
        //построяване на стрелките
        if($direction == 'W' || $direction == 'E'){
            //едната стрелка
            $canvas->moveTo($A1x, $A1y, TRUE);
            $canvas->lineTo(2, 5, FALSE);
            $canvas->moveTo($A1x, $A1y, TRUE);
            $canvas->lineTo(-2, 5, FALSE);
            
            //другата стрелка
            $canvas->moveTo($B1x, $B1y, TRUE);
            $canvas->lineTo(-2, -5, FALSE);
            $canvas->moveTo($B1x, $B1y, TRUE);
            $canvas->lineTo(2, -5, FALSE);

        } else{
            //едната стрелка
            $canvas->moveTo($A1x, $A1y, TRUE);
            $canvas->lineTo(5, 2, FALSE);
            $canvas->moveTo($A1x, $A1y, TRUE);
            $canvas->lineTo(5, -2, FALSE);
            
            //другата стрелка
            $canvas->moveTo($B1x, $B1y, TRUE);
            $canvas->lineTo(-5, 2, FALSE);
            $canvas->moveTo($B1x, $B1y, TRUE);
            $canvas->lineTo(-5, -2, FALSE);
            
        }
    }
}