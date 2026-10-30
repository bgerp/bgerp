<?php


/**
 * Документ по чл. 52о от Наредба Н-18 за платените с карта онлайн поръчки
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_plg_EshopCarts extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Данните от шлюза се пазят, за да може документът да се създаде и по-късно
        $mvc->FLD('n18PaymentData', 'blob(serialize, compress)', 'input=none,column=none,single=none');
    }


    /**
     * След създаване на продажбата по количката - преди имейла за поръчката
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec         - количката
     * @param stdClass $saleRec     - продажбата
     * @param array    $paymentData - данни за картовото плащане
     */
    public static function on_AfterForceSale($mvc, $rec, $saleRec, $paymentData)
    {
        if (empty($paymentData) || !self::isN18CardPayment($rec)) {

            return;
        }

        // Условията при плащането - повторният опит не зависи от по-късна смяна на настройките
        $settings = cms_Domains::getSettings($rec->domainId);
        $paymentData['shopNumber'] = $settings->n18ShopNumber;
        $paymentData['paymentCode'] = cond_PaymentMethods::getOnlinePaymentDriver($rec->paymentId)->getN18PaymentCode($rec->paymentId);
        $rec->n18PaymentData = $paymentData;

        // Неуспешният запис не спира плащането - документът се опитва с данните в паметта
        try {
            $mvc->save($rec, 'n18PaymentData');
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            reportException($e);
            $mvc->logErr('Данните за картовото плащане не са съхранени - повторен опит за документ по чл. 52о няма да е възможен: ' . $e->getMessage(), $rec->id);
        }

        self::createDoc($mvc, $rec, $saleRec);
    }


    /**
     * Създава документа по запазените в количката данни за плащането
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec     - количката
     * @param stdClass $saleRec - продажбата
     *
     * @return stdClass|null - документът или null, ако не е създаден
     */
    public static function createDoc($mvc, $rec, $saleRec)
    {
        $paymentData = (array) ($rec->n18PaymentData ?? array());

        // Без референция плащането не може да се регистрира - шлюзът трябва да я подаде
        if (empty($paymentData['transactionRef'])) {
            $mvc->logWarning('Картово плащане без референция на трансакцията - не е създаден документ по чл. 52о', $rec->id);

            return null;
        }

        // Неуспехът не бива да спира плащането и продажбата - документът се създава после от бутона в количката
        try {
            $docRec = bgfisc_SaleRegDocs::create($rec, $saleRec, $paymentData);
            $mvc->logDebug('Документ по чл. 52о №' . bgfisc_SaleRegDocs::formatNumber($docRec->number), $rec->id);

            return $docRec;
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            reportException($e);
            $mvc->logErr('Грешка при създаване на документ по чл. 52о: ' . $e->getMessage(), $rec->id);
        }

        return null;
    }


    /**
     * Дали поръчката е платена с карта в магазин, регистриран по Н-18
     *
     * @param stdClass $rec - количката
     *
     * @return bool
     */
    public static function isN18CardPayment($rec)
    {
        $settings = cms_Domains::getSettings($rec->domainId);
        if (!is_object($settings) || ($settings->n18Mode ?? null) != 'yes' || empty($settings->n18ShopNumber) || empty($rec->paymentId)) {

            return false;
        }

        $Driver = cond_PaymentMethods::getOnlinePaymentDriver($rec->paymentId);

        return is_object($Driver) && !empty($Driver->getN18PaymentCode($rec->paymentId));
    }


    /**
     * След подготовка на тулбара на единичния изглед - бутон към документа или за повторен опит
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        if (empty($rec->n18PaymentData)) {

            return;
        }

        $docId = bgfisc_SaleRegDocs::fetchField("#cartId = {$rec->id}", 'id');
        if (!empty($docId)) {
            if (bgfisc_SaleRegDocs::haveRightFor('single', $docId)) {
                $data->toolbar->addBtn('Документ по чл. 52о', array('bgfisc_SaleRegDocs', 'single', $docId, 'ret_url' => true), 'ef_icon=img/16/page_white_text.png,title=Документ за регистриране на продажбата');
            }
        } elseif (bgfisc_SaleRegDocs::haveRightFor('createforcart', $rec)) {
            $data->toolbar->addBtn('Документ по чл. 52о', array('bgfisc_SaleRegDocs', 'createforcart', $rec->id, 'ret_url' => true), 'ef_icon=img/16/error.png,title=Документът не е създаден при плащането - нов опит със запазените данни');
        }
    }
}
