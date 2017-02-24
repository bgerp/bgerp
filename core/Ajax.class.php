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
     * Колко дни да остане в лога
     */
    static $logKeepDays = 3;
    
    
    /**
     * Екшън, който се вика по AJAX и извиква всички подадени URL-та
     */
    function act_Get()
    {
        // Ако не сме в DEBUG режим
        if (!isDebug()) {
            
            // Очаквае заявката да е по AJAX - да има такъв хедър
            if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
                self::logNotice("Стартиране на core_Ajax::get() извън AJAX" . core_Type::mixedToString(Request::$vars) . '. IP: ' . core_Users::getRealIpAddr());
                expect(FALSE);
            }
        }
        
        // Масив с URL-та в JSON формат
        $subscribed = Request::get('subscribed');
        
        // Времето на извикване на страницата
        $hitTime = Request::get('hitTime', 'int');
        
        // Уникално ID на хита
        $hitId = Request::get('hitId');
        
        // Времето на извикване на страницата
        $idleTime = Request::get('idleTime', 'int');
        
        // Декодираме масива
        $subscribedArr = json_decode($subscribed);
        
        // URL от който се вика AJAX
        $parentUrl = Request::get('parentUrl');
        
        // Дали се вика по ajax
//        $ajaxMode = Request::get('ajax_mode');
        $ajaxMode = 1;
        
        // Ако няма нищо в масив, прекъсваме функцията
        if (!$subscribed) shutdown();
        
        // Резултатния масив
        $jResArr = array();
        
        // Стойности, които да се игнорират
        Request::ignoreParams(array('subscribed' => TRUE,
                					'parentUrl' => TRUE,
                					'idleTime' => TRUE,
                					'hitTime' => TRUE,
                					'ajax_mode' => TRUE,
                					'refreshUrl' => TRUE,
                					'divId' => TRUE,
                					'hitId' => TRUE));
        
        // Обхождаме всички подадедени локални URL-та
        foreach ((array)$subscribedArr as $name=>$url) {
            
            // Декодираме URL-то
            $url = urldecode($url);
            
            // Вземаме масива от локолното URL
            $urlArr = core_App::parseLocalUrl($url);
            
            // Добавяме параметър, който указва, че е стартиран по AJAX
            $urlArr['ajax_mode'] = $ajaxMode;
            
            // Ако е зададен hitTime
            if ($hitTime) {
                
                // Да се добави в URL-то
                $urlArr['hitTime'] = $hitTime;
            }
            
            // Добавяме уникалното ID на хита
            if ($hitId) {
                $urlArr['hitId'] = $hitId;
            }
            
            // Да се добави в URL-то
            $urlArr['idleTime'] = $idleTime;
            
            // Добавяме URL-то в заявката
            $urlArr['parentUrl'] = $parentUrl;
            
            try {
                // Извикваме URL-то
                $resArr = Request::forward($urlArr);
                
            } catch (core_exception_Expect $e) {
                
                reportException($e);
                
                $errMsg = "Грешка при вземане на данни от {$url} - {$e->getMessage()}";
                
                // Записваме в лога
                self::logWarning($errMsg, NULL, self::$logKeepDays);
                
                // Ако сме в дебъг режим и сме логнат
                if (isDebug() && haveRole('user')) {
                    
                    $errMsg = "|Грешка при вземане на данни от|* {$url} - {$e->getMessage()}";
                    
                    // Показваме статус съобщение
                    core_Statuses::newStatus($errMsg, 'warning');
                }
                
                continue;
            }
            
            // Ако няма масив или масива не е масива
            if (!is_array($resArr)) {
                
                if (is_object($resArr) && ($resArr instanceof core_Redirect)) {
                    // Пушваме ajax_mode, за да може функцията да върне резултат по AJAX, вместо директно да редиректне
                    Request::push(array('ajax_mode' => $ajaxMode));
                    $resArr = $resArr->getContent();
                }
            }
            
            if (!is_array($resArr)) {
                // Записваме в лога резултата
                $resStr = core_Type::mixedToString($resArr);
                
                $errMsg = "Некоректен резултат от {$url} - {$resStr}";
                
                self::logErr($errMsg, NULL, self::$logKeepDays);
                
                // Ако сме в дебъг режим и сме логнат
                if (isDebug() && haveRole('user')) {
                    
                    $errMsg = "|Некоректен резултат от|* {$url} - {$resStr}";
                    
                    // Показваме статус съобщение
                    core_Statuses::newStatus($errMsg, 'warning');
                }
                 
                continue;
            }
            
            // Обединяваме масивите
            $jResArr = array_merge($jResArr, $resArr);
        }
        
        // Нулираме масива за игнориране
        Request::resetIgnoreParams();
        
        core_App::getJson($jResArr);
    }
    
    
    /**
     * Функция, която абонира дадено URL за извлича данни по AJAX
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     * @param array $urlArr - Масив, от който ще се генерира локално URL
     * @param string $name - Уникално име
     * @param int $interval - Интервал на извикване в секунди
     */
    static function subscribe(&$tpl, $urlArr, $name, $interval=5000)
    {
        // Масив с всички използвани имена
        static $nameArr=array();
        
        // Ако няам име или не  е уникално
        if (!$name || $nameArr[$name]) {
            
            // Не би трябвало да се стига до тук
            
            $msg = "Повтарящо се име за абониране - {$name}";
            
            // Добавяме грешката
            self::logWarning($msg, NULL, self::$logKeepDays);
            
            // Ако сме в дебъг режим и сме логнат
            if (isDebug() && haveRole('user')) {
                
                $msg = "|Повтарящо се име за абониране|* - {$name}";
                
                // Показваме статус съобщение
                core_Statuses::newStatus($msg, 'warning');
            }
            
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
        static::run($tpl);
        
        // Локално URL
        $localUrl = toUrl($urlArr, 'local');
        
        // Ескейпваме
        $localUrl = urlencode($localUrl);
                
        // Добавяме стринга, който субскрайбва съответното URL
        jquery_Jquery::run($tpl, "getEfae().subscribe('{$name}', '{$localUrl}', {$interval});", TRUE);
        
    }
    
    
    /**
     * Добавя фунцкция, която стартира efae
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     */
    protected static function run(&$tpl)
    {
        // URL, което сочи към екшъна за извличане на данни по AJAX
        $url = array('core_Ajax', 'Get');
        
        // Ако е в принт режим
        if ($printing = Request::get('Printing')) {
            // Добавяме в пареметрите
            $url['Printing'] = $printing;
        } else if (Mode::get('printing')) {
            $url['Printing'] = 1;
        }
        
        $url = toUrl($url);
        
        // Добавяме URL-то за сваляне на ajax
        jquery_Jquery::run($tpl, "getEfae().setUrl('{$url}');", TRUE);
        
        // URL от който ще се вика айакса
        $parentUrl = toUrl(getCurrentUrl(), 'local');
        
        // Задаваме УРЛ-то
        jquery_Jquery::run($tpl, "getEfae().setParentUrl('{$parentUrl}');", TRUE);
        
        // Ако има hitId сетваме стойността на променливата
        if ($hitId = Request::get('hit_id')) {
        	jquery_Jquery::run($tpl, "getEfae().setHitId('{$hitId}');", TRUE);
        }
        
        // Стартираме извикването на `run` фунцкцията на efae
        jquery_Jquery::run($tpl, "\n getEfae().run();", TRUE);
        jquery_Jquery::run($tpl, "\n getEO().runIdleTimer();", TRUE);
    }
}
