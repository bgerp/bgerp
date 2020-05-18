<?php


/**
 * Плъгин за разширяване на външната част на е-магазина
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class eshop_plg_External extends core_Plugin
{
    /**
     * След подготовка на страницата за външната част
     */
    public static function on_AfterPrepareExternalPage($mvc, &$res)
    {
        $cartTpl = eshop_Carts::getStatus();
        $res->replace($cartTpl, 'USERCART');
        
        $cu = core_Users::getCurrent('id', false);
        if($cartTpl->getContent() !== ' ' || (isset($cu) && core_Users::isContractor($cu))){
            $res->replace("hasCartBlock", 'MAIN_CONTENT_CLASS');
        }
        
        $res->push(('eshop/js/Scripts.js'), 'JS');
        jquery_Jquery::run($res, 'eshopActions();');
        jqueryui_Ui::enable($res);
    }
}
