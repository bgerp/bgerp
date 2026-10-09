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
        $mvc->FLD('courierApiPrice', 'double', 'input=none');
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

            // Неизясненият опит се потвърждава през предупреждението, след проверка при куриера
            if($form->cmd == 'save'){
                $attempt = self::getPendingAttempt($rec->containerId);
                if($attempt){
                    $date = dt::mysql2verbal($attempt->createdOn, 'd.m.Y H:i');
                    $form->setWarning(key($form->fields), "Опитът за товарителница от|* {$date} |остана без ясен резултат. Проверете в системата на куриера дали не е създадена|*!");
                }
            }

            if($form->isSubmitted()){
                $submitted = self::submitBillOfLading($mvc, $rec, $Driver, $form);
                if(!empty($submitted->fh)){
                    followRetUrl(null, "Товарителницата е изпратена успешно|*!");
                }

                if(is_object($submitted->tpl)){
                    $form->info = $submitted->tpl;
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
     * Изпраща (cmd=save) или изчислява (cmd=calc) въведената форма за товарителница
     *
     * @param core_Mvc $mvc         - документ
     * @param stdClass $rec         - запис на документа
     * @param cond_CourierApiIntf $Driver - куриерското API на документа
     * @param core_Form $form       - въведената форма за товарителница
     * @param bool|null $allowExisting - null от UI-то; false само ако няма издадени; true и при издадени.
     *                                   Неизяснен опит спира изпращането, освен в UI с потвърдено предупреждение
     * @param bool $saveCalcPrice   - дали изчислената цена да се запише в документа (проверка на цена от ИИ - не)
     * @return stdClass $res        - fh, number, status (cond_CourierApiIntf::BOL_* или noRights/exists/pendingCheck/unsupported/busy),
     *                                tpl на изчислението и price от драйвера
     */
    public static function submitBillOfLading($mvc, $rec, $Driver, $form, $allowExisting = null, $saveCalcPrice = true)
    {
        $res = (object)array('fh' => null, 'tpl' => null, 'price' => null, 'number' => null, 'status' => null);
        if(!in_array($form->cmd, array('save', 'calc'))) return $res;

        // Извикванията извън екшъна също минават през правата и валидацията
        if(!$mvc->haveRightFor('requestbilloflading', $rec)){
            $res->status = 'noRights';

            return $res;
        }
        if(!$form->isSubmitted()){
            $res->status = cond_CourierApiIntf::BOL_REJECTED;

            return $res;
        }

        if($form->cmd == 'calc'){
            $calculatedShipmentRes = $Driver->calculateShipmentRes($mvc, $rec, $form);
            if(is_object($calculatedShipmentRes->tpl ?? null)){
                $res->tpl = $calculatedShipmentRes->tpl;
                $res->price = $calculatedShipmentRes->price ?? null;
                if ($saveCalcPrice) {
                    self::saveCourierApiPrice($mvc, $rec, $res->price);
                }
            }

            return $res;
        }

        // Две едновременни изпращания към документа биха издали две товарителници
        $errField = key($form->fields);
        $lockKey = "courierBillOfLading_{$rec->containerId}";
        if(!core_Locks::obtain($lockKey, 300, 0, 0)){
            $res->status = 'busy';
            $form->setError($errField, 'В момента се изпраща друга товарителница към документа|*!');

            return $res;
        }

        try{
            $res->status = self::getSubmitBlocker($rec->containerId, $Driver, $allowExisting, $form);
            if(isset($res->status)) return $res;

            // Опитът се записва преди заявката: ако процесът прекъсне, остава за сверяване
            $attemptKey = self::getPendingAttemptKey($rec->containerId);
            $attempt = (object)array('createdOn' => dt::now(), 'createdBy' => core_Users::getCurrent('id', false), 'driver' => get_class($Driver->class));
            core_Permanent::set($attemptKey, $attempt, core_Permanent::FOREVER_VALUE);

            $requestedShipment = $Driver->getRequestedShipmentRes($mvc, $rec, $form);

            // Драйвер без състояние: без файл не е ясно дали е издадена
            $res->status = $requestedShipment->status ?? (!empty($requestedShipment->fh) ? cond_CourierApiIntf::BOL_ISSUED : cond_CourierApiIntf::BOL_UNKNOWN);
            $res->number = $requestedShipment->number ?? null;
            $res->price = $requestedShipment->price ?? null;

            if($res->status == cond_CourierApiIntf::BOL_UNKNOWN){
                $form->setError($errField, 'Не е ясно дали товарителницата е създадена. Проверете в системата на куриера преди нов опит|*!');
            } else {
                core_Permanent::remove($attemptKey);
            }

            if(in_array($res->status, array(cond_CourierApiIntf::BOL_ISSUED, cond_CourierApiIntf::BOL_ISSUED_NO_PDF))){
                $mvc->logWrite("Създаване на товарителница", $rec->id);
                self::saveCourierApiPrice($mvc, $rec, $res->price);
            }

            if(!empty($requestedShipment->fh) && !$form->gotErrors()){
                $fileId = fileman::fetchByFh($requestedShipment->fh, 'id');
                doc_Linked::add($rec->containerId, $fileId, 'doc', 'file', $Driver->class->billOfLadingComment);
                $res->fh = $requestedShipment->fh;
            }
        } finally {
            core_Locks::release($lockKey);
        }

        return $res;
    }


    /**
     * Защо не може да се изпрати товарителница към документа или null, ако може
     *
     * @return string|null - pendingCheck, unsupported или exists
     */
    private static function getSubmitBlocker($containerId, $Driver, $allowExisting, $form)
    {
        // В UI потребителят потвърждава през предупреждението, че е проверил при куриера
        $confirmedInUi = ($allowExisting === null) && !empty($form->ignore);
        if(self::getPendingAttempt($containerId) && !$confirmedInUi) return 'pendingCheck';

        // UI-то и изричната допълнителна товарителница не зависят от издадените
        if($allowExisting !== false) return null;

        $existing = $Driver->getBillOfLadings($containerId);
        if(!is_array($existing)) return 'unsupported';

        // Анулираните при куриера не пречат на нова
        $existing = array_filter($existing, function ($bill) { return ($bill->state ?? null) != 'rejected'; });

        return countR($existing) ? 'exists' : null;
    }


    /**
     * Неизясненият опит за товарителница към документа, ако има
     *
     * @param int $containerId
     * @return stdClass|null - createdOn, createdBy, driver
     */
    public static function getPendingAttempt($containerId)
    {
        $attempt = core_Permanent::get(self::getPendingAttemptKey($containerId));

        return is_object($attempt) ? $attempt : null;
    }


    /**
     * Оттегля връзката на документа с файла на отказана товарителница и я отбелязва като отказана
     *
     * @param int         $containerId - контейнер на документа
     * @param string|null $fh          - файл на товарителницата
     * @return void
     */
    public static function rejectBillOfLadingLink($containerId, $fh)
    {
        $fileId = empty($fh) ? null : fileman::fetchByFh($fh, 'id');
        if(empty($fileId)){

            return;
        }

        $Linked = cls::get('doc_Linked');
        $query = $Linked->getQuery();
        $query->where(array("#outType = 'doc' AND #outVal = [#1#] AND #inType = 'file' AND #inVal = [#2#] AND #state != 'rejected'", $containerId, $fileId));
        while($lRec = $query->fetch()){
            // „Товарителница (Speedy)“ -> „Отказана товарителница (Speedy)“; вече отбелязаният не се пипа
            $comment = $lRec->comment ?? '';
            if(mb_stripos($comment, 'отказан') !== 0){
                $comment = strlen($comment) ? mb_strtolower(mb_substr($comment, 0, 1)) . mb_substr($comment, 1) : 'товарителница';
                $lRec->comment = "Отказана {$comment}";
                $Linked->save_($lRec, 'comment');
            }
            $Linked->reject($lRec->id);
        }
    }


    /**
     * Маха неизяснения опит, след като е проверено в системата на куриера
     *
     * @param int $containerId
     */
    public static function clearPendingAttempt($containerId)
    {
        core_Permanent::remove(self::getPendingAttemptKey($containerId));
    }


    /**
     * Ключ на неизяснения опит за товарителница
     */
    private static function getPendingAttemptKey($containerId)
    {
        return "courierBillOfLadingAttempt_{$containerId}";
    }


    /**
     * Записва цената от куриерското API във валутата на документа
     */
    private static function saveCourierApiPrice($mvc, $rec, $price)
    {
        if(!is_object($price)) return;

        $rec->courierApiPrice = currency_CurrencyRates::convertAmount($price->total ?? null, $rec->{$mvc->valiorFld}, $price->currency ?? null);
        $mvc->save_($rec, 'courierApiPrice');
    }


    /**
     * Преди рендиране на сингъла
     *
     * @param core_Mvc $mvc
     * @param core_Et  $tpl
     * @param stdClass $data
     */
    public static function on_BeforeRenderSingle($mvc, &$tpl, $data)
    {
        $rec = $data->rec;
        if($mvc->lineFieldName ?? null){
            if(!empty($rec->courierApiPrice) && !Mode::isReadOnly()){
                $courierApiPrice = doc_plg_HidePrices::canSeePriceFields($mvc, $rec) ? currency_Currencies::decorate($rec->courierApiPrice, $rec->currencyId) : doc_plg_HidePrices::getBuriedElement();
                $data->row->{$mvc->lineFieldName} ??= '';
                $data->row->{$mvc->lineFieldName} .= " {$courierApiPrice}";
            }
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
