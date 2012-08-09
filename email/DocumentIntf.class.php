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
 */
class email_DocumentIntf extends doc_DocumentIntf
{
    
    
    /**
     * Текстов вид (plain text) на документ при изпращането му по имейл
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string plain text
     */
    public function getEmailText($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return $this->class->getEmailText($id, $emailTo, $boxFrom);
    }
    
    
    /**
     * HTML вид на документ при изпращането му по имейл
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string plain text
     */
    public function getEmailHtml($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return $this->class->getEmailHtml($id, $emailTo, $boxFrom);
    }


    /**
     * Какъв да е събджекта на писмото по подразбиране
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string
     */
    public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return $this->class->getDefaultSubject($id, $emailTo, $boxFrom);
    }
    
    
}