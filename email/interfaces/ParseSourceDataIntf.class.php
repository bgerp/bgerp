<?php


/**
 * Интерфейс за извличаване на данните от имейли
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за извличаване на данните от имейли
 */
class email_interfaces_ParseSourceDataIntf
{


    /**
     * Връща данните на изпращача
     *
     * @param string $source
     *
     * @return false|string
     */
    public function getSenderData($source)
    {

        return $this->class->getSenderData($source);
    }
}
