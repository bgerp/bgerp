<?php



/**
 * Клас 'doc_ContragentDataIntf' - Интерфейс за данните на адресанта
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ContragentDataIntf
{
    
    
    /**
     * Връща данните на получателя
     */
    function getContragentData($id, $email = NULL)
    {
        return $this->class->getContragentData($id, $email);
    }
    
    
    /**
     * Връща тялото по подразбиране на имейла
     */
    function getDefaultEmailBody($originId)
    {
        return $this->class->getDefaultEmailBody($originId);
    }
}