<?php 


/**
 * Статии в системата
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Articles extends core_Master
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
    var $title = "Статии";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Статия";
    
    
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
    var $canList = 'ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';


    /**
     * 
     */
    var $canSingle = 'ceo';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutArticles.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/article.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Art';
    
    
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
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'user';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.1|Общи"; 
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%,changable');
        $this->FLD('body', 'richtext(rows=10,bucket=Notes)', 'caption=Статия,mandatory,changable');
        $this->FLD('version', 'varchar', 'caption=Версия,input=none,width=100%,changable');
        $this->FLD('subVersion', 'int', 'caption=Подверсия,input=hidden,changable');
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако редактраиме записа
            if (($id = $form->rec->id) &&  ($form->rec->state != 'draft')) {
                
                // Вземаме записа
                $rec = $mvc->fetch($id);
                
                // Ако няма промени
                if (($form->rec->subject == $rec->subject) && ($form->rec->body == $rec->body)) {
                    
                    // Сетваме грешка
                    $form->setError(array('subject', 'body'), 'Нямате промени');
                }
            }
        }
        
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако няма версия
            if (!$form->rec->version) {
                
                // Стойността по подразбране
                $form->rec->version = 0;
            }
            
            // Ако няма подверсия
            if (!$form->rec->subVersion) {
                
                // Стойността по подразбиране
                $form->rec->subVersion = 1;
            }
        }
    }
    
    
    /**
     * Прихваща извикването на AfterInputChanges в change_Plugin
     * 
     * @param core_MVc $mvc
     * @param object $oldRec - Стария запис
     * @param object $newRec - Новия запис
     */
    function on_AfterInputChanges($mvc, $oldRec, $newRec)
    {
        // Ако има промени, тогава да се променя подверсията "версията"
//        if ($oldRec->subject != $newRec->subjet) || ($oldRec->body != $newRec->body)

        // Ако не е променяна версията
        if ($oldRec->version == $newRec->version) {
            
            // Увеличаваме подверсията
            $newRec->subVersion++;
        } else {
            
            // Ако е променяна версията, подверсията да е 1
            $newRec->subVersion = 1;
        }
        
        // Версията и подверсията на стария запис и новия да са еднакви
        $oldRec->version = $newRec->version;
        $oldRec->subVersion = $newRec->subVersion;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Екшъна
        $action = log_Documents::ACTION_CHANGE;
        
        // Вземаме записите
        $recsArr = log_Documents::getRecs($data->rec->containerId, $action);
        
        // Ако има записи, добавяме бутон за версиите
        if (count($recsArr[0]->data->{$action})) {
            
            $data->toolbar->addBtn('Версии', array($mvc, 'versions', $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/clipboard_sign.png, row=1');
        }
    }
    
    
    /**
     * 
     */
    function act_Versions()
    {
        requireRole('user');
        
    	$text = tr('В процес на разработка');
    	$underConstructionImg = "<h2>$text</h2><img src=". sbf('img/under_construction.png') .">";

        return $this->renderWrapping($underConstructionImg);
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {
        // Показва версията и подверсията
        $data->row->versionInfo = "{$data->row->version}.{$data->row->subVersion}";
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
     * Добавянето на статия не променя състоянието на треда
     */
    static function getThreadState($id)
    {
        return NULL;
    }
}
