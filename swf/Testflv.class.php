<?php


/**
 * Тестов клас за swfObject 2
 *
 *
 * @category  vendors
 * @package   swf
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class Testflv
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'swfObject';
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_Test()
    {
        $this->swfObject->setSwfFile('test.swf');
        $this->swfObject->setAlternativeContent('<h2>Нямате флаш или ДжаваСкрипт</h2>');
        $this->swfObject->setWidth(300);
        $this->swfObject->setHeight(120);
        
        return $this->swfObject->getContent();
    }
}
