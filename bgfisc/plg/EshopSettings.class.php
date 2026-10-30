<?php


/**
 * Настройки на онлайн магазина за регистриране на продажбите при картово плащане по чл. 52о от Наредба Н-18
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
class bgfisc_plg_EshopSettings extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('n18Mode', 'enum(no=Изключен,yes=Включен)', 'caption=Магазин в НАП по Наредба Н-18 (картови плащания без касов бон)->Режим,notNull,value=no,silent,removeAndRefreshForm=n18ShopNumber|n18ShopType|n18ShopUrl');
        $mvc->FLD('n18ShopNumber', 'varchar(10)', 'caption=Магазин в НАП по Наредба Н-18 (картови плащания без касов бон)->Номер от НАП');
        $mvc->FLD('n18ShopType', 'enum(1=Собствен или нает домейн,2=Онлайн платформа)', 'caption=Магазин в НАП по Наредба Н-18 (картови плащания без касов бон)->Вид,notNull,value=1');
        $mvc->FLD('n18ShopUrl', 'varchar(200)', 'caption=Магазин в НАП по Наредба Н-18 (картови плащания без касов бон)->Уеб адрес');
    }


    /**
     * След подготовка на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        $form->input('n18Mode', 'silent');
        if (($rec->n18Mode ?? null) != 'yes') {
            foreach (array('n18ShopNumber', 'n18ShopType', 'n18ShopUrl') as $field) {
                $form->setField($field, 'input=none');
            }

            return;
        }

        if (!empty($rec->objectId)) {
            $domain = cms_Domains::fetchField($rec->objectId, 'domain');
            $form->setField('n18ShopUrl', array('placeholder' => $domain));
        }
    }


    /**
     * Проверка на въведените данни
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if (!$form->isSubmitted() || ($rec->n18Mode ?? null) != 'yes') {

            return;
        }

        if (empty($rec->n18ShopNumber)) {
            $form->setError('n18ShopNumber', 'Номерът на магазина от НАП е задължителен при включен режим|*!');
        }

        // В НАП се подава адресът на магазина в платформата, а не нейният домейн
        if (($rec->n18ShopType ?? null) == '2' && empty($rec->n18ShopUrl)) {
            $form->setError('n18ShopUrl', 'При онлайн платформа трябва да се посочи адресът на магазина в нея|*!');
        }
    }
}
