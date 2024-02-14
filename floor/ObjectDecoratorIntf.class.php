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
     * Връща масив с ид-та => наименования на всички планове
     */
    public function getPlans()
    {
        return $this->class->describe();
    }

    /**
     * Връща всички обекти, които могат да се покажат на този план
     *
     * @return array(objId => $rec); $rec->name; $rec->type
     */
    public function describe($planId)
    {
        return $this->class->describe($planId);
    }


    /**
     * Връща дефолтната единична цена отговаряща на количеството
     *
     * @param mixed $name - ид/запис на обекта
     * @param styleArr  
     * 
     * @return double|NULL - дефолтната единична цена
     */
    public function decorate($name, $styleArr, $html)
    {
        return $this->class->decorate($name, $styleArr, $html);
    }
}
