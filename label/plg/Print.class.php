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
        setIfNot($mvc->canPrintPeripheralLabel, 'label, admin, ceo');
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
                $btnParams = self::getLabelBtnParams($mvc, $rec);
                if (!empty($btnParams['url'])) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $btnParams['attr'] = arr::make($btnParams['attr']);
                    $btnParams['attr']['style'] = 'position: relative; top: -2px;';
                    $alwaysShow = ($alwaysShow) ? 'alwaysShow' : null;
                    $row->_rowTools->addLink($mvc->printLabelCaptionPlural, $btnParams['url'], $btnParams['attr'], $alwaysShow);
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

            $html = $interface->getHTML($deviceRec, $labelContent);
            $js = $interface->getJS($deviceRec, $labelContent);
            $js .= $interface->afterResultJS($deviceRec, $labelContent, array($mvc, 'printfastlabelresponse', $rec->id, 'ret_url' => getRetUrl(), 'hash' => $hash));

            $js = minify_Js::process($js);

            if (Request::get('ajax_mode')) {
                if ($printedByNow = core_Permanent::get("printPeripheral{$mvc->className}_{$rec->id}")) {
                    $printedByNow += 1;
                } else {
                    $printedByNow = 1;
                }
                core_Permanent::set("printPeripheral{$mvc->className}_{$rec->id}", $printedByNow, 129600);

                // Добавяме резултата
                $resObj = new stdClass();
                $resObj->func = 'printPage';
                $resObj->arg = array('html' => $html, 'js' => $js);

                $res = array($resObj);
            }

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
//                $logMvc->touchRec($logId);
                doc_ThreadRefreshPlg::checkHash($lRec->threadId, array(), true);
            }

            $msg = tr("Етикетът е разпечатан успешно|*!");
            $type = Request::get('type', 'varchar');

            $statusData = array();

            $statusData['type'] = 'notice';
            $statusData['timeOut'] = 1700;
            $statusData['stayTime'] = 7000;
            $statusData['isSticky'] = 0;

            if($type == 'error'){
                $msg = $res;
                $logMvc->logDebug($msg, $logId);
                $msg = haveRole('debug') ? $msg : tr('Проблем при разпечатването|*!');
                $statusData['type'] = 'error';
                $statusData['isSticky'] = 1;
            } elseif ($type == 'unknown') {
                $logMvc->logWrite('Опит за разпечатване на бърз етикет', $logId);
                $msg = tr("Отпечатването завърши|*!");
            } else {
                $logMvc->logWrite('Разпечатване на бърз етикет', $logId);
            }

            $statusData['text'] = $msg;

            $statusObj = new stdClass();
            $statusObj->func = 'showToast';
            $statusObj->arg = $statusData;

            $afterPrint = new stdClass();
            $afterPrint->func = 'afterPrintPage';
            $afterPrint->arg = array('timeOut' => 700);

            $res =  array($statusObj, $afterPrint);

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
        $res = array('url' => null, 'attr' => '');
        
        if ($mvc->haveRightFor('printlabel', $rec)) {
            $templates = $mvc->getLabelTemplates($rec, false);
            
            $title = tr($mvc->title);
            $title = mb_strtolower($title);
            
            $error = (!countR($templates)) ? ",error=Няма наличен шаблон за етикети от|* \"{$title}\"" : '';
            $source = $mvc->getLabelSource($rec);
            
            if (label_Prints::haveRightFor('add', (object) array('classId' => $source['class']->getClassid(), 'objectId' => $source['id']))) {
                core_Request::setProtected(array('classId, objectId'));
                $res['url'] = array('label_Prints', 'add', 'classId' => $source['class']->getClassid(), 'objectId' => $source['id'], 'ret_url' => true);
                $res['url'] = toUrl($res['url']);
                core_Request::removeProtected(array('classId,objectId'));
                $res['attr'] = "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на ". mb_strtolower($mvc->printLabelCaptionSingle). " от|* {$title} №{$rec->id}{$error}";
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
     * Параметрите на бутона за етикетиране
     *
     * @return array $res - наличните шаблони за етикети
     */
    public static function on_AfterGetLabelTemplates($mvc, &$res, $rec)
    {
        if(!isset($res)){
            $res = label_Templates::getTemplatesByClass($mvc);
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
        $btnParams = self::getLabelBtnParams($mvc, $data->rec);
        if (!empty($btnParams['url'])) {
            $data->toolbar->addBtn($mvc->printLabelCaptionPlural, $btnParams['url'], null, $btnParams['attr']);
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
}
