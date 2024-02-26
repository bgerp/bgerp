<?php



/**
 * Тестов декоратор на обекти в floor_Plans
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Тестов декоратор за floor_Plans
 */
class floor_TestDecorator extends core_BaseClass
{
    
    /**
     * Интерфейси, поддържани от този клас
     */
    public $interfaces = 'floor_ObjectDecoratorIntf';
    
    
    
    /**
     * Интерфейсен метод на floor_ObjectDecoratorIntf
     *
     * @return array $result
     */
    public static function decorate($name, &$styleArr, &$html)
    {
        if(substr(md5( (floor(time()/3) ) . $name), -1) > '5') {
            $temp = rand(40, 56)/2;
            $red = min(255, 10 * $temp - 20);
            $blue = min(255, 415 - 8 * $temp);
            $html = "<div style='background-color:red;color:white;display:inline;'>{$html}</div>";
            $color = substr(md5( (floor(time()/3) ) . $name), -6);
            
            $styleArr['background-color'] = "background-color:rgba({$red}, {$blue}, {$blue}, 0.3);";
        }
    }
    
}
