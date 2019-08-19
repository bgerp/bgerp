<?php


/**
 * Клас 'conversejs_Adapter'
 *
 * Адаптер за XMMP чат клиент Coversejs
 *
 *
 * @category  bgerp
 * @package   conversejs
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class conversejs_Adapter extends core_Mvc
{
    public function act_Show()
    {
        if (Request::get('locale')) {
            core_App::outputJson(array());
        }
        requireRole('powerUser');
        
        $tpl = new page_Html();
        $tpl->push('https://cdn.conversejs.org/css/converse.min.css', 'CSS');
        $tpl->push('https://cdn.conversejs.org/dist/converse.min.js', 'JS');
        
        $cu = core_Users::getCurrent();
        $aQuery = remote_Authorizations::getQuery();
        while ($aRec = $aQuery->fetch("#userId = ${cu} AND #state = 'active'")) {
            if ($aRec->xmppUser) {
                $driver = remote_Authorizations::getDriver($aRec);
                $rec = $driver->getXmppCredentials($aRec);
            }
        }
        $url = conversejs_Setup::get('BOSH_SERVICE_URL');
        
        if ($rec) {
            $script = "
                converse.initialize({
                    bosh_service_url: '{$url}',
                    show_controlbox_by_default: true,
                    keepalive: true,
                    message_carbons: true,
                    play_sounds: true,
                    roster_groups: true,
                    xhr_user_search: false,
                    auto_login: true,
                    allow_dragresize: true,
                    jid: '{$rec->xmppUser}',
                    password: '{$rec->xmppPass}'
                });";
            $title = $rec->xmppUser . ' / ConverseJS Chat';
        } else {
            $script = "
                converse.initialize({
                    bosh_service_url: '{$url}',
                    show_controlbox_by_default: true,
                });
                alert('Към профилът ви няма свързана XMPP чат услуга, но ако все-пак имате акаунт - може да се логнете с него.');";
            $title = core_Users::getCurrent('nick') . ' / ConverseJS Chat';
        }
        
        $urlBackground = sbf('conversejs/img/background.jpg', '');
        
        $style = "
            body {
                background-image: url('{$urlBackground}');
                background-repeat: no-repeat;
                background-attachment: fixed;
                background-position: center;
            }
        ";
        $urlIcon = sbf('conversejs/img/16/converse.png', '');
        $icon = "<link rel='icon' type='image/png' href='{$urlIcon}'/>";
        
        $tpl->appendOnce($script, 'SCRIPTS');
        $tpl->appendOnce($style, 'STYLES');
        $tpl->appendOnce($title, 'PAGE_TITLE');
        $tpl->appendOnce($icon, 'HEAD');
        
        $tpl->output();
        
        shutdown();
    }
}
