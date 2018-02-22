<?php


/**
 * Експортиране на документи
 * 
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class export_Export extends core_Mvc
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Експортиране на документ";
    
    
    /**
     * Връща масив с възможните формати за експорт
     * 
     * @param integer $clsId
     * @param integer $objectId
     * @param NULL|integer $limit
     * 
     * @return array
     */
    public static function getPossibleExports($clsId, $objectId, $limit = NULL)
    {
        $clsArr = core_Classes::getOptionsByInterface('export_ExportTypeIntf');
        
        $res = array();
        
        foreach ($clsArr as $cls => $clsName) {
            
            if (!cls::load($clsName, TRUE)) continue;
            
            $clsInst = cls::getInterface('export_ExportTypeIntf', $cls);
            
            if (!$clsInst->canUseExport($clsId, $objectId)) continue;
            
            $res[$cls] = $clsInst->getExportTitle($clsId, $objectId);
            
            if (isset($limit)) {
                if (!--$limit) break;
            }
        }
        
        return $res;
    }
    
    
    
    /**
     * Помощна функция за проверка на права към документа
     *
     * @param integer $clsId
     * @param integer $objId
     *
     * @return boolean
     */
    public static function canUseExport($clsId, $objId)
    {
        static $resArr = array();
        $key = $clsId . '|' . $objId . '|' . core_Users::getCurrent();
        
        if (isset($resArr[$key])) return $resArr[$key];
        
        if (!$clsId || !$objId) {
            $resArr[$key] = FALSE;
            
            return $resArr[$key];
        }
        
        if (!cls::load($clsId, TRUE)) {
            $resArr[$key] = FALSE;
            
            return $resArr[$key];
        }
        
        $dInst = cls::get($clsId);
        
        $dRec = $dInst->fetch($objId);
        
        if (($dRec->state == 'rejected') || ($dRec->state == 'draft')) {
            $resArr[$key] = FALSE;
            
            return $resArr[$key];
        }
        
        if (!$dInst->haveRightFor('single', $objId)) {
            $resArr[$key] = FALSE;
            
            return $resArr[$key];
        }
        
        $resArr[$key] = TRUE;
        
        return $resArr[$key];
    }
    
    
    
    /**
     * Екшън за експортиране
     */
    function act_Export()
    {
        Request::setProtected(array('classId', 'docId'));
        
        $classId = Request::get('classId', 'class(interface=doc_DocumentIntf)');
        $docId = Request::get('docId', 'int');
        
        expect($classId && $docId);
        
        $inst = cls::get($classId);
        $dRec = $inst->fetch($docId);
        
        expect($dRec);
        
        $inst->requireRightFor('exportdoc', $dRec);
        
        $form = $this->getForm();
        
        $form->title = "Експортиране на документ";
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array($inst, 'single', $docId);
        }
        
        $exportFormats = $this->getPossibleExports($classId, $docId);
        
        $suggestions = '';
        foreach ($exportFormats as $clsId => $typeTitle) {
            $suggestions .= "{$clsId}={$typeTitle},";
        }
        $suggestions = rtrim($suggestions, ',');
        
        $form->FNC('type', "enum({$suggestions})", 'maxRadio=10, caption=Вид, input');
        
        $form->input();
        
        if ($form->isSubmitted()) {
            
            $exportFormatsArr = $this->getPossibleExports($classId, $docId);
            expect($exportFormatsArr[$form->rec->type]);
            
            $intfCls = cls::getInterface('export_ExportTypeIntf', $form->rec->type);
            
            $eRes = $intfCls->makeExport($form, $classId, $docId);
            
            if (is_object($eRes) && $eRes instanceOf core_Redirect) {
                
                return $eRes;
            }
            
            $form->setReadOnly('type');
            
            $form->toolbar->addBtn('Затваряне', $retUrl, 'ef_icon = img/16/close-red.png, title=' . tr('Връщане към документа') . ', class=fright');
        } else {
            $form->toolbar->addSbBtn('Генериране', 'save', 'ef_icon = img/16/world_link.png, title = ' . tr('Генериране на линк за сваляне'));
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title= ' . tr('Прекратяване на действията'));
        }
        
        $tpl = $form->renderHtml();
        
        $inst->currentTab = 'Нишка';
    	
	    $tpl = $inst->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Помощен екшън за ескпорт със съответния интерфейс
     */
    function act_ExportInExternal()
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
        
        $fileHnd = $typeClsInst->makeExport($form, $clsId, $mRec);
        
        core_Users::exitSudo($su);
        
        if ($fileHnd) {
            
            $typeClsInst->logInfo('Експортиран документ');
            
            return Request::forward(array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => TRUE));
        }
        
        expect(FALSE);
    }
}
