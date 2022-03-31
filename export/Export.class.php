<?php


/**
 * Експортиране на документи
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class export_Export extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ';
    
    
    /**
     * Връща масив с възможните формати за експорт
     *
     * @param int      $clsId
     * @param int      $objectId
     * @param NULL|int $limit
     *
     * @return array
     */
    public static function getPossibleExports($clsId, $objectId, $limit = null)
    {
        $clsArr = core_Classes::getOptionsByInterface('export_ExportTypeIntf');
        
        $res = array();
        
        foreach ($clsArr as $cls => $clsName) {
            if (!cls::load($clsName, true)) {
                continue;
            }
            
            $clsInst = cls::getInterface('export_ExportTypeIntf', $cls);
            
            if (!$clsInst->canUseExport($clsId, $objectId)) {
                continue;
            }
            
            $res[$cls] = $clsInst->getExportTitle($clsId, $objectId);
            
            if (isset($limit)) {
                if (!--$limit) {
                    break;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Помощна функция за проверка на права към документа
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public static function canUseExport($clsId, $objId)
    {
        static $resArr = array();
        $key = $clsId . '|' . $objId . '|' . core_Users::getCurrent();
        
        if (isset($resArr[$key])) {
            
            return $resArr[$key];
        }
        
        if (!$clsId || !$objId) {
            $resArr[$key] = false;
            
            return $resArr[$key];
        }
        
        if (!cls::load($clsId, true)) {
            $resArr[$key] = false;
            
            return $resArr[$key];
        }
        
        $dInst = cls::get($clsId);
        
        $dRec = $dInst->fetch($objId);
        
        if (($dRec->state == 'rejected') || ($dRec->state == 'draft')) {
            $resArr[$key] = false;
            
            return $resArr[$key];
        }
        
        if (!$dInst->haveRightFor('single', $objId)) {
            $resArr[$key] = false;
            
            return $resArr[$key];
        }
        
        $resArr[$key] = true;
        
        return $resArr[$key];
    }
    
    
    /**
     * Екшън за експортиране
     */
    public function act_Export()
    {
        Request::setProtected(array('classId', 'docId'));
        
        $classId = Request::get('classId', 'class(interface=doc_DocumentIntf)');
        $docId = Request::get('docId', 'int');
        
        expect($classId && $docId);
        
        $inst = cls::get($classId);
        $dRec = $inst->fetch($docId);
        
        expect($dRec);
        
        $inst->requireRightFor('exportdoc', $dRec);

        core_App::setTimeLimit(300);

        $form = $this->getForm();
        $form->title = 'Експортиране на|* ' . $inst->getFormTitleLink($docId);
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array($inst, 'single', $docId);
        }
        
        $exportFormats = $this->getPossibleExports($classId, $docId);

        if (!empty($exportFormats)) {
            ksort($exportFormats);
        }
        
        $suggestions = '';
        foreach ($exportFormats as $clsId => $typeTitle) {
            $suggestions .= "{$clsId}={$typeTitle},";
        }
        $suggestions = rtrim($suggestions, ',');
        
        $form->FNC('type', "enum({$suggestions})", 'maxRadio=10, caption=Вид, input, mandatory,silent,removeAndRefreshForm');

        $pKey = 'docExportType_' . core_Users::getCurrent();
        if (($docExportType = core_Permanent::get($pKey)) && (isset($exportFormats[$docExportType]))) {
            $form->setDefault('type', $docExportType);
        }

        $form->input(null, 'silent');
//        $form->input();

        // Ако е избран драйвер, той може да добавя полета за параметри на формата
        if($type = $form->rec->type){
            $intfCls = cls::getInterface('export_ExportTypeIntf', $type);
            $intfCls->addParamFields($form, $classId, $docId);
        }

        $form->input(null, 'silent');
        $form->input();
        
        if ($form->isSubmitted()) {
            $exportFormatsArr = $this->getPossibleExports($classId, $docId);
            expect($exportFormatsArr[$form->rec->type]);

            Mode::set('exporting', true);

            $intfCls = cls::getInterface('export_ExportTypeIntf', $form->rec->type);
            
            $eRes = $intfCls->makeExport($form, $classId, $docId);

            if ($form->rec->type) {
                core_Permanent::set($pKey, $form->rec->type, 43200);
            }

            if (is_object($eRes) && $eRes instanceof core_Redirect) {
                
                return $eRes;
            }
            
            $form->setReadOnly('type');
            
            $form->toolbar->addBtn('Затваряне', $retUrl, 'ef_icon = img/16/close-red.png, title=' . tr('Връщане към документа') . ', class=fright');

            // Добавяме необходимите бутони от интерфейсите
            $intfArr = core_Classes::getOptionsByInterface('export_FileActionIntf');
            foreach ($intfArr as $cls) {
                $intfCls = cls::getInterface('export_FileActionIntf', $cls);
                $intfCls->addActionBtn($form, $eRes);
            }

            if (!empty($intfArr) && $eRes) {
                if ((strlen($eRes) == FILEMAN_HANDLER_LEN) || !defined('FILEMAN_HANDLER_LEN')) {
                    if ($fRec = fileman::fetchByFh($eRes)) {
                        if ($dRec->containerId && $fRec->id) {
                            doc_Linked::add($dRec->containerId, $fRec->id, 'doc', 'file', tr('Експортиране'));
                        }
                    }
                }
            }
        } else {
            $form->toolbar->addSbBtn('Генериране', 'save', 'ef_icon = img/16/world_link.png, title = ' . tr('Генериране на линк за сваляне'));
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title= ' . tr('Прекратяване на действията'));
        }
        
        $tpl = $form->renderHtml();
        
        $inst->currentTab = 'Нишка';
        if (core_Packs::isInstalled('colab')) {
            if (core_Users::haveRole('partner')) {
                plg_ProtoWrapper::changeWrapper($inst, 'cms_ExternalWrapper');
            }
        }
        
        $tpl = $inst->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Помощен екшън за ескпорт със съответния интерфейс
     */
    public function act_ExportInExternal()
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        
        $objId = Request::get('objId', 'int');
        $clsId = Request::get('clsId', 'int');
        
        expect($objId && $clsId);
        
        $mid = Request::get('mid');
        
        expect($clsInst = cls::get($clsId));
        
        $mRec = $clsInst->fetch($objId);
        
        expect($mRec && $mRec->containerId);
        expect($mRec->state != 'rejected');
        
        expect($action = doclog_Documents::opened($mRec->containerId, $mid));
        doclog_Documents::popAction();
        
        $typeCls = Request::get('typeCls');
        $typeClsInst = cls::get($typeCls);
        
        // Ако е избран друг шаблон за отпечатване
        if ($action->data->tplManagerId) {
            $mRec->template = $action->data->tplManagerId;
        }
        
        $form = $clsInst->getForm();
        
        $mRec->__mid = $mid;
        
        if ($action->createdBy) {
            $su = core_Users::sudo($action->createdBy);
        }

        Mode::set('exporting', true);

        $fileHnd = $typeClsInst->makeExport($form, $clsId, $mRec);
        
        core_Users::exitSudo($su);
        
        if ($fileHnd) {
            $typeClsInst->logInfo('Експортиран документ');
            
            return Request::forward(array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true));
        }
        
        followRetUrl(null, '|Няма данни за експорт', 'error');
    }
}
