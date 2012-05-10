<?php


/**
 * Факс на изпращача.
 * Трябва да е дефиниран, като допустим в efax.com, за да може да изпращаме факс
 */
defIfNOt('FAX_SENDER_BOX', 'team@efax.bgerp.com');


/**
 * Плъгин за изпращане на факс чрез efax.com
 *
 * @category  bgerp
 * @package   fax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fax_EFaxPlg extends core_Plugin
{
    
    
    /**
     * Изпълнява се при извикване на fax_Sent::SendFax.
     * Изпраща факса.
     *
     * @param $mvc
     * @param $res
     * $param $data - Данните на факса
     * ->containerId int - key(mvc=doc_Containers) кой документ се изпраща
     * ->threadId int - key(mvc=doc_Threads) от коя нишка е документа, който се изпраща
     * ->faxArr array - Масив с всички факсове до които ще се изпраща
     * ->subject sting Поле "Относно: "
     * ->body Обект или масив със съдържанието на писмото. Полетата му са:
     * body->html string - HTML частта на писмото
     * body->text string текстовата част на писмото
     * body->attachments array масив с прикачените файлове
     * body->documents array масив с прикачените документи
     * 
     * @return boolean $res - TRUE, ако всичко е минало добре
     */
    function on_BeforeSendFax($mvc, &$res, $data)
    {
        //Очаква да има факс на изпращача
        expect(($faxSender = FAX_SENDER_BOX), 'Не сте дефинирали факс на изпращача.');
        
        //Енкодинг на факса
        $options['encoding'] = 'utf-8';
//        $options['no_thread_hnd'] = 'no_thread_hnd';
        
        //Преобразуваме всеки факс номер към имейл
        foreach ((array)$data->faxArr as $faxNumber) {
            
            //Факса на получателя
            $faxEmail = $faxNumber . '@efaxsend.com';
            
            //Факсовете на получателите
            $recipientFax .= ($recipientFax) ? ', ' . $faxEmail : $faxEmail;
        }

        //Ако не сме дефинирали id на факса, а имейл
        if (!is_numeric(FAX_SENDER_BOX)) {
            
            //Вземаме id' то на получателя
            $faxSender = email_Inboxes::fetchField("#email='$faxSender'");
            
            //Очакваме да има такъв имейл
            expect($faxSender, 'Няма такъв имейл в системата Ви.');
        }
        
        //Изпращаме факса
        $res = email_Sent::send($data->containerId, $data->threadId, $faxSender, $recipientFax, $data->subject, $data->body, $options, TRUE);
    }
}