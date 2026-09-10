<?php


/**
 * Клас 'doc_plg_HidePrices' сквиращ ценови полета, които са посочени в
 * променливата 'priceFields'. Само потребителите с определени права могат
 * да виждат полетата, останалите виждат празни колони.
 *
 * Плъгина може да се прикачи както към Master така и към Detail.
 * Дава възможност с дефинирането на метод 'hidePriceFields' да се направи
 * скриване специфично за модела.
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_HidePrices extends core_Plugin
{
    /**
     * След инициализирането на модела
     *
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription($mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     *
     * @param core_Mvc $mvc
     *
     * @return bool
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            
            return false;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);
        
        if (isset($plugins['doc_DocumentPlg'])) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Дали потребителя може да вижда чувствителната информация
     */
    public static function canSeePriceFields($mvc, $rec)
    {
        if(haveRole('noPrice')) return false;

        // Ако има изброените роли, може да вижда цените
        $mvc = cls::get($mvc);
        if(($mvc instanceof deals_PaymentDocument) || ($mvc instanceof crm_Persons) ||
            ($mvc instanceof cash_InternalMoneyTransfer) || ($mvc instanceof bank_InternalMoneyTransfer) ||
            ($mvc instanceof cash_ExchangeDocument) || ($mvc instanceof bank_ExchangeDocument) || ($mvc instanceof deals_OpenDeals)){
            if(haveRole('ceo,seePrice')) return true;
        } elseif(($mvc instanceof sales_Quotations) || ($mvc instanceof eshop_Carts)){
            if(haveRole('ceo,seePriceSale')) return true;
        } elseif($mvc instanceof purchase_Quotations){
            if(haveRole('ceo,seePricePurchase')) return true;
        } elseif($mvc instanceof findeals_AdvanceReports){
            if(haveRole('ceo,pettyCashReport')) return true;
        }  elseif($mvc instanceof pos_Receipts|| $mvc instanceof pos_Reports){
            if(haveRole('ceo, pos')) return true;
        } elseif(isset($rec->threadId)){
            if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)){
                if($firstDocument->isInstanceOf('sales_Sales')){
                    if(haveRole('ceo,seePriceSale')) return true;
                } elseif($firstDocument->isInstanceOf('purchase_Purchases')){
                    if(haveRole('ceo,seePricePurchase')) return true;
                } elseif($firstDocument->isInstanceOf('findeals_AdvanceDeals')){
                    if($mvc instanceof purchase_Invoices || $mvc instanceof findeals_AdvanceDeals){
                        if(haveRole('ceo,pettyCashReport')) return true;
                    } else {
                        if(haveRole('ceo,seePrice')) return true;
                    }
                } else {
                    if(haveRole('ceo,seePrice')) return true;
                }
            }
        } elseif(is_null($rec)){
            if($mvc instanceof sales_Sales || $mvc instanceof store_ShipmentOrders || $mvc instanceof sales_Proformas || $mvc instanceof sales_Invoices || $mvc instanceof sales_Services){
                if(haveRole('ceo,seePrice')) return true;
            } elseif($mvc instanceof purchase_Purchases || $mvc instanceof purchase_Invoices || $mvc instanceof store_Receipts || $mvc instanceof purchase_Services){
                if(haveRole('ceo,seePricePurchase')) return true;
            }
        }

        if(isset($rec->threadId)){
            $threadRec = doc_Threads::fetch($rec->threadId);
        }

        // Ако е контрактор, и е инсталиран пакета за контрактови и имаме тред
        if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab') && isset($threadRec)) {

            // Ако контрактора може да види треда от външната част, то може и да види цялата ценова информация
            if (colab_Threads::haveRightFor('single', $threadRec)) {

                return true;
            }
        }
        
        // Ако потребителя е системен и е указано че той има достъп до сингъла
        if (Mode::is('isSystemCanSingle')){
            $cu = core_Users::getCurrent('id', FALSE);
            if( isset($cu) && $cu == core_Users::SYSTEM_USER){
                
                return true;
            }
        }

        // Ако документа е нишка на продажба и тя е с видими цени да се показват
        if(isset($firstDocument)){
            if($firstDocument->isInstanceOf('sales_Sales')){
                $visiblePricesByAllInThread = $firstDocument->fetchField('visiblePricesByAllInThread');

                return ($visiblePricesByAllInThread == 'yes');
            }
        }

        // Ако горните не са изпълнени, потребителя няма право да вижда цените/сумите по документите
        return false;
    }
    
    
    /**
     * След рендиране на изгледа се скриват ценовите данни от мастъра
     * ако потребителя няма права
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if (self::canSeePriceFields($mvc, $data->rec) || $data->dontHidePrices === true) {
            
            return;
        }
        
        $mvc->hidePriceFields($data);
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    public static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
        if (self::canSeePriceFields($mvc, $data->rec) || $data->dontHidePrices === true) {
            
            return;
        }
        
        // Флаг да не се подготвя общата сума
        $data->noTotal = true;
    }
    
    
    /**
     * След рендиране на детайлите се скриват ценовите данни от резултатите
     * ако потребителя няма права
     */
    public static function on_AfterPrepareDetail($mvc, $res, &$data)
    {
        if (self::canSeePriceFields($data->masterMvc, $data->masterData->rec) || $data->dontHidePrices === true) {
            
            return;
        }
        
        $mvc->hidePriceFields($data);
        
        // Флаг да не се подготвя общата сума
        $data->noTotal = true;
    }
    
    
    /**
     * Ф-я скриваща всички вербални полета от мастъра или детайла, които
     * са посочени във променливата 'priceFields'
     */
    public static function on_AfterHidePriceFields($mvc, $res, &$data)
    {
        $priceFields = arr::make($mvc->priceFields ?? null);
        
        if (countR($data->rows)) {
            foreach ($data->rows as $row) {
                self::unsetPriceFields($row, $priceFields);
            }
        }
        
        if ($data->row) {
            self::unsetPriceFields($data->row, $priceFields);
        }
        
        if (!$data) {
            $data = new stdClass();
        }
    }


    /**
     * Какъв е скритие елемент, с който ще се замести чувствителната информация
     * @return string
     */
    public static function getBuriedElement()
    {
        $title = tr("Нямате права да виждате сумата/цената");

        return "<span class='confidential-field' title = '{$title}'>" . tr('заличено||buried'). "</span>";
    }


    /**
     * Ф-я махаща всички полета от вербален запис, които са маркирани
     */
    private static function unsetPriceFields(&$row, $fields)
    {
        if (countR($fields)) {
            foreach ($fields as $name) {
                $row->{$name} = static::getBuriedElement();
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if(self::canSeePriceFields($mvc, null)){
            return;
        }

        if (isset($mvc->Master) && self::canSeePriceFields($mvc->Master, $data->masterRec)){

            return;
        }

        $form = &$data->form;
        $priceFields = arr::make($mvc->priceFields ?? null);

        foreach ($priceFields as $fld){
            if($form->getField($fld, false)){
                $form->setField($fld, 'input=none');
            }
        }

        // Помним опаковка/цена преди потребителския input - нужно е при
        // добавяне/клониране на нов ред (без id), за да познаем дали опаковката
        // е сменена спрямо предварително попълнената стойност, и да можем да
        // пренесем старата единична цена (@see on_AfterInputEditForm)
        if (!empty($mvc->packagingFld)) {
            $rec = $form->rec;
            $form->_hidePricesOldSnapshot = (object) array(
                'packaging' => $rec->{$mvc->packagingFld} ?? null,
                'unitPrice' => self::getOldUnitPrice($mvc, $rec),
                'discount' => $rec->discount ?? null,
            );
        }
    }


    /**
     * Единичната (packaging-invariant) цена на реда, изчислена само от
     * РЕАЛНИ (персистирани) полета - никога от FNC, защото обикновен fetch()
     * не смята FNC полета (нямат истинска колона в базата). При различните
     * наследници на doc_Detail тя идва от различно поле:
     * - deals_DealDetail/deals_DeliveryDocumentDetail/deals_InvoiceDetail:
     *   'price' е реално FLD поле (вече е единична цена)
     * - store_InternalDocumentDetail (ConsignmentProtocolDetailsSend/Received):
     *   няма отделно 'price'; 'packPrice' е реално FLD (цена за ОПАКОВКА),
     *   значи единичната цена е packPrice/quantityInPack
     */
    private static function getOldUnitPrice($mvc, $rec)
    {
        if (self::isRealField($mvc, 'price') && isset($rec->price)) {

            return $rec->price;
        }

        if (self::isRealField($mvc, 'packPrice') && !empty($rec->quantityInPack) && isset($rec->packPrice)) {

            return $rec->packPrice / $rec->quantityInPack;
        }

        return null;
    }


    /**
     * Дали посоченото поле е реално (персистирано - FLD/EXT/XPR), а не FNC
     */
    private static function isRealField($mvc, $field)
    {
        return isset($mvc->fields[$field]) && ($mvc->fields[$field]->kind ?? null) != 'FNC';
    }


    /**
     * След въвеждане на формата обработва скритите ценови полета, които зависят
     * от променена опаковка (потребителят няма право да ги вижда/попълва ръчно).
     * Слага '_hidePricesOldUnitPrice' на $rec (packaging-invariant единична
     * цена от преди промяната, @see getOldUnitPrice) и маха 'packPrice' и
     * другите засегнати полета - конкретният детайл (@see deals_DealDetail,
     * deals_DeliveryDocumentDetail, deals_InvoiceDetail,
     * store_InternalDocumentDetail) ползва тази стойност директно вместо да
     * търси нова цена от ценовата политика, която може да върне съвсем друга
     * цена от ръчно въведената преди потребителя без права да смени опаковката,
     * а и може изобщо да няма намерена цена, което би блокирало записа с
     * грешка върху скрито поле.
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted() || empty($mvc->packagingFld)) {
            return;
        }

        $rec = &$form->rec;

        $packagingFld = $mvc->packagingFld;
        if (!$form->getField($packagingFld, false)) {
            return;
        }

        if (self::canSeePriceFields($mvc, null)) {
            return;
        }

        if (isset($mvc->Master, $mvc->masterKey) && !empty($rec->{$mvc->masterKey})) {
            $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            if (self::canSeePriceFields($mvc->Master, $masterRec)) {
                return;
            }
        }

        // Старата опаковка/цена - от БД при промяна на съществуващ ред, или тези
        // от преди потребителския input при добавяне/клониране на нов ред
        if (!empty($rec->id)) {
            $oldRec = $mvc->fetch($rec->id);
            $oldPackaging = $oldRec->{$packagingFld} ?? null;
            $oldUnitPrice = self::getOldUnitPrice($mvc, $oldRec);
            $oldDiscount = $oldRec->discount ?? null;
        } else {
            $snapshot = $form->_hidePricesOldSnapshot ?? null;
            $oldPackaging = $snapshot->packaging ?? null;
            $oldUnitPrice = $snapshot->unitPrice ?? null;
            $oldDiscount = $snapshot->discount ?? null;
        }

        $newPackaging = $rec->{$packagingFld} ?? null;
        if ($oldPackaging == $newPackaging) {
            return;
        }

        // Packaging-invariant единичната цена - пренасяме я директно, за да я
        // ползва конкретният детайл вместо ценова политика
        if (isset($oldUnitPrice)) {
            $rec->_hidePricesOldUnitPrice = $oldUnitPrice;
        }

        $removeAndRefresh = $form->getFieldParam($packagingFld, 'removeAndRefreshForm');
        $refreshFields = arr::make(str_replace('|', ',', $removeAndRefresh ?? ''), true);
        $priceFields = arr::make($mvc->priceFields ?? null, true);
        $fieldsToUnset = array_intersect_key($refreshFields, $priceFields);

        foreach ($fieldsToUnset as $field => $dummy) {
            unset($rec->{$field});
        }

        // При пренасяне на старата единична цена се запазва и отстъпката от
        // същия ред. Иначе специалният клон не стига до ценовата политика и
        // изчистената от removeAndRefreshForm отстъпка би се загубила.
        if (array_key_exists('discount', $fieldsToUnset) && isset($oldDiscount)) {
            $rec->discount = $oldDiscount;
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'exportdoc' && isset($rec)){
            if(!static::canSeePriceFields($mvc, $rec)){
                $requiredRoles = 'no_one';
            }
        }
    }

    /**
     * Точно преди рендирането на лист таблицата (но след всичките on_BeforeRenderListTable)
     *
     * @param $mvc
     * @param $res
     * @param $data
     * @return void
     */
    public static function on_RightBeforeRenderListTable($mvc, $res, &$data)
    {
        if(isset($data->masterMvc)) return;

        $priceFields = arr::make($mvc->priceFields ?? null);

        // За всеки запис от листа се гледа дали може да му се виждат ценовите полета - ако не скриват се
        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            if(!self::canSeePriceFields($mvc, $rec)){
                self::unsetPriceFields($row, $priceFields);
            }
        }
    }
}
