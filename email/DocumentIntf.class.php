<?php



/**
 * Интерфейс на документ който може да бъде изпращан по електронна поща
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс на документ който може да бъде изпращан по електронна поща
 */
class email_DocumentIntf extends doc_DocumentIntf
{
	/**
     * Връща тялото по подразбиране на имейл-а
     */
    function getDefaultEmailBody($originId, $forward = FALSE)
    {
        return $this->class->getDefaultEmailBody($originId, $forward);
    }
}
