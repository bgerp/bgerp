<?php

 /**
 * Интерфейс
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_SentFaxIntf
{
    
    
    /**
     * Добавя скрипт за конвертиране на файлове
     */
    function sendFax($data, $fax)
    {
        return $this->class->sendFax($data, $fax);
    }
}