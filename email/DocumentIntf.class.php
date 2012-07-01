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
     * Прикачените към документ файлове
     *
     * @param int $id ид на документ
     * @return array
     */
    public function getEmailAttachments($id)
    {
        return $this->class->getEmailAttachments($id);
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
    
    
    /**
     * До кой имейл или списък с имейли трябва да се изпрати писмото
     *
     * @param int $id ид на документ
     */
    public function getDefaultEmailTo($id)
    {
        return $this->class->getDefaultEmailTo($id);
    }
    
    
    /**
     * Адреса на изпращач по подразбиране за документите от този тип.
     *
     * @param int $id ид на документ
     * @return int key(mvc=email_Inboxes) пощенска кутия от нашата система
     */
    public function getDefaultBoxFrom($id)
    {
        return $this->class->getDefaultBoxFrom($id);
    }
    
    
    /**
     * Писмото (ако има такова), в отговор на което е направен този постинг
     *
     * @param int $id ид на документ
     * @return int key(email_Incomings) NULL ако документа не е изпратен като отговор
     */
    public function getInReplayTo($id)
    {
        return $this->class->getInReplayTo($id);
    }
}