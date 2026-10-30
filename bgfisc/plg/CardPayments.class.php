<?php


/**
 * Връзка към документа по чл. 52о на страницата за успешно плащане с карта
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
class bgfisc_plg_CardPayments extends core_Plugin
{
    /**
     * Купувачът получава документа в момента на плащането (чл. 52о, ал. 5)
     *
     * @param core_Mvc $mvc
     * @param core_ET  $tpl - страницата с резултата
     * @param stdClass $rec - опитът за плащане
     */
    public static function on_AfterRenderResult($mvc, &$tpl, $rec)
    {
        if (empty($rec->initiatorClassId) || $rec->initiatorClassId != eshop_Carts::getClassId() || empty($rec->initiatorId)) {

            return;
        }

        $docRec = bgfisc_SaleRegDocs::fetch(array("#cartId = [#1#]", $rec->initiatorId));
        if (!is_object($docRec)) {

            return;
        }

        $caption = tr('Документ за регистриране на продажбата') . ' №' . bgfisc_SaleRegDocs::formatNumber($docRec->number);
        $tpl->append(ht::createLink($caption, bgfisc_SaleRegDocs::getPublicUrl($docRec), false, array('ef_icon' => 'img/16/page_white_text.png', 'target' => '_blank')), 'LINKS');
    }


    /**
     * Колона с документа по чл. 52о в дневника на картовите плащания
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        arr::placeInAssocArray($data->listFields, array('n18Doc' => 'Чл. 52о'), null, 'state');
    }


    /**
     * Бутон към документа по чл. 52о за платеното
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!isset($fields['-list']) || ($rec->state ?? null) != 'paid' || ($rec->initiatorClassId ?? null) != eshop_Carts::getClassId() || empty($rec->initiatorId)) {

            return;
        }

        $docRec = bgfisc_SaleRegDocs::fetch(array("#cartId = [#1#]", $rec->initiatorId));
        if (is_object($docRec) && bgfisc_SaleRegDocs::haveRightFor('single', $docRec)) {
            $row->n18Doc = ht::createLink('', array('bgfisc_SaleRegDocs', 'single', $docRec->id, 'ret_url' => true), false, array('ef_icon' => 'img/16/page_white_text.png', 'title' => 'Документ за регистриране на продажбата'));
        }
    }
}
