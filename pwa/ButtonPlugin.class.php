<?php


/**
 *
 *
 * @package   pwa
 *
 * @author    Yusein Yusein <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_ButtonPlugin extends core_Plugin
{


    /**
     *
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        // Да не показва бутон в широк режим, ако е изключено по подразбиране
        $defSettings = pwa_Setup::get('DEFAULT_ACTIVE');
        if ($defSettings != 'yes') {
            if (Mode::is('screenMode', 'narrow') === false) {

                $bridVar = log_Browsers::getVars(array('pwaOnOff'));
                if ($bridVar['pwaOnOff'] != 'yes') {

                    return;
                }
            }
        }

        $on = 'ON';
        $canUse = pwa_Manifest::canUse();
        $title = 'Включване на мобилното приложение';
        if ($canUse == 'yes') {
            $on = 'OFF';
            $title = 'Изключване на мобилното приложение';
        }

        // Добавяме бутон за клонирането му
        $data->toolbar->addBtn("PWA APP ({$on})", array($mvc, 'pwaOnOff', 'ret_url' => true), 'ef_icon=img/16/pwa.png',
            array('title' => $title, 'row' => 2, 'order' => 12));
    }


    /**
     *
     * @param $mvc
     * @param $res
     * @param $action
     * @return false|void
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'pwaonoff') {

            requireRole('user');

            $retUrl = getRetUrl();

            $canUse = pwa_Manifest::canUse();
            if ($canUse != 'yes') {
                $msg = 'Мобилното приложение е включено';

                log_Browsers::setVars(array('pwaOnOff' => 'yes'));
            } else {
                log_Browsers::setVars(array('pwaOnOff' => 'no'));

                $msg = 'Мобилното приложение е изключено';
            }

            $res = new Redirect($retUrl, $msg);

            return false;
        }
    }
}
