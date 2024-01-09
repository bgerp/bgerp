<?php


/**
 * Интерфейс за връзка с Банков терминал
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Ivelin Dimov
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bank_interface_POS extends peripheral_DeviceIntf
{

    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;


    /**
     * Изпраща сумата към POS
     *
     * @param stdClass $pRec
     * @param double $amount
     * @param string|null $port
     * @return null|string
     */
    public function sendAmount($pRec, $amount, $port = null)
    {
        return $this->class->sendAmount($pRec, $amount, $port);
    }
}