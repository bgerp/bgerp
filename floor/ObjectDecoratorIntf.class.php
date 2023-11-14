<?php


/**
 * Интерфейс за декорация на обекти в floor_Plans
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
 * @title     Интерфейс за декорация на обекти в floor_Plans
 */
class floor_ObjectDecoratorIntf
{
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Украсява обекта
     *
     * @param mixed $id - ид/запис на обекта
     * @param array $styleArr
     * @param double $quantity - За какво количество
     * 
     * @return double|NULL - дефолтната единична цена
     */
    public function decorate($name, $styleArr, $html)
    {

        return $this->class->decorate($name, $styleArr, $html);
    }
}
