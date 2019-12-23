<?php


/**
 * Чертае Правоъгълник
 */
class cad2_Rectangle extends cad2_Shape
{
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    public $interfaces = 'cad2_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    public $title = 'Елементи » Правоъгълник';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('x', 'float', 'caption=X');
        $form->FLD('y', 'float', 'caption=Y');
        $form->FLD('w', 'float', 'caption=Широчина');
        $form->FLD('h', 'float', 'caption=Височина');
        
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
        
        $svg->setAttr('stroke', $stroke);
        $svg->setAttr('stroke-width', $strokeWidth);
        $svg->setAttr('fill', $fill);
        $svg->setAttr('fill-opacity', $opacity);
        
        $svg->startPath();
        
        $svg->moveTo($x, $y, true);
        
        self::draw($svg, $w, $h);
    }
    
    
    /**
     * Метод за ичертаване на правоъгълник
     */
    public static function draw($svg, $w, $h)
    {
        $svg->lineTo($w, 0);
        $svg->lineTo(0, $h);
        $svg->lineTo(-$w, 0);
        $svg->lineTo(0, -$h);
    }
}
