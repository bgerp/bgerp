<?php


/**
 *
 *
 * @category  bgerp
 * @package   hikvision
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cvc_Exception extends core_exception_Expect
{
    /**
     * Генерира exception от съотв. клас, в случай че зададеното условие не е изпълнено
     *
     * @param bool   $condition
     * @param string $message
     * @param array  $options
     *
     * @throws cvc_Exception
     */
    public static function expect($condition, $message, $options = array())
    {
        if (!(boolean) $condition) {
            throw new cvc_Exception($message, $options);
        }
    }
}
