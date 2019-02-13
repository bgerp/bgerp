<?php


/**
 * Интерфейс за връзка с везни
 *
 * @category  bgerp
 * @package   wscales
 *
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_FiscPrinter
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Връща JS функция, която да се изпълни и да отпечата бележката
     *
     * @param stdClass $pRec
     * @param array    $paramsArr
     *
     * @return string
     */
    public function getJs($pRec, $paramsArr)
    {
        return $this->class->getJs($pRec, $paramsArr);
    }
    
    
    /**
     * Връща JS функция, която да провери дали има връзка с устройството
     *
     * @param stdClass $pRec
     *
     * @return string
     */
    public function getJsIsWorking($pRec)
    {
        return $this->class->getJsIsWorking($pRec);
    }
}
