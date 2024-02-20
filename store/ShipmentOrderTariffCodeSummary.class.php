<?php


/**
 * Клас 'store_ShipmentOrderTariffCodeSummary'
 *
 * Мениджър за обобщаващите редове на Packing list за митница
 * @see store_tpl_SingleLayoutPackagingListGrouped
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ShipmentOrderTariffCodeSummary extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_Wrapper';


    /**
     * Кой може да го добавя?
     */
    public $title = 'Обобщен ред за Packing list за митница';


    /**
     * Кой може да го листва?
     */
    public $canList = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да го променя?
     */
    public $canModify = 'powerUser';


    /**
     * Кой може да го изтрива?
     */
    public $canDelete = 'debug';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'shipmentId, tariffCode=МТК->Код, displayTariffCode=МТК->Показване, displayDescription=МТК->Описание, weight, netWeight, tareWeight, transUnits=ЛЕ, amount';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=store_ShipmentOrders,select=id)', 'caption=ЕН,input=hidden,silent,mandatory');
        $this->FLD('tariffCode', 'varchar','input=hidden,silent');
        $this->FLD('displayTariffCode', 'varchar','caption=Митнически тарифен код->Код');
        $this->FLD('displayDescription', 'varchar','caption=Митнически тарифен код->Описание,class=copyPlaceholderAsVal');

        $this->FLD('weight', 'cat_type_Weight', 'caption=Тегло->Бруто');
        $this->FLD('netWeight', 'cat_type_Weight', 'caption=Тегло->Нето');
        $this->FLD('tareWeight', 'cat_type_Weight', 'caption=Тегло->Тара');
        $this->FLD('transUnits', 'blob(serialize, compress)', 'input');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Друго->Сума');
        $this->setDbUnique('shipmentId,tariffCode');
    }


    /**
     * Намира записа съответстващ на това ЕН и МТК
     *
     * @param int $shipmentId
     * @param string $tariffCode
     * @return mixed|null
     */
    public static function getRec($shipmentId, $tariffCode)
    {
        return static::fetch(array("#shipmentId = [#1#] AND #tariffCode = '[#2#]'", $shipmentId, $tariffCode));
    }


    /**
     * Кой може да го променя
     */
    public function act_Modify()
    {
        $this->requireRightFor('modify');
        $shipmentId = Request::get('shipmentId', 'int');
        $tariffCode = Request::get('tariffCode', 'varchar');
        $this->requireRightFor('modify', (object)array('shipmentId' => $shipmentId, 'tariffCode' => $tariffCode));
        $exRec = static::getRec($shipmentId, $tariffCode);
        $shipmentRec = store_ShipmentOrders::fetch($shipmentId);

        $form = static::getForm();
        $form->title = core_Detail::getEditTitle('store_ShipmentOrders', $shipmentId, 'митническа информация', $exRec->id);
        $form->input(null, 'silent');
        $dCode = ($form->rec->tariffCode == store_tpl_SingleLayoutPackagingListGrouped::EMPTY_TARIFF_NUMBER) ? tr('Без тарифен код') : $form->rec->tariffCode;
        $form->setField('displayTariffCode', "placeholder={$dCode},caption=Митнически код|*: <b>{$dCode}</b>->Код");
        $form->setField('displayDescription', "caption=Митнически код|*: <b>{$dCode}</b>->Описание");
        if(is_object($exRec)) {
            foreach (array('displayTariffCode', 'displayDescription', 'weight', 'netWeight', 'tareWeight', 'transUnits', 'amount') as $fld) {
                $form->setDefault($fld, $exRec->{$fld});
            }
        }

        // Зареждане на плейсхолдъри от рекуеста
        $transUnitCaption = "Логистична еденици->ЛЕ";
        if($form->cmd != 'save' && $form->cmd != 'empty'){
            if($weightPlaceholder = Request::get('weight', 'varchar')){
                Mode::push('verbalWithoutSuffix', true);
                $weightPlaceholderVerbal = $form->getFieldType('weight')->toVerbal($weightPlaceholder);
                Mode::pop('verbalWithoutSuffix');
                $form->setField('weight', "placeholder={$weightPlaceholderVerbal}");
            }
            if($netWeightPlaceholder = Request::get('netWeight', 'varchar')){
                Mode::push('verbalWithoutSuffix', true);
                $netWeightPlaceholderVerbal = $form->getFieldType('netWeight')->toVerbal($netWeightPlaceholder);
                Mode::pop('verbalWithoutSuffix');
                $form->setField('netWeight', "placeholder={$netWeightPlaceholderVerbal}");
            }

            if($tareWeightPlaceholder = Request::get('tareWeight', 'varchar')){
                Mode::push('verbalWithoutSuffix', true);
                $tareWeightPlaceholderVerbal = $form->getFieldType('tareWeight')->toVerbal($tareWeightPlaceholder);
                Mode::pop('verbalWithoutSuffix');
                $form->setField('tareWeight', "placeholder={$tareWeightPlaceholderVerbal}");
            }

            if($amountPlaceholder = Request::get('amount', 'double')){
                $amountPlaceholderVerbal = $form->getFieldType('amount')->toVerbal($amountPlaceholder);
                $form->setField('amount', "placeholder={$amountPlaceholderVerbal}");
                $amountUnit = "{$shipmentRec->currencyId} " . (($shipmentRec->chargeVat == 'yes' || $shipmentRec->chargeVat == 'separate') ? tr('|с ДДС|*') : tr('|без ДДС|*'));
                $form->setField('amount', "placeholder={$amountPlaceholderVerbal}");
                $form->setField('amount', "unit={$amountUnit}");
            }

            if($transUnits = Request::get('transUnits')){
                $transUnits = strip_tags(trans_Helper::displayTransUnits($transUnits));
                $transUnitCaption = "Логистична еденици->{$transUnits}->ЛЕ";
            }

            if($displayDescription = Request::get('displayDescription')){
                $form->setField('displayDescription', "placeholder={$displayDescription}");
            }
        }

        // Визуализиране на хинт с избраните ЛЕ и показване на таблицата за задаване на конкретни
        $units = trans_TransportUnits::getAll();
        $form->setFieldType('transUnits', 'table(columns=unitId|quantity,captions=Вид|Брой,validate=trans_LineDetails::validateTransTable)');
        $form->setField('transUnits', "caption={$transUnitCaption}");
        $form->setFieldTypeParams('transUnits', array('unitId_opt' => array('' => '') + $units));

        $form->input();
        if($form->isSubmitted()){
            $fRec = $form->rec;

            if($form->cmd == 'save'){
                $isEmpty = empty($fRec->displayTariffCode) && empty($fRec->displayDescription) && !isset($fRec->weight) && !isset($fRec->netWeight) && !isset($fRec->tareWeight) && !isset($fRec->transUnits) && !isset($fRec->amount);
            } else {
                $isEmpty = true;
            }

            // Ако ще се нулира изтрива се съществуващия запис
            if(is_object($exRec)){
                if($isEmpty){
                    static::delete($exRec->id);
                    store_ShipmentOrders::logWrite('Промяна на обощения ред за МТК', $shipmentRec->id);
                    followRetUrl(null, 'Промените са записани успешно');
                }
                $fRec->id = $exRec->id;
            }

            if(!$isEmpty){
                static::save($fRec);
                store_ShipmentOrders::logWrite('Промяна на обощения ред за МТК', $shipmentRec->id);
            }

            followRetUrl(null, 'Промените са записани успешно');
        }

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        if(is_object($exRec)){
            $form->toolbar->addSbBtn('Нулиране', 'empty', 'ef_icon = img/16/reject.png,warning=Наистина ли искате да нулирате ръчно въведените данни|*?');
        }
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        // Рендиране на формата
        $tpl = $form->renderHtml();
        $tpl = $this->renderWrapping($tpl);
        jquery_Jquery::run($tpl, 'copyPlaceholderAsValOnClick()');

        return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if(in_array($action, array('modify', 'delete')) && isset($rec)){
            if(empty($rec->shipmentId)){
                $requiredRoles = 'no_one';
            } elseif(!store_ShipmentOrders::haveRightFor('edit', $rec->shipmentId)){
                $requiredRoles = 'no_one';
            } else {

                // Само ако обработвача на шаблона е за packing list за митница да може да се модифицира обощения ред
                $templateId = store_ShipmentOrders::fetchField($rec->shipmentId, 'template');
                $Handle = doc_TplManager::getTplScriptClass($templateId);
                if($Handle){
                    if(!($Handle instanceof store_tpl_SingleLayoutPackagingListGrouped)){
                        $requiredRoles = 'no_one';
                    }
                } else {
                    $requiredRoles = 'no_one';
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if($rec->tariffCode == store_tpl_SingleLayoutPackagingListGrouped::EMPTY_TARIFF_NUMBER){
            $row->tariffCode = tr('Без тарифен код');
        }
        $row->shipmentId = store_ShipmentOrders::getLink($rec->shipmentId, 0);
        $row->transUnits = strip_tags(trans_Helper::displayTransUnits(trans_Helper::convertTableToNormalArr($rec->transUnits)));
    }
}