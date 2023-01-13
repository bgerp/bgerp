<?php
/**
 * Плъгин за връзка към външна система за генериране на товарителница
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_plg_CourierApiShipment extends core_Plugin
{


    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->canRequestbilloflading, 'powerUser');
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;

        if ($mvc->haveRightFor('requestbilloflading', $rec)) {
            $apiDriverId = $mvc->getCourierApi4Document($rec);
            if ($apiDriverId) {
                $serviceUrl = array($mvc, 'requestBillOfLading', 'objectId' => $rec->id, 'ret_url' => true);
                $Driver = cls::get($apiDriverId);
                $data->toolbar->addBtn($Driver->requestBillOfLadingBtnCaption, $serviceUrl, "ef_icon = {$Driver->requestBillOfLadingBtnIcon},title=Създаване на нова товарителница");
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
        if($action == 'requestbilloflading' && isset($rec)){
            $apiDriverId = $mvc->getCourierApi4Document($rec);
            if(empty($apiDriverId)){
                $requiredRoles = 'no_one';
            } else {

                // Ако потребителя може да избира драйвера
                $Driver = cls::get($apiDriverId);
                if(!$Driver->canRequestBillOfLading($mvc, $rec, $userId)){
                    $requiredRoles = 'no_one';
                }
            }

            if($requiredRoles != 'no_one'){
                if($rec->state != 'active'){
                    $requiredRoles = 'no_one';
                } else {

                    // Само към бърза продажба с доставка може да се създава
                    if($mvc instanceof sales_Sales){
                        $actions = type_Set::toArray($rec->contoActions);
                        if (!isset($actions['ship'])) {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }
    }


    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == 'requestbilloflading') {
            $mvc->requireRightFor('requestbilloflading');
            expect($id = Request::get('objectId', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('requestbilloflading', $rec);
            $apiDriverId = $mvc->getCourierApi4Document($rec);

            // Подаване на формата на драйвера
            $Driver = cls::getInterface('cond_CourierApiIntf', $apiDriverId);
            $form = cls::get('core_Form');
            $Driver->addFieldToBillOfLadingForm($mvc, $rec, $form);
            $form->input();
            $Driver->inputBillOfLadingForm($mvc, $rec, $form);

            if($form->isSubmitted()){
                if($form->cmd == 'save'){

                    // Ще върне ли драйвера файл хендлър на генерирана товарителница
                    $requestedShipmentFh = $Driver->getRequestedShipmentFh($mvc, $rec, $form);
                    if(!empty($requestedShipmentFh)){
                        if(!$form->gotErrors()){
                            $fileId = fileman::fetchByFh($requestedShipmentFh, 'id');
                            doc_Linked::add($rec->containerId, $fileId, 'doc', 'file', $Driver->class->billOfLadingComment);
                            $mvc->logWrite("Създаване на товарителница", $rec->id);
                            followRetUrl(null, "Товарителницата е изпратена успешно|*!");
                        }
                    }
                } elseif($form->cmd == 'calc'){
                    $calculatedShipmentTpl = $Driver->calculateShipmentTpl($mvc, $rec, $form);
                    if(is_object($calculatedShipmentTpl)){
                        $form->info = $calculatedShipmentTpl;
                    }
                }
            }

            // Подготовка на тулбара
            $form->toolbar->addSbBtn('Изпращане', 'save', "ef_icon ={$Driver->class->requestBillOfLadingBtnIcon}, title = Изпращане на товарителницата,id=save");
            $form->toolbar->addSbBtn('Изчисли', 'calc', 'ef_icon = img/16/calculator.png, title = Изчисляване на на товарителницата');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

            // Записваме, че потребителя е разглеждал този списък
            $mvc->logInfo('Форма за генериране на товарителница на Speedy', $rec->id);

            $res = $mvc->renderWrapping($form->renderHtml());
            $Driver->afterPrepareBillOfLadingForm($mvc, $rec, $form, $res);
            core_Form::preventDoubleSubmission($res, $form);

            return false;
        }
    }


    /**
     * Връща тялото на имейла генериран от документа
     */
    public function on_AfterGetDefaultEmailBody($mvc, &$tpl, $id, $isForwarding = false)
    {
        if($apiDriverId = $mvc->getCourierApi4Document($id)){
            $Iface = cls::getInterface('cond_CourierApiIntf', $apiDriverId);
            $defaultEmailTpl = $Iface->getDefaultEmailBody($mvc, $id);
            if(!empty($defaultEmailTpl)){
                $tpl->append($defaultEmailTpl);
            }
        }
    }
}