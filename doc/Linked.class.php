<?php


/**
 * Мениджър за свързани документи и файлове
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_Linked extends core_Manager
{
    /**
     * Възможните действия във формата
     */
    public static $actArr = array('' => '', 'linkDoc' => 'Връзка с документ', 'linkFile' => 'Връзка с файл', 'newDoc' => 'Нов документ');
    
    
    /**
     * Брой записи, които ще се гледат в bgerp_Recently - за подредба
     *
     * @var int
     */
    protected static $recentlyLimit = 20;
    
    
    /**
     * Дължина на селект полето
     *
     * @var int
     */
    protected static $titleLen = 50;
    
    
    /**
     * Заглавие
     */
    public $title = 'Свързани документи и файлове';
    
    
    /**
     * Сингъл заглавие
     */
    public $singleTitle = 'Свързани документи и файлове';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да оттегле?
     */
    public $canReject = 'powerUser';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper, plg_Created, plg_State, plg_Rejected, plg_RowTools2, plg_Modified';
    
    
    /**
     * Кой може да вижда свързаните документи и файлове
     */
    public $canViewlist = 'powerUser';
    
    
    /**
     * Кой може да добавя връзка
     */
    public $canAddlink = 'powerUser';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('outType', 'enum(doc=Документ,file=Файл)', 'caption=Изходен->Тип');
        $this->FLD('outVal', 'int', 'caption=Изходен->Стойност');
        $this->FLD('inType', 'enum(doc=Документ,file=Файл)', 'caption=Входящ->Тип');
        $this->FLD('inVal', 'int', 'caption=Входящ->Стойност');
        $this->FLD('comment', 'varchar', 'caption=Пояснения');
        $this->FLD('actType', 'varchar(64)', 'caption=Действие, input=none');
        $this->FLD('state', 'enum(active=Активно, rejected=Оттеглено)', 'caption=Състояние, input=none');
        
        $this->setDbUnique('outType, outVal, inType, inVal');
        $this->setDbIndex('outType, outVal');
        $this->setDbIndex('inType, inVal');
    }
    
    
    /**
     * Помощна функция за добавяне на връзка
     *
     * @param int         $outVal  - връзка от - източника/родителя - изходящата връзка
     * @param int         $inVal   - връзка към - детето - входящата връзка
     * @param string      $outType
     * @param string      $inType
     * @param null|string $comment
     *
     * @return int
     */
    public static function add($outVal, $inVal, $outType = 'doc', $inType = 'doc', $comment = null)
    {
        $rec = new stdClass();
        $rec->outType = $outType;
        $rec->outVal = $outVal;
        $rec->inType = $inType;
        $rec->inVal = $inVal;
        $rec->comment = $comment;
        $rec->state = 'active';
        
        $sId = self::save($rec, null, 'IGNORE');
        
        return $sId;
    }
    
    
    /**
     * Връща всички записи за подадените типове
     *
     * @param string $type
     * @param int    $id
     * @param bool   $showRejecte
     * @param float  $limit
     *
     * @return array
     */
    public static function getRecsForType($type, $id, $showRejecte = true, $limit = 1000)
    {
        $query = self::getQuery();
        
        if (!$showRejecte) {
            $query->where("#state != 'rejected'");
        }
        
        $query->limit($limit);
        
        $query->setUnion(array("#outType = '[#1#]' AND #outVal = '[#2#]'", $type, $id));
        $query->setUnion(array("#inType = '[#1#]' AND #inVal = '[#2#]'", $type, $id));
        
        $query->orderBy('createdOn', 'DESC');
        
        $recArr = $query->fetchAll();
        
        return $recArr;
    }
    
    
    /**
     * Връща вербализирани данни за различни преставяния
     *
     * @param string $type
     * @param int    $val
     * @param string $viewType
     * @param bool   $showRejecte
     * @param int    $limit
     *
     * @return NULL|string|core_ET|array
     */
    public static function getListView($type, $val, $viewType = 'table', $showRejecte = true, $limit = 1000)
    {
        if (!self::haveRightFor('viewlist')) {
            
            return;
        }
        
        $recArr = self::getRecsForType($type, $val, $showRejecte, $limit);
        
        $rowArr = array();
        
        $me = cls::get(get_called_class());

        foreach ($recArr as $id => $rec) {
            // Ако връзката е към себе си, да не се показва
            if (($rec->outType == $rec->inType) && ($rec->outVal == $rec->inVal)) {

                continue;
            }

            if (!$rec->inType && !$rec->inVal) {

                continue;
            }

            $linkUrl = array();
            $comment = $me->getVerbal($rec, 'comment');
            
            $getUrlWithAccess = false;
            
            if ($rec->state == 'active') {
                $getUrlWithAccess = true;
            }

            if ($rec->outType == $type && $rec->outVal == $val) {
                $icon = 'img/16/arrow_right.png';
                $rowArr[$id]['docLink'] = self::getVerbalLinkForType($rec->inType, $rec->inVal, $comment, $getUrlWithAccess, $linkUrl);
            } else {
                $icon = 'img/16/arrow_left.png';
                $rowArr[$id]['docLink'] = self::getVerbalLinkForType($rec->outType, $rec->outVal, $comment, $getUrlWithAccess, $linkUrl);
            }
            
            $rowArr[$id]['comment'] = $comment;
            
            if ($row = $me->recToVerbal($rec)) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Разглеждане', $linkUrl, "ef_icon={$icon}, title=Отваряне на връзката");
                $rowArr[$id]['_rowTools'] = $row->_rowTools->renderHtml();
                
                $rowArr[$id]['ROW_ATTR'] = $row->ROW_ATTR;
            }
        }
        
        if (empty($rowArr)) {
            
            return ;
        }
        
        if ($viewType == 'table') {
            $table = cls::get('core_TableView');
            $table->tableClass = 'listTable smallerText offsetTop';
            $table->style = 'margin-top: 20px;';
            $res = $table->get($rowArr, '_rowTools=✍,
                                          docLink=Връзка,
	                                      comment=Коментар');
        } elseif ($viewType == 'file') {
            $res = '';
            foreach ($rowArr as $row) {
                $res .= $res ? "\n" : '';
                $res .= $row['docLink'];
                if (trim($row['comment'])) {
                    $res .= ' (' . trim($row['comment']) . ')';
                }
            }
        } else {
            $res = $rowArr;
        }
        
        return $res;
    }
    
    
    /**
     * Добавя връзките във формата с възможност за визуализиране
     *
     * @param core_Form $form
     * @param string    $outVal
     * @param string    $outType
     */
    public static function showLinkedInForm(&$form, $outVal, $outType = 'doc')
    {
        if (!$outVal) {
            
            return ;
        }
        
        if (Mode::is('screenMode', 'wide')) {
            $className = 'floatedElement';
        }
        
        $rowArr = self::getListView($outType, $outVal, 'row', false, 10);
        
        if (!$rowArr) {
            
            return ;
        }
        
        $form->layout = $form->renderLayout();
        
        $conStr = "<div class='preview-holder {$className}' style='padding-top: 25px;'>" . tr('Свързани документи и файлове') . " <a href=\"javascript:toggleDisplay('linkedView')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn show-btn"> </a>';
        
        $hashParams = str::addHash($outType . '_' . $outVal . '_' . core_Users::getCurrent(), 8);
        
        $urlArr = array(get_called_class(), 'renderView', 'hash' => $hashParams);
        $urlArr['currentTab'] = core_Request::get('currentTab');
        
        $renderViewUrl = toUrl($urlArr, 'local');
        $renderViewUrl = urlencode($renderViewUrl);
        
        $style = $renderRes = '';
        
        // Ако има избрана стойност - рендираме предварително
        if ($rIdLinked = Mode::get('linked_' . $hashParams)) {
            $urlArr['rId'] = $rIdLinked;
            $urlArr['pUrl'] = toUrl(getCurrentUrl(), 'local');
            
            try {
                $renderRes = Request::forward($urlArr);
            } catch (core_exception_Expect $e) {
            }
        }
        
        $conStr .= "<div id='linkedView'{$style}><ol style='margin-top:2px;margin-top:2px;margin-bottom:2px;color:#888;' onchange=\"getEfae().process({url: '{$renderViewUrl}'}, {rId: $('input[name=linkedRadio]:checked').val()});\">";
        
        foreach ($rowArr as $id => $row) {
            $rId = 'linked_' . $id;
            
            $caption = 'capt';
            
            $val = $row['docLink'];
            
            if (trim($row['comment'])) {
                $val .= ' (' . trim($row['comment']) . ')';
            }
            
            $checked = '';
            
            if ($rIdLinked == $id) {
                $checked = 'checked';
            }
            
            $conStr .= "<div><input type='radio' name='linkedRadio' value='{$id}' id='{$rId}' {$checked}>{$val}</div>";
        }
        $conStr .= "</ol></div><div id='renderRes'>{$renderRes}</div>";
        $conStr .= '</div>';
        $form->layout->append($conStr);
    }
    
    
    /**
     * Екшън за рендирена на изгледа на свързаните документи и файлове
     *
     * @return array|string
     */
    public function act_RenderView()
    {
        $hash = Request::get('hash', 'varchar');
        expect($hStr = str::checkHash($hash, 8));
        
        $rId = Request::get('rId', 'int');
        
        expect($rId);
        
        Mode::setPermanent('linked_' . $hash, $rId);
        
        list($outType, $outVal, $uId) = explode('_', $hStr);
        
        expect($uId == core_Users::getCurrent());
        
        expect($outType && $outVal);
        
        $lRec = doc_Linked::fetch($rId);
        
        expect($lRec);
        
        expect($lRec->state != 'rejected');
        
        $inType = $lRec->inType;
        $inVal = $lRec->inVal;
        
        // Ако е връзка към
        if (($lRec->outType != $outType) || ($lRec->outVal != $outVal)) {
            expect(($lRec->inType == $outType) && ($lRec->inVal == $outVal));
            
            $outType = $inType;
            $outVal = $inVal;
            $inType = $lRec->outType;
            $inVal = $lRec->outVal;
        }
        
        $pUrl = core_Request::get('pUrl');
        
        if ($outType == 'doc') {
            $docInst = doc_Containers::getDocument($outVal);
            expect($docInst->instance);
            
            $clsInst = $docInst->instance;
            
            $clsInst->requireRightFor('single', $docInst->that);
            
            $rec = $docInst->fetch();
            
            expect($rec);
        } elseif ($type == 'file') {
            $clsInst = cls::get('fileman_Files');
            $clsInst->requireRightFor('single', $outVal);
            $rec = $clsInst->fetch($outVal);
            
            expect($rec);
        } else {
            expect(false, $type);
        }
        
        expect($inType && $inVal);
        
        if ($inType == 'doc') {
            $docInst = doc_Containers::getDocument($outVal);
            
            $tplRes = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Документ') . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");
            
            $document = doc_Containers::getDocument($inVal);
            if ($document->haveRightFor('single')) {
                Request::push(array('ajax_mode' => false), 'noAjaxMode');
                $docHtml = $document->getInlineDocumentBody();
                Request::pop('noAjaxMode');
                
                $tplRes->replace($docHtml, 'DOCUMENT');
            }
        } elseif ($inType == 'file') {
            $fRec = fileman_Files::fetch($inVal);
            expect($fRec);
            $tplRes = doc_DocumentPlg::showOriginalFile($fRec, null, $pUrl);
        }
        
        if ($tplRes instanceof core_ET) {
            $tplRes = $tplRes->getContent();
        }
        
        if (Request::get('ajax_mode')) {
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'renderRes', 'html' => $tplRes, 'replace' => true);
            
            return array($resObj);
        }
        
        return $tplRes;
    }
    
    
    /**
     * Екшън за свързване на файлове и документи
     *
     * @return Redirect|core_Et
     */
    public function act_Link()
    {
        $this->requireRightFor('addlink');
        
        $pArr = array('inType', 'foreignId');
        Request::setProtected($pArr);
        
        $type = Request::get('inType');
        
        $originFId = $fId = Request::get('foreignId', 'int');
        
        Request::removeProtected($pArr);
        
        expect($type && $fId);
        
        $form = cls::get('core_Form');
        
        $floatedClassName = '';
        if (Mode::is('screenMode', 'wide')) {
            $floatedClassName = 'floatedElement';
            $form->class .= " {$floatedClassName} ";
        }
        
        if ($type == 'doc') {
            $docInst = doc_Containers::getDocument($fId);
            expect($docInst->instance);
            
            $clsInst = $docInst->instance;
            $fId = $docInst->that;
            
            $clsInst->requireRightFor('single', $fId);
            
            $rec = $clsInst->fetch($fId);
            
            expect($fId);
        } elseif ($type == 'file') {
            $clsInst = cls::get('fileman_Files');
            $clsInst->requireRightFor('single', $fId);
            $rec = $clsInst->fetch($fId);
            
            $fId = fileman::idToFh($fId);
        } else {
            expect(false, $type);
        }
        
        expect($rec);
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array($clsInst, 'single', $fId);
        }
        
        $intfName = 'doc_LinkedIntf';
        
        $intfArr = core_Classes::getOptionsByInterface($intfName);
        
        // Добавяме екшъните от интерфейсите
        $actTypeIntfArr = array();
        foreach ($intfArr as $key => $intfCls) {
            $intfArr[$key] = cls::get($intfCls, $intfName);
        }
        
        foreach ($intfArr as $intfCls) {
            if ($type == 'doc') {
                $actTypeIntfArr += $intfCls->getActivitiesForDocument($originFId);
            } elseif ($type == 'file') {
                $actTypeIntfArr += $intfCls->getActivitiesForFile($originFId);
            }
        }
        
        // Вид връзка
        $actTypeArr = doc_Linked::$actArr;
        $actTypeArr += $actTypeIntfArr;
        
        $enumInst = cls::get('type_Enum');
        $enumInst->options = $actTypeArr;
        $form->FNC('act', $enumInst, 'caption=Действие, input, removeAndRefreshForm=linkContainerId|linkFolderId|linkThreadId|linkDocType|comment, mandatory, silent');
        
        $defAct = $this->getDefaultActionFor($originFId, $type, $actTypeArr);
        if ($defAct) {
            $form->setDefault('act', $defAct);
        }
        
        $form->input();
        
        if ($form->cmd != 'refresh') {
            doc_Linked::showLinkedInForm($form, $originFId, $type);
        }
        
        if ($form->cmd != 'refresh') {
            if ($type == 'file') {
                doc_DocumentPlg::showOriginalFile($rec, $form);
            } elseif ($type == 'doc') {
                $form->layout = $form->renderLayout();
                
                $tpl = new ET("<div class='preview-holder {$floatedClassName}'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Източник') . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");
                
                Request::push(array('ajax_mode' => false), 'noAjaxMode');
                $docHtml = $clsInst->getInlineDocumentBody($fId);
                Request::pop('noAjaxMode');
                
                $tpl->replace($docHtml, 'DOCUMENT');
                
                $form->layout->append($tpl);
            }
        }
        
        $act = trim($form->rec->act);
        
        if ($act && !doc_Linked::$actArr[$act]) {
            // Подготвяме формата от интерфейсните методи
            foreach ($intfArr as $intfCls) {
                if ($type == 'doc') {
                    $intfCls->prepareFormForDocument($form, $originFId, $act);
                } elseif ($type == 'file') {
                    $intfCls->prepareFormForFile($form, $originFId, $act);
                }
            }
        } else {
            $this->prepareFormForAct($form, $act, $type, $originFId);
        }
        
        $form->FNC('comment', 'varchar', 'caption=Пояснение, input');
        
        $form->input();
        
        $res = null;
        
        if ($act && !doc_Linked::$actArr[$act]) {
            // Субмитваме формата от интерфейсни методи
            foreach ($intfArr as $intfCls) {
                if ($type == 'doc') {
                    $intfRes = $intfCls->doActivityForDocument($form, $originFId, $act);
                } elseif ($type == 'file') {
                    $intfRes = $intfCls->doActivityForFile($form, $originFId, $act);
                }
                
                if (isset($intfRes)) {
                    $res = $intfRes;
                }
            }
        }
        
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
            if (!$act || doc_Linked::$actArr[$act]) {
                $res = $this->onSubmitFormForAct($form, $act, $type, $originFId);
            }
        }
        
        // Да не редиректва, когато формата се отвори автоматично
        if (is_object($res)) {
            if ($res instanceof core_Redirect) {
                if (!$form->cmd) {
                    $res = null;
                }
            }
        }
        
        if ($res) {
            
            return $res;
        }
        
        // Показва избрания документ, когато ще се прикача към него
        if ($act == 'linkDoc' && $form->rec->linkContainerId) {
            $form->layout = $form->renderLayout();
            
            $tpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Документ') . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");
            
            $document = doc_Containers::getDocument($form->rec->linkContainerId);
            if ($document->haveRightFor('single')) {
                Request::push(array('ajax_mode' => false), 'noAjaxMode');
                $docHtml = $document->getInlineDocumentBody();
                Request::pop('noAjaxMode');
                $tpl->replace($docHtml, 'DOCUMENT');
                
                $form->layout->append($tpl);
            }
        }
        
        $form->title = 'Свързване на файлове и документи с|* ' . $clsInst->getLinkToSingle($fId);
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Добавяне на връзка');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $formTpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($formTpl, $form);
        
        return $formTpl;
    }
    
    
    /**
     * Помощна функция, за подготовка на формата
     *
     * @param core_Form   $form
     * @param string      $act
     * @param string      $type
     * @param NULL|string $originFId
     */
    public static function prepareFormForAct(&$form, $act, $type = 'doc', $originFId = null)
    {
        if ($act == 'linkDoc') {
            $form->FNC('linkDocType', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Вид, class=w100, input, removeAndRefreshForm=linkContainerId|linkFolderId');
            $form->input();
            
            if ($type == 'doc' && $originFId) {
                $unsetStr = ",unsetId={$originFId}";
            }
            
            $form->FNC('linkFolderId', 'key2(forceAjax, mvc=doc_Folders, titleFld=title, maxSuggestions=100, selectSourceArr=doc_Linked::prepareFoldersForDoc, allowEmpty, docType=' . $form->rec->linkDocType . ", showWithDocs{$unsetStr})", 'caption=Папка, class=w100, input, removeAndRefreshForm=linkContainerId');
            $form->input();
            
            $form->FNC('linkContainerId', 'key2(forceAjax, mvc=doc_Containers, titleFld=id, maxSuggestions=100, selectSourceArr=doc_Linked::prepareLinkDocId, allowEmpty, docType=' . $form->rec->linkDocType . ', folderId=' . $form->rec->linkFolderId . "{$unsetStr})", 'caption=Документ, class=w100, input, mandatory, refreshForm');
        } elseif ($act == 'linkFile') {
            $form->FNC('linkFileId', 'fileman_FileType(bucket=Linked)', 'caption=Файл, input, mandatory');
        } elseif ($act == 'newDoc') {
            $form->FNC('linkDocType', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Вид, input, mandatory, removeAndRefreshForm=linkFolderId|linkThreadId');
            
            // Махаме документите, които не можем да създадем
            $optArr = $form->fields['linkDocType']->type->prepareOptions();
            foreach ($optArr as $optClsId => $title) {
                $optClsInst = cls::get($optClsId);
                
                if (!$optClsInst->haveRightFor('add')) {
                    unset($optArr[$optClsId]);
                }
            }
            $form->fields['linkDocType']->type->options = $optArr;
            
            $form->input();
            
            if ($form->rec->linkDocType) {
                $form->FNC('linkFolderId', 'key2(forceAjax, mvc=doc_Folders, titleFld=title, maxSuggestions=100, selectSourceArr=doc_Linked::prepareFoldersForDoc, allowEmpty, docType=' . $form->rec->linkDocType . ')', 'caption=Папка, class=w100, input, mandatory, removeAndRefreshForm=linkThreadId');
                $form->input();
                
                $dInst = cls::get($form->rec->linkDocType);
                
                // Ако документа може да се създаде в съществуваща нишка, показваме избор
                if ($form->rec->linkFolderId && !$dInst->onlyFirstInThread) {
                    $mandatory = '';
                    
                    if (!$dInst->canAddToFolder($form->rec->linkFolderId) || !$dInst->haveRightFor('add', (object) array('folderId' => $form->rec->linkFolderId))) {
                        $mandatory = ' ,mandatory';
                    }
                    
                    $form->FNC('linkThreadId', 'key2(forceAjax, mvc=doc_Threads, titleFld=firstContainerId, maxSuggestions=100, selectSourceArr=doc_Linked::prepareThreadsForDoc, allowEmpty, docType=' . $form->rec->linkDocType . ', folderId=' . $form->rec->linkFolderId . ')', "caption=Нишка, class=w100, input, refreshForm{$mandatory}");
                }
            }
        }
        
        // При създаване на имейл, по подразбиране да е папката на контрагента
        if ($form->rec->linkDocType && !$form->rec->linkFolderId && $type == 'doc' && $originFId ) {
            $docType = cls::get($form->rec->linkDocType);
            
            if ($docType instanceof email_Outgoings) {
                $cRec = doc_Containers::fetch($originFId);
                if ($cRec->folderId) {
                    $cover = doc_Folders::getCover($cRec->folderId);
                    if ($cover->that && ($cover->instance instanceof doc_UnsortedFolders)) {
                        $cFolderId = $cover->instance->fetchField($cover->that, 'contragentFolderId');
                        
                        if ($cFolderId && doc_Folders::haveRightFor('single', $cFolderId)) {
                            $form->setDefault('linkFolderId', $cFolderId);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Помощна функция, която се вика след субмитване на формата
     *
     * @param core_Form   $form
     * @param string      $act
     * @param string      $type
     * @param int         $originFId
     * @param NULL|string $actType
     * @param array       $rUrl
     *
     * @return Redirect
     */
    public function onSubmitFormForAct($form, $act, $type, $originFId, $actType = null, $rUrl = array())
    {
        if (!isset($actType)) {
            $actType = $act;
        }
        
        $nRec = new stdClass();
        $nRec->actType = $actType;
        $nRec->outType = $type;
        $nRec->outVal = $originFId;
        $nRec->comment = $form->rec->comment;
        $nRec->state = 'active';
        
        if ($act == 'linkDoc') {
            $nRec->inType = 'doc';
            $nRec->inVal = $form->rec->linkContainerId;
        } elseif ($act == 'linkFile') {
            $nRec->inType = 'file';
            $nRec->inVal = fileman_Files::fetchByFh($form->rec->linkFileId)->id;
        } elseif ($act == 'newDoc') {
            
            // Ако се създава нов документ, записваме в кеша и след създаване добавяме запис
            
            $nRec->inType = 'doc';
            
            if (empty($rUrl)) {
                $url = array(cls::get($form->rec->linkDocType), 'add', 'folderId' => $form->rec->linkFolderId);
            } else {
                $url = $rUrl;
            }
            
            if ($form->rec->linkThreadId) {
                $url['threadId'] = $form->rec->linkThreadId;
            }
            
            $url['linkedHashKey'] = 'LHK_' . substr(md5(serialize($nRec) . '|' . dt::now() . '|' . core_Users::getCurrent()), 0, 8);
            
            $url['ret_url'] = true;
            
            core_Permanent::set($url['linkedHashKey'], $nRec, 600);
            
            return new Redirect($url);
        }
        
        $retUrl = (!empty($rUrl)) ? $rUrl : getRetUrl();
        
        // Прави необходимите проверки и добавя запис
        $fieldsArr = array();
        
        if (!$this->isUnique($nRec, $fieldsArr)) {
            $form->setError($fieldsArr, 'Вече съществува такава връзка');
        } else {
            if ($nRec->inVal && ($nRec->inType == $nRec->outType) && ($nRec->inVal == $nRec->outVal)) {
                $errMsg = 'Избрали сте същия ';
                if ($nRec->inType == 'doc') {
                    $errMsg .= 'документ';
                } else {
                    $errMsg .= 'файл';
                }
                $form->setError('linkContainerId', $errMsg);
            } else {
                $this->save($nRec);
                
                try {
                    $strType = 'документ';
                    if ($nRec->outType == 'doc') {
                        if ($nRec->inType == 'file') {
                            $strType = 'файл';
                        }
                        $outDoc = doc_Containers::getDocument($nRec->outVal);
                        $outDoc->instance->logRead("Добавена връзка към {$strType}", $outDoc->that);
                    }
                    
                    $strType = 'документ';
                    if ($nRec->inType == 'doc') {
                        if ($nRec->outType == 'file') {
                            $strType = 'файл';
                        }
                        $inDoc = doc_Containers::getDocument($nRec->inVal);
                        $inDoc->instance->logRead("Добавена връзка от {$strType}", $inDoc->that);
                    }
                } catch (core_exception_Expect $e) {
                }
                
                return new Redirect($retUrl);
            }
        }
    }
    
    
    /**
     * Връща възможно най-добрият екшън за съответния документ
     *
     * @param int      $docId
     * @param string   $type
     * @param array    $actTypeArrOpt
     * @param int|NULL $folderId
     * @param int|NULL $userId
     *
     * @return string|mixed
     */
    protected static function getDefaultActionFor($docId, $type, $actTypeArrOpt, $folderId = null, $userId = null)
    {
        $qLimit = 3;
        $minBestCnt = $qLimit - 1;
        
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if (!$folderId) {
            if ($type == 'doc') {
                $docInst = doc_Containers::getDocument($docId);
                
                $folderId = $docInst->fetchField('folderId');
            }
        }
        
        $query = self::getQuery();
        $query->where("#state = 'active'");
        $query->orderBy('createdOn', 'DESC');
        $query->limit($qLimit);
        
        $query->where(array("#outType = '[#1#]'", $type));
        
        // Търсим само в наличните опции
        $or = false;
        foreach ((array) $actTypeArrOpt as $aTypeStr => $aTypeVerb) {
            $aTypeStr = trim($aTypeStr);
            if (!$aTypeStr) {
                continue;
            }
            
            $query->where(array("#actType = '[#1#]'", $aTypeStr), $or);
            $or = true;
        }
        
        if ($type == 'doc') {
            
            // Подобен файл - от същия клас
            
            $document = doc_Containers::getDocument($docId);
            $docClsId = $document->instance->getClassId();
            
            $query->EXT('cDocClass', 'doc_Containers', 'externalKey=outVal, externalName=docClass');
            $query->where(array("#cDocClass = '[#1#]'", $docClsId));
        } elseif ($type == 'file') {
            $fRec = fileman_Files::fetch($docId);
            $ext = fileman_Files::getExt($fRec->name);
            $query->EXT('fileName', 'fileman_Files', 'externalKey=outVal, externalName=name');
            
            $mimeType = fileman_Mimes::getMimeByExt($ext);
            
            // Подобни файлове - от миме типа
            $extArr = array();
            if ($mimeType) {
                $extArr = fileman_Mimes::getExtByMime($mimeType);
                
                if (!isset($extArr)) {
                    $extArr = array();
                }
            }
            
            if (array_search($ext, $extArr) === false) {
                $extArr[] = $ext;
            }
            
            $or = false;
            foreach ($extArr as $ext) {
                $ext = mb_strtolower($ext);
                $ext = trim($ext);
                
                if (!$ext) {
                    continue;
                }
                
                $query->where(array("#fileName LIKE LOWER('%.[#1#]')", $ext), $or);
                $or = true;
            }
        }

        // Същия тим документ или папка +
        // 1 - потребител и папка
        // 2 - потребител
        // 3 - папка
        
        $qArr = array();
        
        $qArr[2] = clone $query;
        $qArr[2]->where(array("#createdBy = '[#1#]'", $userId));
        
        if ($folderId) {
            $qArr[1] = clone $query;
            
            $qArr[1]->EXT('folderId', 'doc_Containers', 'externalKey=outVal, externalName=folderId');
            $qArr[1]->where(array("#folderId = '[#1#]'", $folderId));
            
            $qArr[3] = clone $qArr[1];
            
            $qArr[1]->where(array("#createdBy = '[#1#]'", $userId));
        }
        
        ksort($qArr);
        
        $actStr = '';
        foreach ($qArr as $q) {
            $actTypeArr = array();
            while ($rec = $q->fetch()) {
                if (!$rec->actType) {
                    continue;
                }
                $actTypeArr[$rec->actType]++;
            }
            
            if (empty($actTypeArr)) {
                continue;
            }
            
            arsort($actTypeArr);
            if (!empty($actTypeArr)) {
                reset($actTypeArr);
                $firstElemKey = key($actTypeArr);
                
                if ($actTypeArr[$firstElemKey] >= $minBestCnt) {
                    $actStr = $firstElemKey;
                    
                    break;
                }
            }
        }
        
        return $actStr;
    }
    
    
    /**
     * Подготвя опциите за key2 за избор на документ
     *
     * @param array          $params
     * @param NULL|int       $limit
     * @param string         $q
     * @param NULL|array|int $onlyIds
     * @param bool           $includeHiddens
     *
     * @return array
     */
    public static function prepareLinkDocId($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        setIfNot($limit, $params['maxSuggestions'], 100);
        $sArr = array();
        
        $cInst = cls::get($params['mvc']);
        
        $cQuery = $cInst->getQuery();
        
        doc_Threads::restrictAccess($cQuery);
        
        if (!$includeHiddens) {
            $cQuery->where("#state != 'rejected'");
        }
        
        if (is_array($onlyIds)) {
            if (!count($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $cQuery->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $cQuery->where("#id = ${onlyIds}");
        }
        
        if ($q) {
            plg_Search::applySearch($q, $cQuery, 'searchKeywords');
        } else {
            
            // Показваме последните записи в началото
            if ($limit != 1) {
                if (isset($limit) && ($limit < self::$recentlyLimit)) {
                    self::$recentlyLimit = $limit;
                }
                
                $recentlyArr = self::getLastObjectsFromRecently('document');
                
                if (!empty($recentlyArr)) {
                    foreach ($recentlyArr as $cId) {
                        if (!$cId) {
                            continue;
                        }
                        
                        $cRec = doc_Containers::fetch($cId);
                        
                        if (!$cRec) {
                            continue;
                        }
                        
                        if ($cRec->state == 'rejected') {
                            continue;
                        }
                        
                        if ($params['docType']) {
                            if ($cRec->docClass != $params['docType']) {
                                continue;
                            }
                        }
                        
                        if ($params['folderId']) {
                            if ($cRec->folderId != $params['folderId']) {
                                continue;
                            }
                        }
                        
                        if ($params['unsetId']) {
                            if ($cRec->id == $params['unsetId']) {
                                continue;
                            }
                        }
                        
                        try {
                            $dInst = cls::get($cRec->docClass);
                            
                            if (!$dInst->haveRightFor('single', $cRec->docId)) {
                                continue;
                            }
                            
                            $title = '';
                            
                            if ($cRec->docId) {
                                $oRow = $dInst->getDocumentRow($cRec->docId);
                                $title = $oRow->recTitle ? $oRow->recTitle : $oRow->title;
                                $title = trim($title);
                                $title = str::limitLen($title, self::$titleLen);
                            }
                            
                            if ($title) {
                                $sArr[$cRec->id] = $title . ' (' . $dInst->getHandle($cRec->docId) . ')';
                            } else {
                                $sArr[$cRec->id] = $dInst->getHandle($cRec->docId);
                            }
                            
                        } catch (core_exception_Expect $e) {
                            reportException($e);
                            continue;
                        }
                    }
                }
            }
        }
        
        if ($params['docType']) {
            $cQuery->where(array("#docClass = '[#1#]'", $params['docType']));
        }
        
        if ($params['folderId']) {
            $cQuery->where(array("#folderId = '[#1#]'", $params['folderId']));
        } else {
            $cQuery->where(array("#modifiedOn >= '[#1#]'", dt::addDays(-730)));
        }
        
        if ($params['unsetId']) {
            $cQuery->where(array("#id != '[#1#]'", $params['unsetId']));
        }
        
        $limit -= count($sArr);
        $cQuery->limit($limit);
        
        $cQuery->orderBy('modifiedOn', 'DESC');
        
        while ($cRec = $cQuery->fetchAndCache()) {
            if ($sArr[$cRec->id]) {
                continue;
            }
            
            try {
                $dInst = cls::get($cRec->docClass);
                $oRow = $dInst->getDocumentRow($cRec->docId);
                $title = $oRow->recTitle ? $oRow->recTitle : $oRow->title;
                $title = trim($title);
                $title = str::limitLen($title, self::$titleLen);
                $sArr[$cRec->id] = $title . ' (' . $dInst->getHandle($cRec->docId) . ')';
            } catch (core_exception_Expect $e) {
                reportException($e);
                continue;
            }
        }
        
        return $sArr;
    }
    
    
    /**
     * Подготвя опциите за key2 за избор на папка
     *
     * @param array          $params
     * @param NULL|int       $limit
     * @param string         $q
     * @param NULL|array|int $onlyIds
     * @param bool           $includeHiddens
     *
     * @return array
     */
    public static function prepareFoldersForDoc($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $maxTrays = 500;
        setIfNot($limit, $params['maxSuggestions'], 100);
        $res = array();
        
        if ($params['docType']) {
            $docTypeInst = cls::get($params['docType']);
        }
        
        $query = doc_Folders::getQuery();
        
        doc_Folders::restrictAccess($query, null, false);
        
        if (!$includeHiddens) {
            $query->where("#state != 'rejected' AND #state != 'closed'");
        }
        
        if (is_array($onlyIds)) {
            if (!count($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        }
        
        $titleFld = $params['titleFld'];
        
        $show = "id,class,{$titleFld}";
        
        $query->EXT('class', 'core_Classes', 'externalKey=coverClass,externalName=title');
        
        if ($q) {
            $query->XPR('searchFieldXpr', 'text', "LOWER(CONCAT(' ', #{$titleFld}))");
            
            $show .= ',searchFieldXpr';
            
            if ($q[0] == '"') {
                $strict = true;
            }
            
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            
            $q = mb_strtolower($q);
            
            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $query->where(array("#searchFieldXpr REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        } else {
            if (isset($limit) && ($limit < self::$recentlyLimit)) {
                self::$recentlyLimit = $limit;
            }
            
            if ($limit != 1) {
                // Подреждаме последно посетените папки в началото
                $recentlyArr = self::getLastObjectsFromRecently();
                if (!empty($recentlyArr)) {
                    foreach ($recentlyArr as $fId) {
                        if (!$fId) {
                            continue;
                        }
                        
                        if ($docTypeInst) {
                            if ($docTypeInst->onlyFirstInThread && (!$docTypeInst->canAddToFolder($fId) || !$docTypeInst->haveRightFor('add', (object) array('folderId' => $fId)))) {
                                continue;
                            }
                        }
                        
                        $fRec = doc_Folders::fetch($fId);
                        
                        if (!$fRec) {
                            continue;
                        }
                        
                        if (($fRec->state == 'rejected') || ($fRec->state == 'closed')) {
                            continue;
                        }
                        
                        if (!doc_Folders::haveRightFor('single', $fRec)) {
                            continue;
                        }
                        
                        $fTitle = doc_Folders::fetchField($fId, 'title');
                        $fTitle = trim($fTitle);
                        $fTitle = str::limitLen($fTitle, self::$titleLen);
                        
                        if ($fRec->coverClass) {
                            $clsTitle = core_Classes::fetchField($fRec->coverClass, 'title');
                            $fTitle .= ' (' . $clsTitle . ')';
                        }
                        
                        $res[$fId] = $fTitle;
                    }
                }
            }
        }
        
        // Ако е зададено да се показват папките в които има такива документи
        if ($params['showWithDocs'] && $docTypeInst) {
            $pKey = 'linkedDocFolders_' . substr(md5($docTypeInst->className . '|' . core_Users::getCurrent()), 0, 8) . '|' . $params['unsetId'];
            
            $cacheTime = 5;
            
            $minCreatedOn = dt::subtractSecs($cacheTime * 60);
            $fArr = core_Permanent::get($pKey, $minCreatedOn);
            
            if (!isset($fArr) || !is_array($fArr)) {
                $dQuery = $docTypeInst->getQuery();
                
                $dQuery->where("#state != 'rejected'");
                
                if ($params['unsetId']) {
                    $dQuery->where(array("#containerId != '[#1#]'", $params['unsetId']));
                }
                
                doc_Folders::restrictAccess($dQuery, null, false);
                
                $dQuery->groupBy('folderId');
                
                $dQuery->show('folderId');
                
                $fArr = array();
                while ($dRec = $dQuery->fetch()) {
                    $fArr[$dRec->folderId] = $dRec->folderId;
                }
                
                core_Permanent::set($pKey, $fArr, $cacheTime);
            }
            
            if (!empty($fArr)) {
                $query->in('id', $fArr);
            } else {
                // Да не се показва нищо, ако няма документ
                $query->where('1=2');
            }
            
            // Премахваме папките, които нямат такъв документ
            foreach ($res as $fId => $fTitle) {
                if (!isset($fArr[$fId])) {
                    unset($res[$fId]);
                }
            }
        }
        
        $query->show($show);
        
        $limit -= count($res);
        $query->orderBy('last', 'DESC');
        
        while ($rec = $query->fetch()) {
            
            // Това е защита от увисване
            if ($maxTrays-- < 0 && (!empty($res))) {
                $group = new stdClass();
                $group->title = tr('За още резултати, въведете част от името');
                $group->attr = array('class' => 'team');
                $group->group = true;
                $res['more'] = $group;
                
                break;
            }
            
            if ($res[$rec->id]) {
                continue;
            }
            
            if ($docTypeInst) {
                if ($docTypeInst->onlyFirstInThread && (!$docTypeInst->canAddToFolder($rec->id) || !$docTypeInst->haveRightFor('add', (object) array('folderId' => $rec->id)))) {
                    continue;
                }
            }
            
            if (!$limit--) {
                break;
            }
            
            $title = trim($rec->{$titleFld});
            $title = str::limitLen($title, self::$titleLen);
            
            $res[$rec->id] = $title . ' (' . $rec->class . ')';
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя опциите за key2 за избор на нишка
     *
     * @param array          $params
     * @param NULL|int       $limit
     * @param string         $q
     * @param NULL|array|int $onlyIds
     * @param bool           $includeHiddens
     *
     * @return array
     */
    public static function prepareThreadsForDoc($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        setIfNot($limit, $params['maxSuggestions'], 100);
        $res = array();
        
        if ($params['docType']) {
            $docTypeInst = cls::get($params['docType']);
        }
        
        $folderId = $params['folderId'];
        
        $query = doc_Threads::getQuery();
        
        if ($folderId) {
            $query->where(array("#folderId = '[#1#]'", $folderId));
        }
        
        doc_Threads::restrictAccess($query);
        
        if (!$includeHiddens) {
            $query->where("#state != 'rejected'");
        }
        
        if (is_array($onlyIds)) {
            if (!count($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        }
        
        $show = 'id, firstDocClass, firstDocId';
        if ($q) {
            $query->EXT('searchKeywords', 'doc_Containers', 'externalKey=firstContainerId, externalName=searchKeywords');
            
            $show .= ' ,searchKeywords';
            
            plg_Search::applySearch($q, $query, 'searchKeywords');
        } else {
            if ($limit != 1) {
                if (isset($limit) && ($limit < self::$recentlyLimit)) {
                    self::$recentlyLimit = $limit;
                }
                
                $recentlyArr = self::getLastObjectsFromRecently('document', 'threadId');
                if (!empty($recentlyArr)) {
                    foreach ($recentlyArr as $tId) {
                        if (!$tId) {
                            continue;
                        }
                        
                        if ($docTypeInst) {
                            if ($docTypeInst->onlyFirstInThread || !$docTypeInst->canAddToThread($tId)) {
                                continue;
                            }
                            
                            if (!$docTypeInst->haveRightFor('add', (object) array('threadId' => $tId))) {
                                continue;
                            }
                        }
                        
                        $title = $tId;
                        
                        $tRec = doc_Threads::fetch($tId);
                        
                        if (!$tRec) {
                            continue;
                        }
                        
                        if ($tRec->state == 'rejected') {
                            continue;
                        }
                        
                        if ($folderId && $tRec->folderId != $folderId) {
                            continue;
                        }
                        
                        if (!doc_Threads::haveRightFor('single', $tRec)) {
                            continue;
                        }
                        
                        if ($tRec->firstDocClass) {
                            try {
                                $dInst = cls::get($tRec->firstDocClass);
                                
                                $oRow = $dInst->getDocumentRow($tRec->firstDocId);
                                $title = $oRow->recTitle ? $oRow->recTitle : $oRow->title;
                                $title = trim($title);
                                $title = str::limitLen($title, self::$titleLen);
                            } catch (core_exception_Expect $e) {
                                // Не се променя title
                            }
                        }
                        
                        $res[$tId] = $title;
                    }
                }
            }
        }
        
        $query->show($show);
        
        $limit -= count($res);
        $query->orderBy('last', 'DESC');
        
        while ($rec = $query->fetch()) {
            if ($res[$rec->id]) {
                continue;
            }
            
            if ($docTypeInst) {
                if ($docTypeInst->onlyFirstInThread || !$docTypeInst->canAddToThread($rec->id)) {
                    continue;
                }
                
                if (!$docTypeInst->haveRightFor('add', (object) array('threadId' => $rec->id))) {
                    continue;
                }
            }
            
            if (!$limit--) {
                break;
            }
            
            $title = $rec->id;
            
            if ($rec->firstDocClass) {
                try {
                    $dInst = cls::get($rec->firstDocClass);
                    
                    $oRow = $dInst->getDocumentRow($rec->firstDocId);
                    $title = $oRow->recTitle ? $oRow->recTitle : $oRow->title;
                    $title = trim($title);
                    $title = str::limitLen($title, self::$titleLen);
                } catch (core_exception_Expect $e) {
                    // Не се променя title
                }
            }
            
            $res[$rec->id] = $title;
        }
        
        return $res;
    }
    
    
    /**
     * Връща последните записи от bgerp_Recently
     *
     * @param string   $type
     * @param string   $show
     * @param NULL|int $userId
     *
     * @return array
     */
    protected static function getLastObjectsFromRecently($type = 'folder', $show = 'objectId', $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $rQuery = bgerp_Recently::getQuery();
        $rQuery->where(array("#type = '[#1#]'", $type));
        $rQuery->where(array("#userId = '[#1#]'", $userId));
        $rQuery->where('#objectId IS NOT NULL');
        $rQuery->where("#objectId != ''");
        $rQuery->where('#objectId != 0');
        $rQuery->orderBy('last', 'DESC');
        $rQuery->limit(self::$recentlyLimit);
        
        $rQuery->show($show);
        
        $resArr = array();
        while ($rRec = $rQuery->fetch()) {
            if (!$rRec->{$show}) {
                continue;
            }
            $resArr[$rRec->{$show}] = $rRec->{$show};
        }
        
        return $resArr;
    }
    
    
    /**
     * Помощна функция за връщане на линк към документ/файл
     *
     * @param string      $type
     * @param int         $valId
     * @param NULL|string $comment
     * @param bool        $getUrlWithAccess
     * @param array       $linkUrl
     *
     * @return string|core_ET
     */
    protected static function getVerbalLinkForType($type, $valId, &$comment = null, $getUrlWithAccess = false, &$linkUrl = array())
    {
        if ($type == 'doc') {
            try{
                // Документа
                $doc = doc_Containers::getDocument($valId);
                
                $title = $doc->getTitleById();
                
                $title = str::limitLen($title, 36);
                
                // Полетата на документа във вербален вид
                $docRow = $doc->getDocumentRow();
                
                $linkUrl = $doc->getSingleUrlArray();
                if (empty($linkUrl) && ($getUrlWithAccess)) {
                    $linkUrl = $doc->getUrlWithAccess($doc->instance, $doc->that);
                }
                
                // Атрибутеите на линка
                $attr = array();
                $attr['ef_icon'] = $doc->getIcon($doc->that);
                $attr['title'] = 'Документ|*: ' . $docRow->title;
                
                // Ако документа е оттеглен
                $dRec = $doc->fetch();
                if ($dRec->state == 'rejected') {
                    $attr['class'] = 'state-rejected';
                    $attr['style'] = 'text-decoration: line-through; color: #666;';
                }
            } catch(core_exception_Expect $e){
                $title = "<span class='red'>" . tr('Проблем при показването') . " </span>";
            }
            
            $link = ht::createLink($title, $linkUrl, null, $attr);
            
            $folderId = doc_Containers::fetchField($valId, 'folderId');
            if ($folderId && doc_Folders::haveRightFor('single', $folderId)) {
                $fRec = doc_Folders::fetch($folderId);
                $fRec->title = str::limitLen($fRec->title, 52);
                $link .= ' « <span class="small">' . doc_Folders::recToVerbal($fRec, 'title')->title . "</span>";
            }
            
            $comment = $doc->getDefaultLinkedComment($comment);
        } elseif ($type == 'file') {
            $clsInst = cls::get('fileman_Files');
            $valId = fileman::idToFh($valId);
            
            expect($valId);
            
            $link = $clsInst->getLinkToSingle($valId);
            
            $linkUrl = array('fileman_Files', 'single', $valId);
            
            if (!trim($comment)) {
                $comment = tr('Файл');
            }
        } else {
            expect(false, $type);
        }
        
        return $link;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако се радактира записа, само коментара да може да се сменя
        if ($data->form->rec->id) {
            $data->form->setField('outType', 'input=none');
            $data->form->setField('outVal', 'input=none');
            $data->form->setField('inType', 'input=none');
            $data->form->setField('inVal', 'input=none');
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec)
    {
        if (!trim($rec->comment)) {
            
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if ($rec->outType == 'doc') {
            $doc = doc_Containers::getDocument($rec->outVal);
            $doc->touchRec();
        }
        
        if ($rec->inType == 'doc') {
            $doc = doc_Containers::getDocument($rec->inVal);
            $doc->touchRec();
        }
    }
    
    
    /**
     * След инсталация на класа
     *
     * @param doc_linked  $mvc
     * @param NULL|string $res
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        // Инсталиране на кофата
        $res .= fileman_Buckets::createBucket('Linked', 'Файлове във свързаните документи', null, '300 MB', 'user', 'user');
    }
}
