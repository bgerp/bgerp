<?php 


/**
 * Коментари всистема
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Comments extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'subject, body';
    
    
    /**
     * Заглавие
     */
    var $title = "Коментари";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Коментар";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'comment';
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';


    /**
     * 
     */
    var $canSingle = 'ceo';
    

    /**
     *
     */
    var $canActivate = 'user';
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutComments.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/comment.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'C';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'subject, body';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, subject, sharedUsers=Споделяне, createdOn, createdBy';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=10,bucket=Comments)', 'caption=Коментар,mandatory');
        $this->FLD('sharedUsers', 'keylist(mvc=core_Users,select=nick)', 'caption=Споделяне->Потребители');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        
        //Ако добавяме нови данни
        if (!$rec->id) {
            
            //Ако имаме originId и добавяме нов запис
            if ($rec->originId) {
                //Добавяме в полето Относно отговор на съобщението
                $oDoc = doc_Containers::getDocument($rec->originId);
                $oRow = $oDoc->getDocumentRow();
                $rec->subject = 'За: ' . html_entity_decode($oRow->title);
            }
        }
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {
        if (Mode::is('text', 'plain')) {
            // Форматиране на данните в $data->row за показване в plain text режим
            $width = 80;
            $row = $data->row;
            $row->body = type_Text::formatTextBlock($row->body, $width, 0);
        }
        
        $data->row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
        $data->row->headerType = tr('Коментар');
    }
    
    
    /**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     */
    static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        if (Mode::is('text', 'plain')) {
            //Ако сме в текстов режим, използваме txt файла
            $tpl = new ET('|*' . getFileContent('doc/tpl/SingleLayoutComments.txt'));
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        $row = new stdClass();
        
        $row->title = $subject;
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    

    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     * Добавянето на коментар не променя състоянието на треда
     */
    static function getThreadState($id)
    {
        return NULL;
    }


    /**
     * Потребителите, с които е споделен този документ
     *
     * @return string keylist(mvc=core_Users)
     * @see doc_DocumentIntf::getShared()
     */
    static function getShared($id)
    {
        return static::fetchField($id, 'sharedUsers');
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Comments', 'Прикачени файлове в коментарите', NULL, '300 MB', 'user', 'user');
    }
}
