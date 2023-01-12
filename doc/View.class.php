<?php 


/**
 * Документ "Изглед"
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_View extends core_Master
{


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
    /**
     * Заглавие
     */
    public $title = 'Изгледи';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Изглед';


    /**
     * Полета, които се показват в листови изглед
     */
    public $listFields = 'id,tplId,clsId,createdOn,createdBy,modifiedOn,modifiedBy,activatedOn,activatedBy';


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
    public $canAdd = 'officer';
    
    
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
     * Кой може да разглежда сингъла на документа
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'doc/tpl/SingleLayoutViews.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/ui_saccordion.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'V';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'tplId, clsId, dataId';


    /**
     * Позволява премахването на бланката при отпечатване
     *
     * @see bgerp_plg_Blank
     */
    public $allowPrintingWithoutBlank = true;

    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('clsId', 'class', 'caption=Клас, input=hidden, silent');
        $this->FLD('dataId', 'int', 'caption=Документ, input=hidden, silent');
        $this->FLD('tplId', 'key(mvc=doc_TplManager, select=name)', 'caption=Изглед, silent, removeAndRefreshForm=docHtml');
        $this->FLD('body', 'blob(serialize,compress)', 'caption=Изглед,input=none');

        $this->FNC('docHtml', 'html(rows=10, size=1000000)', 'caption=Преглед, input');
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
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     *
     * @param int $id
     */
    public function getDocumentRow_($id)
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
     * @param stdClass      $res
     * @param stdClass      $data
     */
    protected static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        Request::setProtected(array('clsId', 'dataId'));

        // Да не се рендира оригиналния документ
        Mode::set('stopRenderOrigin', true);
    }


    /**
     * Рендира изгледа на документа за шаблона
     *
     * @param $classId
     * @param $dataId
     * @param $tplId
     *
     * @return false|string
     */
    protected static function getDocumentContentFor($classId, $dataId, $tplId)
    {
        $clsInst = cls::get($classId);
        Mode::push('docView', true);
        Mode::push('text', 'xhtml');
        Mode::push('noBlank', true);

        $tplManagerLg = false;
        if ($tplId) {
            $lg = doc_TplManager::fetchField($tplId, 'lang');

            if ($lg) {
                Mode::push('tplManagerLg', $lg);
                $tplManagerLg = true;
                core_Lg::push($lg);
            }
        }

        $options = null;
        if ($dataId) {
            $dRec = $clsInst->fetch($dataId);
            $dRec->template = $tplId;
            $options = new stdClass();
            $options->rec = $dRec;
        }

        $dData = $clsInst->prepareDocument($dataId, $options);

        $dData->rec->template = $tplId;

        $dData->noToolbar = true;

        $res = $clsInst->renderDocument($dataId, $dData);

        $html = $res->getContent();

        $css = doc_PdfCreator::getCssStr($html);
        $html = doc_PdfCreator::removeFormAttr($html);
        $html = '<div id="begin">' . $html . '<div id="end">';
        $CssToInlineInst = cls::get(csstoinline_Setup::get('CONVERTER_CLASS'));
        $html = $CssToInlineInst->convert($html , $css);
        $html = str::cut($html, '<div id="begin">', '<div id="end">');

        if ($tplManagerLg) {
            core_Lg::pop();
            Mode::pop('tplManagerLg');
        }

        Mode::pop('noBlank');
        Mode::pop('text');
        Mode::pop('docView');

        return $html;
    }
    
    
    /**
     * Извиква се преди подготовката на формата за редактиране/добавяне $data->form
     *
     * @param crm_Locations $mvc
     * @param stdClass      $res
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        $form = $data->form;

        $tplArr = doc_TplManager::getTemplates($rec->clsId);
        
        expect($tplArr || cls::get($rec->clsId)->createView);

        if (empty($tplArr)) {
            $data->form->setField('tplId', 'input=none');
        } else {
            $data->form->setOptions('tplId', $tplArr);
        }

        $oRec = $force = false;
        if ($form->rec->id) {
            $oRec = $mvc->fetch($form->rec->id);

            $data->form->input('tplId', true);
            if ($oRec->tplId != $form->rec->tplId) {
                $force = true;
            }
        }

        if ($form->rec->id && !$force) {
            $form->rec->docHtml = $form->rec->body;
        } else {
            $form->setDefault('tplId', key($tplArr));

            $form->rec->docHtml = $mvc->getDocumentContentFor($form->rec->clsId, $form->rec->dataId, $form->rec->tplId);
        }

        if ($form->rec->docHtml && !$form->rec->id) {
            // Добавяме клас, за да може формата да застане до привюто на документа/файла
            $className = '';
            if (Mode::is('screenMode', 'wide')) {
                $className = ' floatedElement ';
            }

            $data->form->layout = $data->form->renderLayout();
            $tpl = new ET("<div class='preview-holder{$className}'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Преглед') . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");

            $tpl->append($form->rec->docHtml, 'DOCUMENT');

            $data->form->layout->append($tpl);
        }

        $haveRightForOrigin = false;
        if ($form->rec->clsId) {
            $clsInst = cls::get($form->rec->clsId);
            if ($clsInst->haveRightFor('single', $form->rec->dataId)) {
                $haveRightForOrigin = true;
            }
        }

        $btnName = 'Цял екран';
        if (!$rec->id) {
            $data->form->setField('docHtml', 'input=hidden');
            $btnName = 'Редактиране';
        }

        if (($oRec && $mvc->haveRightFor('edit', $oRec)) || $haveRightForOrigin) {
            $data->form->toolbar->addSbBtn($btnName, 'fullView', 'id=fullView, order=10.00029', 'ef_icon = img/16/doc_resize.png,title=Запис и редакция на изгледа');
        }
    }


    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if (isset($rec->docHtml)) {
            $rec->body = $rec->docHtml;
        }
    }


    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        if ($data->form->rec->_toFullView) {
            $data->retUrl = toUrl(array($mvc, 'editDocument', $data->form->rec->id));
        }
    }


    /**
     * Екшън за редактиране на HTML файл
     *
     * @return Redirect|core_ET
     */
    public function act_editDocument()
    {
        $id = Request::get('id', 'int');

        expect($id);

        $rec = $this->fetch($id);

        expect($rec && $this->haveRightFor('single', $rec));

        $retUrl = getRetUrl();

        if (empty($retUrl)) {
            $retUrl = array($this, 'single', $id);
        }

        $data = Request::get('data');
        if ($data) {
            expect($this->haveRightFor('edit', $rec));

            $rec->docHtml = $data;

            $this->save($rec);

            return new Redirect($retUrl, 'Успешно обновихте документа');
        }

        $form = cls::get('core_Form');

        $html = 'html(tinyToolbars=fullscreen print, tinyFullScreen)';

        if ($this->haveRightFor('edit', $rec)) {
            $urlArr = array($this, 'editDocument', $id);
            $localUrl = toUrl($urlArr, 'local');
            $localUrl = urlencode($localUrl);
            $html = "html(tinyToolbars=fullscreen print save, tinyFullScreen, tinySaveCallback={$localUrl})";
        }

        $form->FNC('html', $html, 'input, caption=HTML', array('attr' => array('id' => 'editor')));
        $form->setDefault('html', $rec->body);

        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $form->title = 'Редактиране на HTML файл';

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty');

        // Връщаме съдържанието
        return $form->renderHtml();
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if ($form->cmd == 'fullView') {
                $form->rec->_toFullView = true;

                if (!$form->rec->id) {
                    status_Messages::newStatus('Документът е записан');
                } else {
                    status_Messages::newStatus('Документът е обновен');
                }
            }
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
            
            if ($rec->tplId) {
                $row->tplId = doc_TplManager::getLinkToSingle($rec->tplId, 'name');
            }
        } else {
            unset($row->tplId);
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     *
     * @param doc_View    $mvc
     * @param string|NULL $res
     * @param stdClass    $rec
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $body = strip_tags($rec->body);
        
        $res .= ' ' . plg_Search::normalizeText($body);
    }
    
    
    /**
     * Модификация на ролите, които могат да видят избраната тема
     *
     * @param doc_View    $mvc
     * @param string      $requiredRoles
     * @param string      $action
     * @param string|NULL $rec
     * @param string|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add') {
            if ($rec) {
                if (!$rec->clsId || !$rec->dataId) {
                    $requiredRoles = 'no_one';
                } else {
                    if (!cls::load($rec->clsId, true)) {
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
