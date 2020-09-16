<?php


/**
 * Интерфейс на документ който може да бъде изпращан по електронна поща
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс на документ който може да бъде изпращан по електронна поща
 */
class email_DocumentIntf extends doc_DocumentIntf
{
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        return $this->class->getDefaultEmailBody($id, $forward = false);
    }
    
    
    /**
     * Връща заглавието на имейла
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string
     */
    public function getDefaultEmailSubject($id, $forward = false)
    {
        return $this->class->getDefaultEmailSubject($id, $forward = false);
    }
    
    
    /**
     * Какъв е дефолтния имейл за изпращане
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string
     */
    public function getDefaultEmailTo($id, $forward = false)
    {
        return $this->class->getDefaultEmailTo($id, $forward = false);
    }
}
