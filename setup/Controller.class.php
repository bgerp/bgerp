<?php


if ($_REQUEST['SetupKey'] == 'demo') {
    $setup = new setup_Controller();
    echo $setup->action();
    die;
}


// Предварителни данни
// 1. Имаме ли досегашна инсталация или започваме отначало?
//
// Започване на инсталация има право:
//  - администратора
//  - всеки потребител, ако базата е празна



/**
 * Клас 'setup_Controller'
 *
 *
 * @category  bgerp
 * @package   setup
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class setup_Controller
{
    /**
     * Масив със всички стойности на въведените променливи
     */
    private $state = array();
    
    
    /**
     * Лицензно споразумение
     */
    public function form1(&$res)
    {
        $res->title = 'Лицензно споразумение за използване';
        $res->next = 'Приемам лиценза »';
        $res->back = false;
        $res->body = file_get_contents(__DIR__ . '/../license/gpl3.html');
    }
    
    
    /**
     * Нова инсталация или възстановяване от backUP
     */
    public function form2(&$res)
    {
        if (defined('EF_SALT') && !$this->state['installationType']) {
            $this->state['installationType'] = 'update';
        }
        
        $db = cls::get('core_Db');
        if ($db->getDBInfo('ROWS') > 0) {
            
            return false;
        }
        
        $res->title = 'Вид на инсталацията';
        $res->question = 'Какъв тип инсталация желаете?';
        $res->body = $this->createRadio('installationType', array('new' => 'Нова инсталация',   'recovery' => 'Възстановяване от бекъп'));
    }
    
    
    /**
     * Нова инсталация или възстановяване от backUP
     */
    public function form3(&$res)
    {
        if ($this->state['installationType'] != 'recovery') {
            
            return false;
        }
        
        $res->title = 'Източник на резервирани данни';
        $res->question = 'Къде се намира бекъп-а на системата?';
        $res->body = $this->createRadio('recoverySource', array('path' => 'Локална директория', 'amazon' => 'Сметка в Amazon S3'));
    }
    
    
    /**
     * Да се провери ли за нови версии на bgERP?
     */
    public function form4(&$res)
    {
        if ($this->state['installationType'] == 'recovery') {
            
            return false;
        }
        
        $res->title = 'Проверка за обновления';
        $res->question = 'Да проверя ли за нови версии на bgERP?';
        $res->body = $this->createRadio('checkForUpdates', array('yes' => 'Да, провери сега', 'recovery' => 'Не, пропусни'));
    }
    
    
    /**
     * Кои приложения да се обновят?
     */
    public function form5(&$res)
    {
        // Ще покажем възможностите за обновяване, само ако:
        // 1. Не сме отказали проверките за обновяване
        // 2. Не правим нова инсталация
        if ($this->state['checkForUpdates'] != 'yes' || $this->state['installationType'] == 'recovery') {
            
            return false;
        }
        if (defined('EF_PRIVATE_PATH')) {
            $repo2Path = EF_PRIVATE_PATH;
            $repo2Branch = PRIVATE_GIT_BRANCH;
        }
        $repo1Path = EF_APP_PATH;
        $repo1Branch = BGERP_GIT_BRANCH;
        
        $log = array();
        
        //$newVer = core_Git::gitHasNewVersion($repo1Path, $log, $repo1Branch);
        $repos = core_App::getRepos();
        
        foreach ($repos as $r) {
            $r = basename($r);
            $opt[$r] = $r;
        }
        
        
        $res->title = 'Обновяване на избраното';
        $res->question = 'Желаете ли обновяване на:';
        $res->body = $this->createCheckbox(
            'updates',
            $opt,
            $opt
        );
    }
    
    
    /**
     * Какво ще бъде предназначението на системата
     */
    public function form6(&$res)
    {
        if ($this->state['installationType'] != 'new') {
            
            return false;
        }
        
        $res->title = 'Предназначение на системата';
        $res->question = 'Какво ще бъде основното предназначение на системата?';
        $res->body = $this->createRadio(
            'bgerpType',
            array('base' => 'Организация на екип, имейли и документооборот',
                'trade' => 'Управление на продажби ( + предходното)',
                'manufacturing' => 'Производствен мениджмънт ( + предходното)',
                'demo' => 'За демонстрация и обучение',
                'dev' => 'За разработка и тестване',
            
            )
        );
    }
    
    
    /**
     * Какви допълнителни модули да бъдат инсталирани, при първоначалния сетъп?
     */
    public function form7(&$res)
    {
        if ($this->state['installationType'] != 'new') {
            
            return false;
        }
        
        $res->title = 'Допълнителни модули';
        $res->question = 'Кои допълнителни модули да бъдат инсталирани?';
        if ($this->state['bgerpType'] == 'base') {
            $res->body = $this->createCheckbox(
                'bgerpAddmodules',
                array('web' => 'cms (Управление на уеб-сайт)',
                    'mon2' => 'mon2 (Мониторинг на IoT контролери)',
                    'cams' => 'cams (Записване на IP видеокамери)',
                    'catering' => 'catering (Кетъринг за персонала)',
                
                )
            );
        } else {
            $res->body = $this->createCheckbox(
                'bgerpAddmodules',
                array('pos' => 'pos (Продажби в магазин или заведение)',
                    'web' => 'cms (Управление на уеб-сайт)',
                    'mon2' => 'mon2 (Мониторинг на сензори)',
                    'cams' => 'cams (Записване на IP видеокамери)',
                    'catering' => 'catering (Кетъринг за персонала)',
                
                )
            );
        }
    }
    
    
    public function form8(&$res)
    {
        if ($this->state['installationType'] != 'new' || !in_array('web', $this->state['bgerpAddmodules'])) {
            
            return false;
        }
        
        $res->title = 'Модули за уеб-сайт';
        $res->question = 'Какви разширения за уеб сайт да бъдат инсталирани?';
        if ($this->state['bgerpType'] == 'base') {
            $res->body = $this->createCheckbox(
                'webAddmodules',
                array(
                    'forum' => 'forum (Форум към уеб-сайт)',
                    'blogm' => 'blogm (Блог към уеб-сайт)',
                )
            );
        } else {
            $res->body = $this->createCheckbox(
                'webAddmodules',
                array(
                    'forum' => 'forum (Форум към уеб-сайт)',
                    'blogm' => 'blogm (Блог към уеб-сайт)',
                    'eshop' => 'eshop (Продуктов каталог към уеб-сайт)',
                )
            );
        }
    }
    
    
    public function form9(&$res)
    {
        if ($this->state['installationType'] != 'recovery') {
            
            return false;
        }
        
        $res->title = 'Възстановяване от архив';
        $res->question = 'Въведете път до архива:';
        $res->body = $this->createInput('path', '', 'style=width:400px;font-size:1.1em');
    }
    
    
    public function form10(&$res)
    {
        if ($this->state['installationType'] != 'recovery') {
            
            return false;
        }
        
        $configPath = rtrim(trim(str_replace('\\', '/', $this->state['path'])), '/') . '/config.cfg.php';
        if (!is_dir($this->state['path'])) {
            $res->title = "<span style='color:red'>Грешка!</span>";
            $res->question = 'Невалиден път до архив:';
            $res->body = '<span>' . $this->state['path'] . '</span>';
            $res->next = false;
        } elseif (!file_exists($configPath)) {
            $res->title = "<span style='color:red'>Грешка!</span>";
            $res->question = 'На посочения път липсва бекъп:';
            $res->body = '<span>' . $this->state['path'] . '</span>';
            $res->next = false;
        } elseif (!is_readable($configPath)) {
            $res->title = "<span style='color:red'>Грешка!</span>";
            $res->question = 'Бекъпът не е достъпен за четене:';
            $res->body = '<span>' . $this->state['path'] . '</span>';
            $res->next = false;
        } else {
            $res->title = 'Открит е бекъпа';
            $res->question = 'Бекъпът е открит на посочения адрес:';
            $res->body = '<span>' . $this->state['path'] . '</span>';
        }
    }
    
    
    /**
     * От кой бранч да се теглят обновленията
     */
    public function form11(&$res)
    {
        if ($this->state['installationType'] != 'new') {
            
            return false;
        }
        
        $res->title = 'Междинни версии на софтуера';
        $res->question = 'Искате ли да получавате междинни версии?';
        $res->body = $this->createRadio(
            'branch',
                            array('master' => 'Не искам да рискувам',
                                'DC2' => 'Желая да съм бета-тестер',
                                'DC1' => 'Желая да съм алфа-тестер')
        );
    }
    
    
    /**
     * Дали да се рапортуват отдалечено грешките?
     */
    public function form12(&$res)
    {
        if ($this->state['installationType'] != 'new') {
            
            return false;
        }
        
        $res->title = 'Рапортуване на грешките';
        $res->question = 'Когато възникне грешка:';
        $res->body = $this->createRadio(
            'reportErrors',
            array('yes' => 'Докладвай на разработчиците',
                'recovery' => 'Не изпращай нищо')
        );
    }
    
    
    /**
     * Двигател на диалога
     */
    public function action()
    {
        session_start();
        
        // Извличаме състоянието
        if ($_SESSION['state']) {
            $this->state = $_SESSION['state'];
        }
        
        if (!isset($_REQUEST['Step'])) {
            $_REQUEST['Step'] = 1;
        }
        
        // Текущата форма
        $current = (int) $_REQUEST['Step'];
        
        // Посоката, според действието
        $step = $_REQUEST['Cmd_Back'] ? -1 : 1;
        
        // Изпълняваме текущата
        $method = "form{$current}";
        $res = new stdClass;
        if (method_exists($this, $method)) {
            call_user_func_array(array($this, $method), array(&$res));
            $_SESSION['state'] = $this->state;
        }
        
        do {
            $current += $step;
            $method = "form{$current}";
            $res = new stdClass;
            
            if (method_exists($this, $method)) {
                call_user_func_array(array($this, $method), array(&$res));
                $_SESSION['state'] = $this->state;
            } else {
                break;
            }
        } while (!count((array) $res));
        
        
        // Рендиране
        if (count((array) $res)) {
            $tpl = $this->getFormTpl($current);
            $res = (array) $res;
            if (!isset($res['back'])) {
                $res['back'] = '« Предишен';
            }
            if ($res['back']) {
                if (!$res['next']) {
                    $autofocus = "autofocus='on' ";
                } else {
                    $autofocus = '';
                }
                $res['back'] = "<input {$autofocus}type='submit' name='Cmd_Back' style='font-size:14px;margin:3px;color:black' value='" . $res['back'] . "'>";
            }
            if (!isset($res['next'])) {
                $res['next'] = 'Следващ »';
            }
            if ($res['next']) {
                $res['next'] = "<input autofocus='on' type='submit' name='Cmd_Next' style='font-size:14px;margin:3px;color:black' value='" . $res['next'] . "'>";
            }
            
            $res['title'] = $res['title'];
            
            foreach ($res as $name => $value) {
                if ($value !== false) {
                    $res['[#' . $name . '#]'] = $value;
                }
                unset($res[$name]);
            }
            $tpl = strtr($tpl, $res);
            $tpl = preg_replace('/\[#([a-zA-Z0-9_:]{1,})#\]/', '', $tpl);
        } else {
            $tpl = 'Финал';
        }
        
        Mode::set('wrapper', 'page_Empty');
        
        return $tpl;
    }
    
    
    /**
     * Връща шаблона за страницата
     */
    public function getFormTpl($current)
    {
        $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAAY1BMVEX///' .
                '8Aod4AqeMDs+kRvO8Wv/EiHh8jHh8wzvMxz/M50/RYljJmozZ0rzmDvTyDvT2GvkCHv0K9M3rEO4DKRof' .
                'PTo3XWpbdYpzlbaT0gCD1ih72lhz2oBv3pBr4tzP5vDr5wUARJx0eAAAAAXRSTlMAQObYZgAAAGFJREFUG'.
                'BkFwTESAVEQBcCeX6OUhJCE+99LJN2IYt/orjs8D/jeYAEANAACHRAIdEAgAACgLtZkO476nFftOgKF2dEDw' .
                'EAPAAMAAOoKr1PJ+7GMBn4wOzpgYKADAoEFAPAHl3wkRpLmpFkAAAAASUVORK5CYII=';
        
        $background = sbf('setup/img/sunrise.jpg', '');
        
        $tpl = '<!DOCTYPE html>
        <html>
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2">
                <title>Setup - bgERP</title>
                <style>' . file_get_contents(__DIR__ . '/setup.css') . "</style>
                <link href='{$icon}' rel='icon' type='image/x-icon'>
        </head>

        <body style='background-color:#000 !important; background-image: url(\"{$background}\");'>
        <table class='center' border='0'><tbody><tr><td class='center'>

            <form method='POST'>
                <table width=100% id='container'>
                <tr class='header'  id='titleRow'>
                    <td>
                    <span style='background:url(\"{$icon}\") left center no-repeat; padding-left:22px'>
                        bgERP Настройчик » [#title#]
                    </span>
                    </td>
                </tr>

                <tr><td class='center' id='bodyTd'>
                    <div class='centeredContent'>
                        <div id='bodyCnt'>
                            <div class='question'>[#question#]</div>
                            [#body#]
                        </div>
                    </div>
                </td></tr>
                
                <tr> <td id='buttonsRow'>
                <input name='Step' value='{$current}' type='hidden'>
                <div  class='formFooter centeredContent'>
                    <center id='buttons'>[#next#][#back#]</center>
                </div>
                </td></tr>
            </table>
            </form> 

        </td></tr></tbody></table>

        </body>
        </html>";
        
        return $tpl;
    }
    
    
    /**
     * Създава радио група
     */
    public function createRadio($name, $opt)
    {
        if (isset($opt[$_REQUEST[$name]])) {
            $this->state[$name] = $_REQUEST[$name];
        }
        
        $checked = ' checked';
        
        foreach ($opt as $val => $caption) {
            $id = 'id' . crc32($val);
            
            if ($this->state[$name]) {
                $checked = ($this->state[$name] == $val) ? ' checked' : '';
            }
            
            $res .= "\n<div class='answer'>" .
                    "\n<input type='radio' name='{$name}' value='{$val}' id='{$id}'{$checked}>" .
                    "<label for='{$id}'>{$caption}</label></div>";
            $checked = '';
        }
        
        return $res;
    }
    
    
    /**
     * Създава група от чек-боксове
     */
    public function createCheckbox($name, $opt, $defaults = array())
    {
        if (is_array($_REQUEST[$name])) {
            $this->state[$name] = $_REQUEST[$name];
        }
        
        foreach ($opt as $val => $caption) {
            $id = 'id' . crc32($val);
            
            if ($this->state[$name]) {
                $checked = (in_array($val, $this->state[$name])) ? ' checked' : '';
            } else {
                $checked = (in_array($val, $defaults)) ? ' checked' : '';
            }
            
            
            $res .= "\n<div class='answer'>" .
                    "\n<input type='checkbox' name='{$name}[]' value='{$val}' id='{$id}'{$checked}>" .
                    "<label for='{$id}'>{$caption}</label></div>";
            $checked = '';
        }
        
        $res .= "\n<input type='hidden' name='{$name}[]' value='is_used'>";
        
        return $res;
    }
    
    
    /**
     * Създава input елемент
     */
    public function createInput($name, $value, $attr = array())
    {
        if (isset($_REQUEST[$name])) {
            $this->state[$name] = $_REQUEST[$name];
        }
        
        if (isset($this->state[$name])) {
            $value = $this->state[$name];
        }
        
        $res = "\n<div class='answer'>" .
                "\n<input type='text' name='{$name}' value='{$value}' {$attr}>";
        
        return $res;
    }
    
    
    /**
     * Ескейпва съдържание на атрибут
     */
    public static function escapeAttr($aValue)
    {
        $aValue = htmlspecialchars($aValue, ENT_QUOTES, null);
        $aValue = str_replace(array("\n"), array('&#10;'), $aValue);
        
        return $aValue;
    }
}
