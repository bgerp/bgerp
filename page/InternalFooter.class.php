<?php


/**
 * Клас 'page_InternalFooter' - Долния завършек на страницата
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  bgerp
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class page_InternalFooter extends core_ET {
    
    
    /**
     * Конструктор на шаблона
     */
    function page_InternalFooter()
    {
        $nick = Users::getCurrent('nick');
        if(EF_USSERS_EMAIL_AS_NICK) {
            list($nick,) = explode('@', $nick);
        }

        $isGet = strtoupper($_SERVER['REQUEST_METHOD']) == 'GET';

        if(Mode::is('screenMode', 'narrow')) {
            if($nick) {
                $this->append(ht::createLink(tr("Изход"), array('core_Users', 'logout'), FALSE, array('title' => "Изход на " . $nick)));
            }
                        
            if($isGet) {
                $this->append("&nbsp;<small>|</small>&nbsp;");
                $this->append(ht::createLink(tr("Широк"), array('core_Browser', 'setWideScreen', 'ret_url' => TRUE), FALSE, array('title' => " Превключване на системата в десктоп режим")));

                // Добавяме превключване между езиците
                $this->append("&nbsp;<small>|</small>&nbsp;");
                $this->addLgChange();
            }

            $this->append("&nbsp;<small>|</small>&nbsp;");
            $this->append(ht::createLink(dt::mysql2verbal(dt::verbal2mysql(), 'H:i'), array('Index', 'default'), NULL, array('title' => tr('Страницата е заредена на') . ' ' . dt::mysql2verbal(dt::verbal2mysql(), 'd-m H:i:s'))));
        } else {
            if($nick) {
                $this->append(ht::createLink("&nbsp;" . tr('изход') . ":" . $nick, array('core_Users', 'logout'), FALSE, array('title' => "Прекъсване на сесията")));
                $this->append('&nbsp;<small>|</small>');
            }
            
            $this->append('&nbsp;');
            $this->append(dt::mysql2verbal(dt::verbal2mysql()));
            
            if($isGet) {
                $this->append("&nbsp;<small>|</small>&nbsp;");
                $this->append(ht::createLink(tr("Тесен"), array('core_Browser', 'setNarrowScreen', 'ret_url' => TRUE), FALSE, array('title' => "Превключване на системата в мобилен режим")));
            
                // Добавяме превключване между езиците
                $this->addLgChange();
            }
            // Добавяме кода, за определяне параметрите на браузъра
            $Browser = cls::get('core_Browser');
            $this->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

            // Добавя бутон за калкулатора
            $this->append('&nbsp;<small>|</small>&nbsp;');
            $this->append(calculator_View::getBtn());
            
            if(isDebug()) {
            	$this->append('&nbsp;<small>|</small>&nbsp;<a href="#wer" onclick="toggleDisplay(\'debug_info\')">Debug</a>');
            }
        }
        
        $conf = core_Packs::getConfig('help');
        
        if($conf->BGERP_SUPPORT_URL && strpos($conf->BGERP_SUPPORT_URL, '//') !== FALSE) {
            $email = email_Inboxes::getUserEmail();
            if(!$email) {
                $email = core_Users::getCurrent('email');
            }
            list($user, $domain) = explode('@', $email);
            $name = core_Users::getCurrent('names');
            $img = sbf('img/supportmale-20.png', '');
            $btn = "<input title='Сигнал за бъг, въпрос или предложение' class='bugReport' type=image src='{$img}' name='Cmd[refresh]' value=1>";
            $form = new ET("<form style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}');\" action='" . $conf->BGERP_SUPPORT_URL . "'>[#1#]</form>", $btn);
            $this->append('&nbsp;<small>|</small>&nbsp;');
            $this->append($form);
        }
        
        if(isDebug() && Mode::is('screenMode', 'wide')) {
        	$this->append(new ET("<div id='debug_info' style='margin:5px; display:none;'>
                                     Време за изпълнение: [#DEBUG::getExecutionTime#]
                                     [#Debug::getLog#]</div>"));
        }

    }


    /**
     * Добавя хипервръзки за превключване между езиците на интерфейса
     */
    function addLgChange()
    {
        $langArr = core_Lg::getLangs();
        $cl      = core_Lg::getCurrent();
        unset($langArr[$cl]);
 
        if(count($langArr)) {
            foreach($langArr as $lg => $title) {
                $url = toUrl(array('core_Lg', 'Set', 'lg' => $lg, 'ret_url' => TRUE));
                $attr = array('href' => $url, 'title' => $title);
                $lg{0} = strtoupper($lg{0});
                $this->append('&nbsp;<small>|</small>&nbsp;');
                $this->append(ht::createElement('a', $attr, $lg));
            }
        }
    }
}
