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
    public static function decorate($name, &$stuleArr, &$html)
    {
        if(substr(md5( (floor(time()/3) ) . $name), -1) != '5') {
            $html = "<div style='background-color:red;color:white;display:inline;'>{$html}</div>";
        }
    }
    
}
