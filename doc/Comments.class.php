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
class doc_Comments extends embed_Manager
{
    
    
    /**
     * Интерфейс на драйверите
     */
    public $driverInterface = 'doc_ExpandCommentsIntf';
    
    
    /**
     * Шаблон (ET) за заглавие на перо
     */
    public $recTitleTpl = '[#subject#]';
    
    
    /**
     * Дали да се споделя създадели на оригиналния документ
     */
    public $autoShareOriginCreator = true;
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, colab_CreateDocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'user';
    
    
    /**
     * Заглавие
     */
    public $title = 'Коментари';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Коментар';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * @todo Чака за документация...
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой може да променя активирани записи
     * @see change_Plugin
     */
    public $canChangerec = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin, plg_Clone';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'doc/tpl/SingleLayoutComments.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/comment.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'C';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'subject, body';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, subject, sharedUsers=Споделяне, createdOn, createdBy';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '1.1|Общи';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'subject, body, sharedUsers';
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;
    
    
    /**
     * Описание на модела
     */
    public function description()
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
        return false;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param stdClass $data
     */
    public function prepareEditForm_($data)
    {
        if (!Request::get($this->driverClassField) && !Request::get('id')) {
            $dClsId = doc_ExpandComments::getClassId();
            
            Request::push(array($this->driverClassField => $dClsId));
        }
        
        return parent::prepareEditForm_($data);
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setField($mvc->driverClassField, 'input=hidden');
        
        // Да се цитират документа, ако не се редактира
        if (!$data->form->rec->id) {
            $data->form->fields['body']->type->params['appendQuote'] = 'appendQuote';
        }
        
        if (!$data->form->rec->id && !$data->form->rec->clonedFromId) {
            $detId = Request::get('detId', 'int');
            
            $originId = $data->form->rec->originId;
            
            if ($originId) {
                $doc = doc_Containers::getDocument($originId);
                
                $dRec = $doc->fetch();
                
                $doc->instance->requireRightFor('single', $dRec);
                
                $dData = $doc->instance->getDefaultDataForComment($dRec, $detId);
                
                if (!empty($dData)) {
                    foreach ($dData as $key => $val) {
                        if (!isset($val)) {
                            continue;
                        }
                        
                        $data->form->rec->{$key} = $val;
                    }
                }
            }
        }
    }
    
    
    /**
     *
     * @param core_Mvc   $mvc
     * @param NULL|array $res
     * @param stdClass   $rec
     * @param array      $otherParams
     */
    public function on_AfterGetDefaultData($mvc, &$res, $rec, $otherParams = array())
    {
        $res = arr::make($res);
        
        //Ако имаме originId
        if ($rec->originId) {
            $cid = $rec->originId;
        } elseif ($rec->threadId) {
            // Ако добавяме коментар в нишката
            $cid = doc_Threads::fetchField($rec->threadId, 'firstContainerId');
        }
        
        if ($cid) {
            //Добавяме в полето Относно отговор на съобщението
            $oDoc = doc_Containers::getDocument($cid);
            $oRow = $oDoc->getDocumentRow();
            $for = tr('|За|*: ');
            $res['subject'] = $for . html_entity_decode($oRow->title, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        }
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$data)
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
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        if (Mode::is('text', 'plain')) {
            //Ако сме в текстов режим, използваме txt файла
            $tpl = new ET('|*' . getFileContent('doc/tpl/SingleLayoutComments.txt'));
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
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
     * Изпълнява се след създаването на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Comments', 'Прикачени файлове в коментарите', null, '300 MB', 'user', 'user');
    }
}
