<?php


/**
 * Плъгин даващ възможност да се печатат етикети от обект
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_plg_Print extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->canPrintlabel, 'label, admin, ceo');
        setIfNot($mvc->canPrintperipherallabel, 'label, admin, ceo');
        setIfNot($mvc->printLabelCaptionPlural, 'Етикети');
        setIfNot($mvc->printLabelCaptionSingle, 'Етикет');
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
        if ($mvc->hasPlugin('plg_RowTools2')) {
            $alwaysShow = true;
            
            // Ако има бутон за печат на бърз етикет, показва се
            if($mvc->haveRightFor('printperipherallabel', $rec)){
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $lUrl = toUrl(array($mvc, 'printperipherallabel', $rec->id), 'local');
                $lUrl = urlencode($lUrl);

                $attr = array('ef_icon' => 'img/16/printer.png', 'title' => 'Разпечатване на ' . mb_strtolower($mvc->printLabelCaptionSingle), 'style' => 'position: relative; top: -2px;');
                if ($printedByNow = core_Permanent::get("printPeripheral{$mvc->className}_{$rec->id}")) {
                    $attr['alwaysShowCaption'] = "<span class='green'>({$printedByNow})</span>";
                } else {
                    $attr['alwaysShowCaption'] = "<span class='quiet'>(0)</span>";
                }

                $row->_rowTools->addFnLink($mvc->printLabelCaptionSingle, "getEfae().process({url: '{$lUrl}'});", $attr, 'alwaysShow');
                $alwaysShow = false;
            }

            if(($mvc instanceof core_Master && isset($fields['-single'])) || (!($mvc instanceof core_Master))){
                $btnsArr = self::getLabelBtnParams($mvc, $rec);
                foreach ($btnsArr as $btnArr){
                    if (!empty($btnArr['url'])) {
                        core_RowToolbar::createIfNotExists($row->_rowTools);
                        $btnArr['attr'] = arr::make($btnArr['attr']);
                        $btnArr['attr']['style'] = 'position: relative; top: -2px;';
                        $alwaysShow = ($alwaysShow) ? 'alwaysShow' : null;
                        $row->_rowTools->addLink($btnArr['caption'], $btnArr['url'], $btnArr['attr'], $alwaysShow);
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if($action == 'printperipherallabel'){
            $mvc->requireRightFor('printperipherallabel');
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('printperipherallabel', $rec);
            
            // Ако има периферия за печат на етикети
            expect($deviceRec = peripheral_Devices::getDevice('peripheral_PrinterIntf'));
            $source = $mvc->getLabelSource($rec);
            $interface = cls::getInterface('label_SequenceIntf', $source['class']);
            expect($peripheralTemplateId = $interface->getDefaultFastLabel($source['id'], $deviceRec));
            $labelContent = $interface->getDefaultLabelWithData($rec->id, $peripheralTemplateId);
            
            Request::setProtected('hash');
            $hash = str::addHash('fastlabel', 4);
            Request::removeProtected('hash');
            
            // Прави се опит за печат от периферията
            $interface = core_Cls::getInterface('peripheral_BrowserPrinterIntf', $deviceRec->driverClass);

            $responseUrl = array($mvc, 'printfastlabelresponse', $rec->id, 'ret_url' => getRetUrl(), 'hash' => $hash);
            $refreshUrl = Request::get('refreshUrl');
            if ($refreshUrl) {
                $responseUrl['refreshUrl'] = $refreshUrl;
            }

            $html = $interface->getHTML($deviceRec, $labelContent);
            $js = $interface->getJS($deviceRec, $labelContent);
            $js .= $interface->afterResultJS($deviceRec, $labelContent, $responseUrl);

            $js = minify_Js::process($js);

            if (Request::get('ajax_mode')) {
                // Добавяме резултата
                $resObj = new stdClass();
                $resObj->func = 'printPage';
                $resObj->arg = array('html' => $html, 'js' => $js);

                $res = array($resObj);
            }

            Mode::setPermanent('PREV_SAVED_ID', null);

            return false;
        }
        
        // Екшън обработващ резултата от разпечатването на бързия етикет
        if($action == 'printfastlabelresponse'){
            $mvc->requireRightFor('printperipherallabel');
            Request::setProtected('hash');
            expect($hash = Request::get('hash', 'varchar'));
            expect(str::checkHash($hash, 4));
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('printperipherallabel', $rec);
            expect($res = Request::get('res', 'varchar'));
            $logMvc = ($mvc instanceof core_Detail) ? $mvc->Master : $mvc;
            $logId = ($mvc instanceof core_Detail) ? $rec->{$mvc->masterKey} : $rec->id;

            $lRec = $logMvc->fetch($logId);
            if ($lRec->threadId) {
                doc_ThreadRefreshPlg::checkHash($lRec->threadId, array(), true);
            }

            $msg = tr("Етикетът е разпечатан успешно|*!");
            $type = Request::get('type', 'varchar');

            $statusData = array();

            $statusData['type'] = 'notice';
            $statusData['timeOut'] = 1700;
            $statusData['stayTime'] = 7000;
            $statusData['isSticky'] = 0;

            $cacheSuccess = true;
            if($type == 'error'){
                $msg = $res;
                $logMvc->logDebug($msg, $logId);
                $msg = haveRole('debug') ? $msg : tr('Проблем при разпечатването|*!');
                $statusData['type'] = 'error';
                $statusData['isSticky'] = 1;
                $cacheSuccess = false;
            } elseif ($type == 'unknown') {
                $logMvc->logWrite('Опит за разпечатване на бърз етикет', $logId);
                $msg = tr("Отпечатването завърши|*!");
            } else {
                $logMvc->logWrite('Разпечатване на бърз етикет', $logId);
            }

            if($cacheSuccess){
                if ($printedByNow = core_Permanent::get("printPeripheral{$mvc->className}_{$rec->id}")) {
                    $printedByNow += 1;
                } else {
                    $printedByNow = 1;
                }
                core_Permanent::set("printPeripheral{$mvc->className}_{$rec->id}", $printedByNow, 129600);
            }

            $statusData['text'] = $msg;

            $statusObj = new stdClass();
            $statusObj->func = 'showToast';
            $statusObj->arg = $statusData;

            $afterPrint = new stdClass();
            $afterPrint->func = 'afterPrintPage';
            $afterPrint->arg = array('timeOut' => 700);

            $res =  array($statusObj, $afterPrint);

            if ($refreshUrl = Request::get('refreshUrl')) {
                status_Messages::newStatus($msg, $statusData['type']);

                $res = array();

                // Добавяме резултата
                $redirectObj = new stdClass();
                $redirectObj->func = 'redirect';
                $redirectObj->arg = array('url' => $refreshUrl);

                $res[] = $redirectObj;
            }

            return false;
        }
    }
    
    
    /**
     * Параметрите на бутона за етикетиране
     *
     * @param core_mvc $mvc
     * @param stdClass $rec
     *
     * @return array $res -
     *               ['url'] - урл, ако има права
     *               ['attr] - атрибути
     */
    private static function getLabelBtnParams($mvc, $rec)
    {
        $series = $mvc->getLabelSeries($rec);
        $title = tr($mvc->title);
        $title = mb_strtolower($title);
        $source = $mvc->getLabelSource($rec);

        $res = array();
        foreach ($series as $series => $caption){
            $res[$series] = array('url' => null, 'attr' => '', 'caption' => $caption);
            if ($mvc->haveRightFor('printlabel', $rec)) {
                $templates = $mvc->getLabelTemplates($rec, $series, false);
                if(countR($templates)){
                    if (label_Prints::haveRightFor('add', (object) array('classId' => $source['class']->getClassid(), 'objectId' => $source['id'], 'series' => $series))) {
                        core_Request::setProtected(array('classId,objectId,series'));
                        $res[$series]['url'] = array('label_Prints', 'add', 'classId' => $source['class']->getClassid(), 'objectId' => $source['id'], 'series' => $series, 'ret_url' => true);
                        $res[$series]['url'] = toUrl($res[$series]['url']);
                        core_Request::removeProtected(array('classId,objectId,series'));
                        $res[$series]['attr'] = "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на ". mb_strtolower($mvc->printLabelCaptionSingle). " от|* {$title} №{$rec->id}{$error}";
                    }
                }
            }
        }

        return $res;
    }
    
    
    /**
     * Какви ще са параметрите на източника на етикета
     *
     * @param core_mvc $mvc
     * @param stdClass $rec
     *
     * @return array $res -
     *               ['class'] - клас
     *               ['id] - ид
     */
    public static function on_AfterGetLabelSource($mvc, &$res, $rec)
    {
        // По дефолт е текущия клас
        if(!isset($res)){
            $res = array('class' => $mvc, 'id' => $rec->id);
        }
    }


    /**
     * Връща наличните серии за етикети от източника
     *
     * @param $mvc
     * @param $res
     * @param $rec
     * @return void
     */
    public static function on_AfterGetLabelSeries($mvc, &$res, $rec = null)
    {
        // По дефолт е текущия клас
        if(!isset($res)){
            $res = array('label' => $mvc->printLabelCaptionPlural);
        }
    }


    /**
     * Параметрите на бутона за етикетиране
     *
     * @return array $res - наличните шаблони за етикети
     */
    public static function on_AfterGetLabelTemplates($mvc, &$res, $rec, $series = 'label', $ignoreWithPeripheralDriver = true)
    {
        if(!isset($res)){
            $res = label_Templates::getTemplatesByClass($mvc, $series, $ignoreWithPeripheralDriver);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $btnsArr = self::getLabelBtnParams($mvc, $data->rec);
        foreach ($btnsArr as $btnArr){
            if (!empty($btnArr['url'])) {
                $data->toolbar->addBtn($btnArr['caption'], $btnArr['url'], null, $btnArr['attr']);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'printlabel' && isset($rec)) {
            if (in_array($rec->state, array('rejected', 'draft', 'template', 'closed'))) {
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'printperipherallabel' && isset($rec)){
            $deviceRec = peripheral_Devices::getDevice('peripheral_BrowserPrinterIntf');
            if(!$deviceRec){
                $requiredRoles = 'no_one';
            } else {
                $source = $mvc->getLabelSource($rec);
                if(!cls::haveInterface('label_SequenceIntf', $source['class'])){
                    $requiredRoles = 'no_one';
                } else {
                    $interface = cls::getInterface('label_SequenceIntf', $source['class']);
                    if(!$interface->getDefaultFastLabel($source['id'], $deviceRec)){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Заглавие от източника на етикета
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param mixed    $id
     *
     * @return void
     */
    public static function on_AfterGetLabelSourceLink($mvc, &$res, $id)
    {
        if (cls::existsMethod($mvc, 'getFormTitleLink')) {
            $res = $mvc->getFormTitleLink($id);
        } elseif ($mvc instanceof core_Detail) {
            $rec = $mvc->fetchRec($id);
            $res = $mvc->Master->getFormTitleLink($rec->{$mvc->masterKey});
        }
    }


    /**
     * Изпълнява се след запис/промяна на роля
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFields = null)
    {
        if (cls::getClassName($mvc) . '_SAVE_AND_NEW') {
            Mode::setPermanent('PREV_SAVED_ID', $rec->id);
        }
    }


    /**
     *
     *
     * @param $invoker
     * @param $tpl
     */
    public function on_AfterRenderWrapping($invoker, &$tpl)
    {
        if ($invoker->_isSaveAndNew && ($prevSavedId = Mode::get('PREV_SAVED_ID'))) {
            if (label_Setup::get('AUTO_PRINT_AFTER_SAVE_AND_NEW') == 'yes') {
                if ($invoker->haveRightFor('printperipherallabel', $prevSavedId)) {
                    $lUrl = toUrl(array($invoker, 'printperipherallabel', $prevSavedId, 'refreshUrl' => toUrl(getCurrentUrl())), 'local');
                    $lUrl = urlencode($lUrl);

                    jquery_Jquery::run($tpl, "getEfae().process({url: '{$lUrl}'});", TRUE);
                }
            }
        }
    }
}
