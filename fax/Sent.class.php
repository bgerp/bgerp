<?php


/**
 * Изпращане на факс
 *
 * @category  bgerp
 * @package   fax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class fax_Sent extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,fax_Wrapper, plg_RowTools';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Изпратени факсове";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, faxTo, attachments, documents, containerId, threadId, createdOn=Изпратено->на, createdBy=Изпратено->от, documents=Прикачени->Документи, attachments=Прикачени->Файлове';
           
    
    /**
     * Кой има право да го прочете?
     */
    var $canRead = 'admin';
    
    
    /**
     * КОМЕНТАР МГ: Никой не трябва да може да добавя или редактира записи.
     *
     * Всичко потребители трябва да могат да изпращат '$canSend' писма
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой има право да го отхвърли?
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да изпраща?
     */
    var $canSend = 'admin,fax';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('faxTo', 'drdata_PhoneType', 'input,caption=До,mandatory,width=785px');
        $this->FLD('attachments', 'keylist(mvc=fileman_files, select=name)', 'caption=Файлове,columns=4,input=none');
        $this->FLD('documents', 'keylist(mvc=fileman_files, select=name)', 'caption=Документи,columns=4,input=none');
        
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden,mandatory,caption=Нишка');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'input=hidden,caption=Документ,oldFieldName=threadDocumentId,mandatory');
    }
    
    
    /**
     * Изпраща факса
     *
     * @param int $containerId key(mvc=doc_Containers) кой документ се изпраща
     * @param int $threadId key(mvc=doc_Threads) от коя нишка е документа, който се изпраща
     * @param string $faxTo drdata_PhoneType Факса на получателя
     * @param string $subject Поле "Относно: "
     * @param mixed $body Обект или масив със съдържанието на писмото. Полетата му са:
     * ->html string - HTML частта на писмото
     * ->text string текстовата част на писмото
     * ->attachments array масив с прикачените файлове
     * ->documents array масив с прикачените документи
     */
    static function send($containerId, $threadId, $faxTo, $subject, $body)
    {
        //Масив с всички факс номера
        $faxArr = preg_split(fax_Outgoings::$pattern, $faxTo, NULL, PREG_SPLIT_NO_EMPTY);
        
        //Обект с данните на факса, който се използва от плъгина
        $data = new stdClass();
        $data->containerId = $containerId;
        $data->threadId = $threadId;
        $data->faxArr = $faxArr;
        $data->subject = $subject;
        $data->body = $body;
        
        //Резултата след изпращането на факса
        $status = fax_Sent::sendFax($data);
        
        //Ако сме изпратили успешно факса
        if ($status) {
            
            //Добавяме запис в изпратени
            $rec = new stdClass();
            $rec->containerId = $containerId;
            $rec->threadId = $threadId;
            $rec->faxTo = $faxTo;
            $rec->attachments = $body->attachments;
            $rec->documents = $body->documents;
            
            static::save($rec);
        }
        
        return $status;
    }
}
