<?php


/**
 *
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class openai_Exception extends core_exception_Expect
{
    /**
     * Генерира exception от съотв. клас, в случай че зададеното условие не е изпълнено
     *
     * @param bool   $condition
     * @param string $message
     * @param array  $options
     *
     * @throws openai_Exception
     */
    public static function expect($condition, $message, $options = array())
    {
        if (!(boolean) $condition) {
            throw new openai_Exception($message, $options);
        }
    }
}
