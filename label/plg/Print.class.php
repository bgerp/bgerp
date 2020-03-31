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
                $row->_rowTools->addLink('Етикет', array($mvc, 'printperipherallabel', $rec->id, 'ret_url' => true), array('ef_icon' => 'img/16/printer.png', 'title' => 'Разпечатване на бърз етикет', 'style' => 'position: relative; top: -2px;'), 'alwaysShow');
                $alwaysShow = false;
            }
            
            $btnParams = self::getLabelBtnParams($mvc, $rec);
            if (!empty($btnParams['url'])) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $btnParams['attr'] = arr::make($btnParams['attr']);
                $btnParams['attr']['style'] = 'position: relative; top: -2px;';
                $alwaysShow = ($alwaysShow) ? 'alwaysShow' : null;
                $row->_rowTools->addLink('Етикети', $btnParams['url'], $btnParams['attr'], $alwaysShow);
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
            
            $res = new core_ET('');
            $res->append('<body><div class="fullScreenBg" style="position: fixed; top: 0; z-index: 1002; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: block;"><h3 style="color: #fff; font-size: 56px; text-align: center; position: absolute; top: 30%; width: 100%">Разпечатва се етикет ...<br> Моля, изчакайте!</h3></div></body>');
            
            // Ако има периферия за печат на етикети
            expect($deviceRec = peripheral_Devices::getDevice('peripheral_PrinterIntf'));
            $source = $mvc->getLabelSource($rec);
            $interface = cls::getInterface('label_SequenceIntf', $source['class']);
            expect($peripheralTemplateId = $interface->getDefaultFastLabel($source['id'], $deviceRec));
            $labelContent = $interface->getDefaultLabelWithData($rec->id, $peripheralTemplateId);
            
            Request::setProtected('hash');
            $hash = str::addHash('fastlabel', 4);
            $responseUrl = toUrl(array($mvc, 'printfastlabelresponse', $rec->id, 'ret_url' => getRetUrl(), 'hash' => $hash));
            Request::removeProtected('hash');
            
            // Прави се опит за печат от периферията
            $interface = core_Cls::getInterface('escpos_PrinterIntf', $deviceRec->driverClass);
            $js = $interface->getJS($deviceRec, $labelContent);
            $js .= " function escPrintOnSuccess(res) {
            if (res == 'OK') {
                document.location = '{$responseUrl}&res=' + res;
            } else {
                    escPrintOnError(res);
                }
            }   
            function escPrintOnError(res) {
                if($.isPlainObject(res)){
                    res = res.status  + '. ' +  res.statusText;
                }

                document.location = '{$responseUrl}&type=error&res=' + res;
            }";
            
            $res->append($js, 'SCRIPTS');
            
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
            
            $msg = tr("Етикетът е разпечатан успешно|*!");
            $type = Request::get('type', 'varchar');
            
            if($type == 'error'){
                $msg = $res;
                $logMvc->logDebug($msg, $logId);
                core_Statuses::newStatus($msg, 'error');
            } else {
                $logMvc->logWrite('Разпечатване на бърз етикет', $logId);
                core_Statuses::newStatus($msg);
            }
            
            followRetUrl();
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
                core_Request::removeProtected('classId,objectId');
                $res['attr'] = "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на етикети от|* {$title} №{$rec->id}{$error}";
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
            $data->toolbar->addBtn('Етикети', $btnParams['url'], null, $btnParams['attr']);
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
            $deviceRec = peripheral_Devices::getDevice('peripheral_PrinterIntf');
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
