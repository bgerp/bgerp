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
     * @return core_ET|null $tpl     - хтмл с рендиране на информацията за плащането
     * @throws core_exception_Expect
     */
    public function calculateShipmentTpl($mvc, $documentRec, &$form)
    {
        return $this->class->calculateShipmentTpl($mvc, $documentRec, $form);
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
     * @return string|null $fh       - хендлър на готовата товарителница
     * @throws core_exception_Expect
     */
    public function getRequestedShipmentFh($mvc, $documentRec, &$form)
    {
        return $this->class->getRequestedShipmentFh($mvc, $documentRec, $form);
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
}