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
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class conversejs_Adapter extends core_Mvc
{
    /** Показва чата с избраната версия и достъпните XMPP данни. */
    public function act_Show()
    {
        if (Request::get('locale')) {
            core_App::outputJson(array());
        }
        requireRole('powerUser');
        
        $cu = core_Users::getCurrent();
        $rec = null;
        $aQuery = remote_Authorizations::getQuery();
        while ($aRec = $aQuery->fetch("#userId = {$cu} AND #state = 'active'")) {

            // 'xmppUser' се добавя само от prosody_RemoteDriver - другите драйвери нямат такова поле
            if (empty($aRec->xmppUser)) {
                continue;
            }

            $driver = remote_Authorizations::getDriver($aRec);
            $rec = $driver->getXmppCredentials($aRec);
        }
        $tpl = self::getChatPage($rec, conversejs_Setup::get('BOSH_SERVICE_URL'), core_Users::getCurrent('nick'));
        $tpl->output();
        shutdown();
    }


    /** Подготвя чата без извличане на данни за достъп или отваряне на връзка. */
    public static function getChatPage($rec, $url, $nick)
    {
        $tpl = new page_Html();

        $version = conversejs_Setup::get('VERSION');
        $isModule = $version === '14.0.1';
        $base = 'https://cdn.conversejs.org/' . ($isModule ? '14.0.1/' : '') . 'dist/';
        $tpl->push($base . 'converse.min.css', 'CSS');
        if (!$isModule) {
            $tpl->push($base . 'converse.min.js', 'JS');
        }
        
        $options = array('bosh_service_url' => $url, 'show_controlbox_by_default' => true);
        if ($rec) {
            $options += array(
                'view_mode' => 'fullscreen',
                'keepalive' => true,
                'message_carbons' => true,
                'play_sounds' => true,
                'roster_groups' => true,
                'xhr_user_search' => false,
                'auto_login' => true,
                'allow_dragresize' => true,
                'jid' => $rec->xmppUser ?? '',
                'password' => $rec->xmppPass ?? '',
            );
            $title = ($rec->xmppUser ?? '') . ' / ConverseJS Chat';
        } else {
            $title = $nick . ' / ConverseJS Chat';
        }
        if ($isModule) {
            $options['assets_path'] = $base;
            $options['discover_connection_methods'] = false;
        }
        $script = 'converse.initialize(' . json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');';
        if (!$rec) {
            $script .= "alert('Към вашият профил няма свързана XMPP чат услуга, но ако все пак имате акаунт - може да се логнете с него.');";
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
        
        if ($isModule) {
            $tpl->appendOnce('<script type="module">import converse from "' . $base . 'converse.min.js";' . $script . '</script>', 'HEAD');
        } else {
            $tpl->appendOnce($script, 'SCRIPTS');
        }
        $tpl->appendOnce($style, 'STYLES');
        $tpl->appendOnce($title, 'PAGE_TITLE');
        $tpl->appendOnce($icon, 'HEAD');
        
        return $tpl;
    }
}
