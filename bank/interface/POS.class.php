<?php


/**
 * Интерфейс за връзка с банков терминал
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
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
     * Сол
     */
    const SALT = 'bank';


    /**
     * Връща JS за изпращане на стойност
     *
     * @param stdClass $pRec
     * @param string $funcName
     * @param string $resFuncName
     * @param string $errorFuncName
     *
     * @return core_ET
     */
    public function getJs($pRec, $funcName = 'getAmount', $resFuncName = 'getAmountRes', $errorFuncName = 'getAmountError')
    {

        return $this->class->getJs($pRec, $funcName, $resFuncName, $errorFuncName);
    }


    /**
     * Хеш за банковото плащане
     *
     * @param int $classId
     * @param int $objectId
     * @param null|int $cu
     * @return string
     */
    public static function getPaymentHash($classId, $objectId, $cu = null)
    {
        $cu = $cu ?? core_Users::getCurrent();

        return md5("{$classId}|{$objectId}|{$cu}|" . static::SALT);
    }


    /**
     * Заглавие на бутона за плащане
     *
     * @param stdClass $pRec
     * @return string
     */
    public function getBtnName($pRec)
    {
        return $this->class->getBtnName($pRec);
    }


    /**
     * Коя е функцията за изпращане на сумата
     *
     * @param stdClass|int $pRec
     *
     * @return string|null
     */
    public function getSendAmountFncName($pRec)
    {
        return $this->class->getSendAmountFncName($pRec);
    }
}