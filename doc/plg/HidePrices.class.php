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
        // Ако има изброените роли, може да вижда цените
        $mvc = cls::get($mvc);
        if(($mvc instanceof deals_PaymentDocument) || ($mvc instanceof crm_Persons)){
            if(haveRole('ceo,seePrice')) return true;
        } elseif($mvc instanceof sales_Quotations){
            if(haveRole('ceo,seePriceSale')) return true;
        } elseif($mvc instanceof purchase_Quotations){
            if(haveRole('ceo,seePricePurchase')) return true;
        } elseif($mvc instanceof findeals_AdvanceReports){
            if(haveRole('ceo,pettyCashReport')) return true;
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
        $priceFields = arr::make($mvc->priceFields);
        
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
        return "<span class='confidential-field'>" . tr('заличено||buried'). "</span>";
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
        $priceFields = arr::make($mvc->priceFields);
        
        foreach ($priceFields as $fld){
            if($form->getField($fld, false)){
                $form->setField($fld, 'input=none');
            }
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
}
