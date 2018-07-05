<?php


/**
 * Изпращане на факс чрез efax.com
 *
 * @category  bgerp
 * @package   efax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class efax_Sender
{
    
    
    /**
     * Интерфейсния клас за изпращане на факс
     */
    public $interfaces = 'email_SentFaxIntf';
    
    
    
    public $title = 'EFax.com';
    
    
    /**
     * Интерфейсния метод за изпращане на факс
     */
    public function sendFax($rec, $faxTo)
    {
        $conf = core_Packs::getConfig('efax');
        
        //Очаква да има факс на изпращача
        expect(($faxSender = $conf->EFAX_SENDER_BOX), 'Не сте дефинирали факс на изпращача.');
        
        //Броя на прикачените файлове и документи
        $attachCnt = count($rec->documentsFh) + count($rec->attachmentsFh);
        
        expect(!($attachCnt > $conf->MAX_ALLOWED_ATTACHMENTS_IN_FAX), 'Надвишили сте максималния брой за прикачени файлове: ' . $conf->MAX_ALLOWED_ATTACHMENTS_IN_FAX);
        
        //Енкодинг на факса
        $options['encoding'] = 'utf-8';
        
        //Дали да се добави манипулатора на нишката пред заглавието
        $options['no_thread_hnd'] = 'no_thread_hnd';

        //Указва дали е факс или не
        $options['is_fax'] = 'is_fax';
        
        //Факс номера се преобразува в имейл
        $recipientFaxEmail = $faxTo . '@efaxsend.com';
        
        // Ако вместо id на факса дефинираме имейл
        if (!is_numeric($conf->EFAX_SENDER_BOX)) {
            
            //Вземаме id' то на получателя
            $faxSender = email_Inboxes::fetchField("#email='${faxSender}'");
            
            //Очакваме да има такъв имейл
            expect($faxSender, 'Няма такъв имейл в системата Ви.');
        }
        
        //Изпращаме факса
        $res = email_Sent::sendOne($faxSender, $recipientFaxEmail, $rec->subject, $rec, $options);
        
        return $res;
    }
}
