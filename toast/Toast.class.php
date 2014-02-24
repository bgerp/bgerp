<?php 


/**
 * Плъгин за визуализиране на статус съобщенията, като toast съобщянията при Android
 *
 * @category  vendors
 * @package   toast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class toast_Toast extends core_Plugin
{
    
    
    /**
     * Показва статус съобщенията с toast плъгина
     * 
     * Изпълнява се преди show_ метода
     * Ако javascript' а не е активен, прескача изпълнението на метода.
     * Ако е активен тогава се изпълнява.
     * 
     * @param object $mvc - 
     * @param core_ET $tpl - 
     * @param integer $hitTime - Timestamp на показване на страницата
     * @param boolean $subscribe - Дали да се абонира системата, да извлича други записи по AJAX
     *  
     * @return FALSE - За да не изпълняват други функции (show_)
     */
    function on_BeforeShow(&$mvc, &$tpl, $hitTime, $subscribe=TRUE)
    {
        //Проверяваме дали е включн javascript'a.
        //Ако не е връщаме TRUE, за да може да се изпълнят другите функции
        if (!Mode::is('javascript', 'yes')) return TRUE;
        
        //Създаваме шаблона
        $tpl = new ET();
        
        //Вземаме текущата версия на външния пакет
        $conf = core_Packs::getConfig('toast');
        $version = $conf->TOAST_MESSAGE_VERSION;
        
        // Активираме JQuery
        jquery_Jquery::enable($tpl);
        
        //Добавяме JS и CSS необходими за работа на статусите
        $tpl->push("toast/{$version}/javascript/jquery.toastmessage.js", 'JS');
        $tpl->push("toast/{$version}/resources/css/jquery.toastmessage.css", 'CSS');
        
        // Добавяме функцията, за показване на статус събощенията
        $tpl->appendOnce("function showToast(data)
                            {
                            	setTimeout(function(){
                                    $().toastmessage('showToast', {
                                        text            : data.text,
                                        sticky          : data.isSticky,
                                        stayTime        : data.stayTime,
                                        type            : data.type,
                                        inEffectDuration: 800,
                                        position        : 'bottom-right',
                                        });
                                	}, data.timeOut);
                            }", 'SCRIPTS');
        
        // Ако е зададено да се абонира
        if ($subscribe) {
            
            // Абонираме, за да се вика по JS
            core_Ajax::subscribe($tpl, array('toast_Toast', 'getStatuses'), 'status', 5, FALSE);
            
            // Показва статус събщениет само веднъж
            core_Ajax::subscribe($tpl, array('toast_Toast', 'getStatuses'), 'statusOnce', 1, TRUE);
        }
        
        // Връщаме FALSE за да не се изпълнява метода
        return FALSE;
    }
    
    
    /**
     * Екшън, който се вика по AJAX и показва статус съобщенията
     */
    function act_GetStatuses()
    {
        // Ако заявката е по AJAX
        if (Request::get('ajax_mode')) {
            
            // Времето на отваряне на таба
            $hitTime = Request::get('hitTime', 'int');
            
            // Всички активни статуси за текущия потребител, след съответното време
            $toastJs = static::getStatusesJS($hitTime);
            
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'js';
            $resObj->arg = $toastJs;
            
            return array($resObj);
        }
    }
    
    
    /**
     * Връща javascript за показване на статус съобщения
     * 
     * @param integer $hitTime - Timestamp на показване на страницата
     * 
     * @return string - javascript за показване на статус съобщения
     */
    static function getStatusesJS($hitTime)
    {
        // Всички активни статуси за текущия потребител
        $notifArr = status_Messages::getStatuses($hitTime);
        
        // Броя на намерените статуси
        $countArr = count($notifArr);
        
        // JS, който ще се вика
        $toastJS = '';
        
        // Обикаляме всички открити статуси
        foreach ($notifArr as $val) {
            
            // Типа на статуса
            $toastType = $val['type'];
            
            // Всеки следващ статус със закъсенине + 1 секунди
            $timeOut += (!$timeOut) ? 1 : 1000;
            
            // Ако статусите за показване са повече от 3
            if ($countArr > 3) {
                
                // Статусите да са лепкави (да не се премахват след определено време от екрана)
                $sticky = TRUE;
                $stayTime = 10000;
            } else {
                
                // Лепкавостта на статусите да се определя от вида на статуса
                $sticky = static::isSticky($val['type']);
                
                // Времето за оставане на екрана да се определя от типа на статуса (само за тези, които не са лепкави)
                $stayTime = static::getStayTime($val['type']);
            }
            
            // Стойността да е число
            $isSticky = (int)$sticky;
            
            // Ескейпваме текста
            $text = addslashes($val['text']);
            
            // Добавяме към JS
            $toastJS .= "showToast({timeOut:{$timeOut}, text:'{$text}', isSticky:{$isSticky}, stayTime:{$stayTime}, type:'{$toastType}'});";
        }
        
        return $toastJS;
    }
        
    
    /**
     * В зависимост от типа определяме дали статуса е да лепкав или не (да не се маха от екрана)
     * 
     * @param string $type - Типа на статуса
     * 
     * @return boolean - Дали статус съобщението да е лепкаво или не
     */
    static function isSticky($type)
    {
        // В зависимост от типа определяме дали е да лепкав или не
        switch ($type) {
            case 'success':
                $sticky = FALSE;
            break;
            
            case 'notice':
                $sticky = FALSE;
            break;
            
            case 'warning':
                $sticky = FALSE;
            break;
            
            case 'error':
                $sticky = TRUE;
            break;
            
            default:
                $sticky = TRUE;
            break;
        }
        
        return $sticky;
    }
    
    
    /**
     * Определя дали е статуса да е лепкав в зависимост от подадения тип
     * 
     * @param string $type - Типа на статуса
     * 
     * @return integer - Колко дълго да се показва статуса на екрана
     */
    static function getStayTime($type)
    {
        // В зависимост от типа определяме времето на престой на екрана
        switch ($type) {
            case 'success':
                $time = 6000;
            break;
            
            case 'notice':
                $time = 8000;
            break;
            
            case 'warning':
                $time = 15000;
            break;
            
            case 'error':
                $time = 50000;
            break;
            
            default:
                 $time = 5000;
            break;
        }
        
        return $time;
    }
}
