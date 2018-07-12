<?php


/**
 * Интерфейс за опаковка на вътрешна страница
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_page_WrapperIntf
{
    /**
     * Инстанция на обекта
     */
    public $class;
    
    
    /**
     * Подготвя шаблона на опаковката
     */
    public function prepare()
    {
        $this->class->prepare();
    }
}
