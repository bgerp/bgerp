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
    
    public $canEditpsingle = 'user';
    public $canAddpsingle = 'user';
    /**
     * Шаблон (ET) за заглавие на перо
     */
    public $recTitleTpl = '[#subject#]';
    
    
    /**
     * Дали да се споделя създадели на оригиналния документ
     */
    public $autoShareOriginCreator = TRUE;
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, colab_CreateDocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'user';
    
    
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
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canSingle = 'powerUser';
    
    
	/**
     * Кой може да променя активирани записи
     * @see plg_Change
     */
    var $canChangerec = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin, plg_Clone';
    
    
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
     * Групиране на документите
     */
    var $newBtnGroup = "1.1|Общи";
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'subject, body, sharedUsers';
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = TRUE;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%,input=hidden,reduceText');
        $this->FLD('body', 'richtext(rows=10,bucket=Comments)', 'caption=Коментар,mandatory');
        $this->FLD('visibleForPartners', 'enum(no=Не,yes=Да)', 'caption=Споделяне->С партньори, input=none');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Да се цитират документа, ако не се редактира
        if (!$data->form->rec->id) { 
            $data->form->fields['body']->type->params['appendQuote'] = 'appendQuote';
        }
        
        $rec = $data->form->rec;
        
        //Ако добавяме нови данни
        if (!$rec->id) {
            
            $haveOrigin = FALSE;
            
            //Ако имаме originId
            if ($rec->originId) {
                
                $cid = $rec->originId;
                $haveOrigin = TRUE;
            } elseif ($rec->threadId) {
                
                // Ако добавяме коментар в нишката
                $cid = doc_Threads::fetchField($rec->threadId, 'firstContainerId');
            }
            
            if ($cid && $data->action != 'clone') {
                
                //Добавяме в полето Относно отговор на съобщението
                $oDoc = doc_Containers::getDocument($cid);
                $oRow = $oDoc->getDocumentRow();
                $for = tr('|За|*: ');
                $rec->subject = $for . html_entity_decode($oRow->title, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                
                if ($haveOrigin) {
                    $rec->body = $for . '#' .$oDoc->getHandle() . "\n" . $rec->body;
                }
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
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
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
     * 
     * @param integer $id
     * 
     * @return NULL|string
     */
    static function getThreadState($id)
    {
	    $res = NULL;
	    
	    if (core_Packs::isInstalled('colab')) {
	        $rec = self::fetch($id);
	        if (core_Users::haveRole('partner', $rec->createdB)) {
	            $res = 'opened';
	        } elseif (core_Users::isPowerUser($rec->createdBy) && self::isVisibleForPartners($rec)) {
	            $res = 'closed';
	        }
	    }
	    
	    return $res;
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
    
    
    /**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'add' && empty($rec)){
            $requiredRoles = 'no_one';
        }
    }
}
