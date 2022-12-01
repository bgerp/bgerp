<?php
/**
 * Плъгин за връзка към външна система за генериране на товарителница
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_plg_DeliveryServiceExternalRequest extends core_Plugin
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

        //@todo да се взима от условие на доставка
        $Driver = cls::get('speedy_interface_ApiImpl');
        $driverClassId = $Driver->getClassId();

        // Ако протокол може да се добавя към треда и не се експедира на момента
        if ($mvc->haveRightFor('requestbilloflading', (object)array('objectId' => $rec->id, 'requestDriverId' => $driverClassId))) {
            $serviceUrl = array($mvc, 'requestBillOfLading', 'requestDriverId' => $driverClassId, 'objectId' => $rec->id, 'ret_url' => true);
            $data->toolbar->addBtn($Driver->requestBillOfLadingBtnCaption, $serviceUrl, "ef_icon = {$Driver->requestBillOfLadingBtnIcon},title=Създаване на нова товарителница");
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
            $objectRec = $mvc->fetch($rec->objectId);
            if(empty($rec->requestDriverId)){
                $requiredRoles = 'no_one';
            } else {
                $Driver = cls::get($rec->requestDriverId);
                if(!$Driver->canRequestBillOfLading($mvc, $objectRec, $userId)){
                    $requiredRoles = 'no_one';
                }
            }

            if($requiredRoles != 'no_one'){
                if($objectRec->state != 'active'){
                    $requiredRoles = 'no_one';
                } else {
                    if($mvc instanceof sales_Sales){
                        $actions = type_Set::toArray($objectRec->contoActions);
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
            expect($driverId = Request::get('requestDriverId'));
            $mvc->requireRightFor('requestbilloflading', (object)array('objectId' => $id, 'requestDriverId' => $driverId));

            // Подаване на формата на драйвера
            $Driver = cls::get($driverId);
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
                            doc_Linked::add($rec->containerId, $fileId, 'doc', 'file', 'Товарителница');
                            followRetUrl(null, "Успешно генерирана товарителница|*!");
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
            $form->toolbar->addSbBtn('Изпращане', 'save', 'ef_icon = img/16/speedy.png, title = Изпращане на товарителницата,id=save');
            $form->toolbar->addSbBtn('Изчисли', 'calc', 'ef_icon = img/16/calculator.png, title = Изчисляване на на товарителницата');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

            // Записваме, че потребителя е разглеждал този списък
            $mvc->logInfo('Форма за генериране на товарителница на Speedy');

            $res = $mvc->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($res, $form);

            return false;
        }
    }
}