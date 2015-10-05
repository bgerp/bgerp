<?php

if($_REQUEST['SetupKey']=='demo') {
    $setup = new setup_Controller();
    echo $setup->action();
    die;
}

/**
 * Клас 'setup_Controller'
 *
 *
 * @category  bgerp
 * @package   setup
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class setup_Controller {
    
    /**
     * Масив със всички стойности на въведените променливи
     */
    private $state = array();


    /**
     * Лицензно споразумение
     */
    function form1(&$res)
    {
        $res->title = "Лицензно споразумение за използване";
        $res->next  = "Приемам лиценза »";
        $res->back  = FALSE;
        $res->body  = file_get_contents(__DIR__ . '/../license/gpl3.html');
    }
    

    /**
     * Нова инсталация или възстановяване от backUP
     */
    function form2(&$res)
    {   
        if(defined('EF_SALT') && !$this->state['installationType']) {
            $this->state['installationType'] = 'update';
            //return FALSE;
        }

        $res->title = "Вид на инсталацията";
        $res->question  = "Какъв тип инсталация желаете?";
        $res->body  = $this->createRadio('installationType', array('new' => 'Нова инсталация', 'update' => 'Обновяване на текущата', 'recovery' => 'Възстановяване от бекъп'));
    }

    /**
     * Нова инсталация или възстановяване от backUP
     */
    function form3(&$res)
    {
        if($this->state['installationType'] != 'recovery') return FALSE;

        $res->title = "Източник на резервирани данни";
        $res->question  = "Къде се намира бекъп-а на системата?";
        $res->body  = $this->createRadio('recoverySource', array('path' => 'Локална директория', 'amazon' => 'Сметка в Amazon S3'));
    }


    function form4(&$res)
    {   
        if($this->state['installationType'] != 'update') {

            return FALSE;
        }

        $res->title = "Проверка за обновяване";
        $res->question  = "Желаете ли проверка за нови версии на bgERP?";
        $res->body  = $this->createRadio('checkForUpdates', array('yes' => 'Да, желая', 'recovery' => 'Не, пропусни'));
    }


    function form5(&$res)
    {   
        if($this->state['installationType'] != 'new') {

            return FALSE;
        }

        $res->title = "Предназначение на системата";
        $res->question  = "Какво ще бъде основното предназначение на системата?";
        $res->body  = $this->createRadio('bgerpType', 
            array(  'base'    => 'Организация на екип, имейли и документооборот', 
                    'trade' => 'Търговия ( + предходното)', 
                    'manufacturing' => 'Производство ( + предходното)',
                    'demo' => 'За демонстрация и обучение',
                    'dev' => 'За разработка и тестване',

            ));
    }
    
    
    function form6(&$res)
    {   
        if($this->state['installationType'] != 'new') {

            return FALSE;
        }

        $res->title = "Допълнителни модули";
        $res->question  = "Какви допълнителни модули да бъдат инсталирани?";
        $res->body  = $this->createCheckbox('bgerpAddmodules', 
            array(  'pos'    => 'pos (Продажби в магазин или заведение)', 
                    'web'    => 'cms (Управление на уеб-сайт)',
                    'forum'    => 'cms (Форум към уеб-сайт)', 
                    'blogm'    => 'blogm (Блог към уеб-сайт)', 
                    'eshop'    => 'eshop (Продуктов каталог към уеб-сайт)', 
                    'mon2'   => 'mon2 (Мониторинг на сензори)', 
                    'cams'    => 'cams (Записване на IP видеокамери)',
                    'catering'    => 'catering (Кетъринг за персонала)',

            ));
    }


    function action()
    {
        session_start();
        // Извличаме състоянието
        if($_SESSION['state']) {
            $this->state = $_SESSION['state'];
        }
 
        // Текущата форма
        $current = (int) $_REQUEST['Step'];

        // Посоката, според действието
        $step = $_REQUEST['Cmd_Back'] ? -1 : 1;

        // Изпълняваме текущата 
        $method  = "form{$current}";
        $res = new stdClass; 
        if(method_exists($this, $method)) {
            call_user_func_array(array($this, $method), array(&$res));
            $_SESSION['state'] = $this->state;
        }
 
  
        do {
            $current += $step;
            $method  = "form{$current}";
            $res = new stdClass; 

            if(method_exists($this, $method)) {
                call_user_func_array(array($this, $method), array(&$res));
                $_SESSION['state'] = $this->state;
            } else {
                break;
            }
        } while(!count((array)$res));
 
        // Рендиране
        if(count((array)$res)) {
            $tpl = $this->getFormTpl($current);
            $res = (array) $res;
            if(!isset($res['back'])) {
                $res['back'] = "« Предишен";
            }
            if($res['back']) {
                $res['back'] = "<input type='submit' name='Cmd_Back' style='font-size:16px;margin:3px' value='" . $res['back'] . "'>";
            }
            if(!isset($res['next'])) {
                $res['next'] = "Следващ »";
            }
            if($res['next']) {
                $res['next'] = "<input type='submit' name='Cmd_Next' style='font-size:16px;margin:3px' value='" . $res['next'] . "'>";
            }

            $res['title'] = $res['title'];

            foreach($res as $name => $value) {
                if($value !== FALSE) {
                    $res['[#' . $name . '#]'] = $value;
                }
                unset($res[$name]);
            }  
            $tpl = strtr($tpl, $res);
            $tpl = preg_replace('/\[#([a-zA-Z0-9_:]{1,})#\]/', '', $tpl);
        } else {
            $tpl = 'Финал';
        }

        return $tpl;
    }


    /**
     * Връща шаблона за страницата
     */
    function getFormTpl($current)
    {
        $icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAAY1BMVEX///" .
                "8Aod4AqeMDs+kRvO8Wv/EiHh8jHh8wzvMxz/M50/RYljJmozZ0rzmDvTyDvT2GvkCHv0K9M3rEO4DKRof" .
                "PTo3XWpbdYpzlbaT0gCD1ih72lhz2oBv3pBr4tzP5vDr5wUARJx0eAAAAAXRSTlMAQObYZgAAAGFJREFUG".
                "BkFwTESAVEQBcCeX6OUhJCE+99LJN2IYt/orjs8D/jeYAEANAACHRAIdEAgAACgLtZkO476nFftOgKF2dEDw" . "EAPAAMAAOoKr1PJ+7GMBn4wOzpgYKADAoEFAPAHl3wkRpLmpFkAAAAASUVORK5CYII=";

        $tpl = "
        <!DOCTYPE html>
        <html>
            <head>
                <title>HTML centering</title>

                <style type='text/css'>
                <!--
                html, body, .center { height: 100%; width: 100%; padding: 0; margin: 0; border-spacing: 0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;}
                .center { vertical-align: middle;}
                #bodyCnt {max-height:235px;}
                #bodyTd {height:235px;}
                @media (min-height: 525px) {
                    #bodyCnt {max-height:400px;}
                    #bodyTd {height:400px;}
                }
                @media (min-width: 820px) and (min-height: 360px) {
                    #container {border:solid 3px #bbb;border-radius:10px;}
                }
                -->
                </style>
        </head>

        <body bgcolor='#ffffff'>
        <table class='center' border='0'><tbody><tr><td class='center'>

        <div id='container' style='max-width:800px;background-color:#ddd;margin: 0 auto;padding:0px;'>
            <form method='POST' style='margin:0; padding:0px;'>
                <div style='padding:5px;font-size:1.2em;text-align:center; line-height:32px;'>
                    <span style='background-repeat: no-repeat; background:url(\"{$icon}\") left center no-repeat; padding-left:22px'>
                        bgERP: [#title#]
                    </span>
                </div>

                <table  class='center'><tbody><tr><td class='center' id='bodyTd' style='padding:5px;  background-color:#fff;'>
                    <div style='display:table;  margin: auto;'>
                        <div id='bodyCnt' style='overflow:auto;padding:10px'>
                            <div style='font-size:1.2em;'>[#question#]</div>
                            [#body#]
                        </div>
                    </div>
                </td></tr></tbody></table>

                <input name='Step' value='{$current}' type='hidden'>
                <div style='font-size:1.2em;padding:5px;text-align:center'>
                    [#back#][#next#]
                </div>
            </form>
        </div>

        </td></tr></tbody></table>
        </body>
        </html>";

        return $tpl;
    }




    /**
     * Създава радио група
     */
    function createRadio($name, $opt)
    {
        if(isset($opt[$_REQUEST[$name]])) {
            $this->state[$name] = $_REQUEST[$name];
        }

        $checked = ' checked';

        foreach($opt as $val => $caption) {
            
            $id = 'id' . crc32($val);
            
            if( $this->state[$name]) {
                $checked = ($this->state[$name] == $val) ? ' checked' : '';
            }
            
            $res .= "\n<div style='margin-top:10px;margin-left:10px;'>" .
                    "\n<input type='radio' name='{$name}' value='{$val}' id='{$id}'{$checked}>" .
                    "<label for='{$id}'>{$caption}</label></div>";
            $checked = '';
        }

        return $res;
    }
    
    
    /**
     * Създава група от чек-боксове
     */
    function createCheckbox($name, $opt)
    { 
        if(is_array($_REQUEST[$name])) {
            $this->state[$name] = $_REQUEST[$name];
        }
 
        foreach($opt as $val => $caption) {
            
            $id = 'id' . crc32($val);
            
            if( $this->state[$name]) {
                $checked = (in_array($val, $this->state[$name])) ? ' checked' : '';
            }
            
            $res .= "\n<div style='margin-top:10px;margin-left:10px;'>" .
                    "\n<input type='checkbox' name='{$name}[]' value='{$val}' id='{$id}'{$checked}>" .
                    "<label for='{$id}'>{$caption}</label></div>";
            $checked = '';
        }

        return $res;
    }

}