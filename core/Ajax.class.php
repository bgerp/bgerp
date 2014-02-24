<?php


/**
 * Клас за работа с EFAE - Experta Framework Ajax Engine
 * 
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Ajax extends core_Mvc
{
    
    
    /**
     * Екшън, който се вика по AJAX и извиква всички подадени URL-та
     */
    function act_Get()
    {
        // Масив с URL-та в JSON формат
        $subscribed = Request::get('subscribed');
        
        // Времето на извикване на страницата
        $hitTime = Request::get('hitTime', 'int');
        
        // Декодираме масива
        $subscribedArr = json_decode($subscribed);
        
        // Ако няма нищо в масив, прекъсваме функцията
        if (!$subscribed) shutdown();
        
        // Резултатния масив
        $jResArr = array();
        
        // Обхождаме всички подадедени локални URL-та
        foreach ((array)$subscribedArr as $name=>$url) {
            
            // Декодираме URL-то
            $url = urldecode($url);
            
            // Вземаме масива от локолното URL
            $urlArr = core_App::parseLocalUrl($url);
            
            // Добавяме параметър, който указва, че е стартиран по AJAX
            $urlArr['ajax_mode'] = 1;
            
            // Ако е зададен hitTime
            if ($hitTime) {
                
                // Да се добави в URL-то
                $urlArr['hitTime'] = $hitTime;
            }
            
            try {
                // Извикваме URL-то
                $resArr = Request::forward($urlArr);
            } catch (Exception $e) {
                
                // Записваме в лога
                core_Logs::add($this, NULL, "Грешка при вземане на данни за {$url}");
                
                continue;
            }
            
            // Ако няма масив или масива не е масива
            if (!$resArr || !is_array($resArr)) {
                
                // Записваме в лога резултата
                $resStr = core_Type::mixedToString($resArr);
                core_Logs::add($this, NULL, "Некоректен резултат за {$url} - $resStr");
                
                continue;
            }
            
            // Обединяваме масивите
            $jResArr = array_merge($jResArr, $resArr);
        }
        
        // За да не се кешира
        header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Указваме, че ще се връща JSON
        header('Content-Type: application/json');
        
        // Връщаме резултата в JSON формат
        echo json_encode($jResArr);
        
        // Прекратяваме процеса
        shutdown();
    }
    
    
    /**
     * Функция, която абонира дадено URL за извлича данни по AJAX
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     * @param array $urlArr - Масив, от който ще се генерира локално URL
     * @param string $name - Уникално име
     * @param integer $interval - Интервал на извикване в секунди
     */
    static function subscribe(&$tpl, $urlArr, $name, $interval=5)
    {
        // Масив с всички използвани имена
        static $nameArr=array();
        
        // Ако няам име или не  е уникално
        if (!$name || $nameArr[$name]) {
            
            // Не би трябвало да се стига до тук
            
            // Добавяме грешката
            core_Logs::add('core_Ajax', NULL, "Повтарящо се име - '{$name}'");
            
//            // Докато генерираме уникално име
//            while ($nameArr[$name]) {
//                
//                // Добавяме след името
//                $name .= '_efae';
//            }
        }
        
        // Добавяме в масива
        $nameArr[$name] = TRUE;
        
        // Добавяме необходимите неща за стартиране на efae
        static::enable($tpl);
        
        // Интервала в милисекунди
        $interval *= 1000;
        
        // Локално URL
        $localUrl = toUrl($urlArr, 'local');
        
        // Ескейпваме
        $localUrl = urlencode($localUrl);
        
        // Добавяме стринга, който субскрайбва съответното URL
        $subscribeStr = "efaeInst.subscribe('{$name}', '{$localUrl}', {$interval});";
        
        // Добавяме само веднъж
        $tpl->appendOnce($subscribeStr, 'SCRIPTS');
    }
    
    
    /**
     * Добавя необходимите неща, за да може да се стартира efae
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     */
    protected static function enable(&$tpl)
    {
        // Скрипт, за вземане на инстанция на efae
        $tpl->appendOnce("\n efaeInst = new efae();\n", 'SCRIPTS');
        
        // Този пакет е във vendors
        if (method_exists('jquery_Jquery', 'enable')) {
            
            // Стартираме JQuery
            jquery_Jquery::enable($tpl);
        } else {
            
            // Добавяме грешката
            core_Logs::add('core_Ajax', NULL, 'Липсва метода `enable` в `jquery_Jquery`');
        }
        
        // Стартираме извикването на `run` фунцкцията на efae
        static::run($tpl);
    }
    
    
    /**
     * Добавя фунцкция, която стартира efae
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     */
    protected static function run(&$tpl)
    {
        // URL, което сочи към екшъна за извличане на данни по AJAX
        $url = toUrl(array('core_Ajax', 'Get'));
        
        // Добавяме променливата
        $tpl->appendOnce("efaeInst.setUrl('{$url}');", 'SCRIPTS');
        
        // Този пакет е във vendors - ако липсва
        if (method_exists('jquery_Jquery', 'run')) {
            
            // Стартираме извикването на `run` фунцкцията на efae
            jquery_Jquery::run($tpl, 'efaeInst.run();', TRUE);
        } else {
            
            // Стартираме извикването на run
            $tpl->appendOnce("\n runOnLoad(function(){efaeInst.run();});", 'JQRUN');
            
            // Добавяме грешката
            core_Logs::add('core_Ajax', NULL, 'Липсва метода `run` в `jquery_Jquery`');
        }
    }
}
