<?php 


/**
 * Документ "Изглед"
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_View extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
    /**
     * Заглавие
     */
    var $title = "Изгледи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Изглед";
    
    
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
    var $canAdd = 'officer';
    
    
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
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutViews.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/ui_saccordion.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'V';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'tplId, clsId, dataId';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('clsId', 'class', 'caption=Клас, input=hidden, silent');
        $this->FLD('dataId', 'int', 'caption=Документ, input=hidden, silent');
        $this->FLD('tplId', 'key(mvc=doc_TplManager, select=name)', 'caption=Изглед, silent');
        $this->FLD('body', 'blob(serialize,compress)', 'caption=Изглед,input=none');
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
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     * 
     * @param int $id
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $clsInst = cls::get($rec->clsId);
        $dRow = $clsInst->getDocumentRow($rec->dataId);
        $row = new stdClass();
        
        $row->title = $dRow->title;
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $dRow->recTitle;
        
        return $row;
    }
    
    
    /**
     * Извиква се преди подготовката на формата за редактиране/добавяне $data->form
     * 
     * @param crm_Locations $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    protected static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        Request::setProtected(array('clsId', 'dataId'));
    }
    
    
    /**
     * Извиква се преди подготовката на формата за редактиране/добавяне $data->form
     *
     * @param crm_Locations $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        $tplArr = doc_TplManager::getTemplates($rec->clsId);
        
        expect($tplArr);
        
        $data->form->setOptions('tplId', $tplArr);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $clsInst = cls::get($form->rec->clsId);
            expect($clsInst->haveRightFor('single', $form->rec->dataId));
            
            Mode::push('text', 'xhtml');
            Mode::push('noBlank', TRUE);
            
            $tplManagerLg = FALSE;
            if ($form->rec->tplId) {
                $lg = doc_TplManager::fetchField($form->rec->tplId, 'lang');
                
                if ($lg) {
                    Mode::push('tplManagerLg', $lg);
                    $tplManagerLg = TRUE;
                    core_Lg::push($lg);
                }
            }
            
            $data = $clsInst->prepareDocument($form->rec->dataId);
            $data->rec->template = $form->rec->tplId;
            
            $data->noToolbar = TRUE;
            
            $res  = $clsInst->renderDocument($form->rec->dataId, $data);
            
            $form->rec->body = $res->getContent();
            
            if ($tplManagerLg) {
                core_Lg::pop();
                Mode::pop('tplManagerLg');
            }
            
            Mode::pop('noBlank');
            Mode::pop('text');
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->body = $rec->body;
        
        if (!Mode::isReadOnly()) {
            $clsInst = cls::get($rec->clsId);
            $row->subject = $clsInst->getLinkToSingle($rec->dataId);
            $row->tplId = doc_TplManager::getLinkToSingle($rec->tplId, 'name');
        } else {
            unset($row->tplId);
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     * 
     * @param doc_View $mvc
     * @param string|NULL $res
     * @param stdObject $rec
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $body = strip_tags($rec->body);
        
        $res .= " " . plg_Search::normalizeText($body);
    }


    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 * 
     * @param doc_View $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param string|NULL $rec
     * @param string|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
	    if ($action == 'add') {
	        if ($rec) {
	            if (!$rec->clsId || !$rec->dataId) {
	                $requiredRoles = 'no_one';
	            } else {
	                if (!cls::load($rec->clsId, TRUE)) {
	                    $requiredRoles = 'no_one';
	                } else {
	                    $inst = cls::get($rec->clsId);
	                    if (!$inst->haveRightFor('single', $rec->dataId)) {
	                        $requiredRoles = 'no_one';
	                    }
	                }
	            }
	            
	            // Ако няма достъп до сингъла на нишката, да не може да създава изглед
	            if (($requiredRoles != 'no_one') && ($rec->originId)) {
	                if ($threadId = doc_Containers::fetchField($rec->originId, 'threadId')) {
	                    if (!doc_Threads::haveRightFor('single', $threadId)) {
	                        $requiredRoles = 'no_one';
	                    }
	                }
	            }
	        }
	    }
	}
}
