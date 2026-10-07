<?php


/**
 * Интерфейс за връзка към куриерско API
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_CourierApiIntf extends embed_DriverIntf
{
    /**
     * Състояния на изпратена товарителница
     */
    const BOL_ISSUED = 'issued';
    const BOL_ISSUED_NO_PDF = 'issuedNoPdf';
    const BOL_REJECTED = 'rejected';
    const BOL_UNKNOWN = 'unknown';
    const BOL_CANCELLED = 'cancelled';


    /**
     * Роли по дефолт, които изисква драйвера
     */
    public $requireRoles;


    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;


    /**
     * Заглавие на  бутон за създаване на товарителница
     */
    public $requestBillOfLadingBtnCaption;


    /**
     * Иконка за бутон за създаване на товарителница
     */
    public $requestBillOfLadingBtnIcon;


    /**
     * Коментар към връзката на прикачения файл
     */
    public $billOfLadingComment = 'Товарителница (Speedy)';


    /**
     * Модифициране на формата за създаване на товарителница към документ
     *
     * @param core_Mvc $mvc   - Документ
     * @param stdClass $rec   - Запис на документ
     * @param core_Form $form - Форма за създаване на товарителница
     * @return void
     */
    public function addFieldToBillOfLadingForm($mvc, $rec, &$form)
    {
        return $this->class->addFieldToBillOfLadingForm($mvc, $rec, $form);
    }


    /**
     * Инпут на формата за изпращане на товарителница
     *
     * @param core_Mvc $mvc         - Документ
     * @param stdClass $documentRec - Запис на документ
     * @param core_Form $form       - Форма за създаване на товарителница
     * @return void
     */
    public function inputBillOfLadingForm($mvc, $documentRec, &$form)
    {
        return $this->class->inputBillOfLadingForm($mvc, $documentRec, $form);
    }


    /**
     * Калкулира цената на товарителницата
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return object $obj           - информация за шаблона и цената
     * @throws core_exception_Expect
     */
    public function calculateShipmentRes($mvc, $documentRec, &$form)
    {
        return $this->class->calculateShipmentRes($mvc, $documentRec, $form);
    }


    /**
     * След подготовка на формата за товарителница
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return core_ET|null $tpl     - хтмл с рендиране на информацията за плащането
     * @throws core_exception_Expect
     */
    public function afterPrepareBillOfLadingForm($mvc, $documentRec, $form, &$tpl)
    {
        return $this->class->afterPrepareBillOfLadingForm($mvc, $documentRec, $form, $tpl);
    }


    /**
     * Връща файл хендлъра на генерираната товарителница след Request-а
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return object $obj           - price, fh, number и status (self::BOL_*) на товарителницата
     * @throws core_exception_Expect
     */
    public function getRequestedShipmentRes($mvc, $documentRec, &$form)
    {
        return $this->class->getRequestedShipmentRes($mvc, $documentRec, $form);
    }


    /**
     * Може ли потребителя да създава товарителница от документа
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param int|null $userId - ид на потребител (null за текущия)
     * @return bool
     */
    public function canRequestBillOfLading($mvc, $id, $userId = null)
    {
        return $this->class->canRequestBillOfLading($mvc, $id, $userId);
    }


    /**
     * Може ли потребителя да създава товарителница от документа
     *
     * @param core_Mvc $mvc
     * @param int|stdClass $id
     * @return core_ET|null
     */
    public function getDefaultEmailBody($mvc, $id)
    {
        return $this->class->getDefaultEmailBody($mvc, $id);
    }


    /**
     * Издадените товарителници към документа, от последната към първата
     *
     * @param int $containerId
     * @return array|null $res - обекти с number, date, file и state; null ако драйверът не поддържа проверката
     */
    public function getBillOfLadings($containerId)
    {
        // Реализациите извън ядрото може да нямат метода
        if(!cls::existsMethod($this->class, 'getBillOfLadings')) return null;

        return $this->class->getBillOfLadings($containerId);
    }


    /**
     * Отказва издадена товарителница към документа при куриера
     *
     * @param int    $containerId - контейнер на документа
     * @param string $number      - номер на товарителницата
     * @param string $reason      - причина
     * @return stdClass|null $res - status (cancelled, rejected, unknown, notFound, noRights) и error; null ако драйверът не поддържа отказ
     */
    public function cancelBillOfLading($containerId, $number, $reason)
    {
        if(!cls::existsMethod($this->class, 'cancelBillOfLading')) return null;

        return $this->class->cancelBillOfLading($containerId, $number, $reason);
    }
}