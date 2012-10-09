<?php 


/**
 * Клас 'statuses_Toast' - Плъгин за визуализиране на статус съобщенията, като toast съобщянията при Android
 *
 * @category  vendors
 * @package   statuses
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class statuses_Toast extends core_Plugin
{
    
    
    /**
     * Изпълнява се преди show_ метода
     * 
     * Ако javascript' а не е активен, прескача изпълнението на метода.
     * Ако е активен тогава се изпълнява.
     * Показва статус съобщенията с toast плъгина
     * 
     *  @param $mvc
     *  @param $tpl
     *  
     *  @return FALSE - За да не изпълняват други функции (show_)
     */
    function on_BeforeShow(&$mvc, &$tpl)
    {
        //Проверяваме дали е включн javascript'a.
        //Ако не е връщаме TRUE, за да може да се изпълнят другите фунцкии
        if (!Mode::is('javascript', 'yes')) return TRUE;
        
        //Всички активни статуси за текущия потребител
        $notifArr = core_Statuses::getStatuses();
        
        //Създаваме шаблона
        $tpl = new ET();
        
        //Създаваме инстанция на jquery плъгина
        $JQuery = cls::get('jquery_Jquery');
        $JQuery->enable($tpl);
        
        //Вземаме текущата версия на външния пакет
        $conf = core_Packs::getConfig('statuses');
        $version = $conf->STATUSES_TOAST_MESSAGE_VERSION;
        
        //Добавяме JS и CSS необходими за работа на статусите
        $tpl->push("statuses/{$version}/javascript/jquery.toastmessage.js", 'JS');
        $tpl->push("statuses/{$version}/resources/css/jquery.toastmessage.css", 'CSS');
        
        //Броя на намерените статуси
        $countArr = count($notifArr);

        //Обикаляме всички открити статуси
        foreach ($notifArr as $val) {
            //Типа на статуса
            $toastType = $val['statusType'];
            
            //Първия статус да се покаже 1 секунда след зареждане на страницата
            //Всеки следващ статус със закъсенине + 1,5 секунди
            $timeOut += (!$timeOut) ? 1000 : 1500;
            
            //Ако статусите за показване са повече от 3
            if ($countArr > 3) {
                
                //Статусите да са лепкави (да не се премахват след определено време от екрана)
                $sticky = TRUE;
                $stayTime = 10000;
            } else {
                
                //Лепкавостта на статусите да се определя от вида на статуса
                $sticky = $this->isSticky($val['statusType']);
                
                //Времето за оставане на екрана да се определя от типа на статуса (само за тези, които не са лепкави)
                $stayTime = $this->getStayTime($val['statusType']);
            }
            
            //Създаваме шаблон за статусите
            $toastTpl = new ET("
            	setTimeout(function(){
                    $().toastmessage('showToast', {
                        text            : '[#text#]',
                        sticky          : '[#isSticky#]',
                        stayTime        : [#stayTime#],
                        inEffectDuration: 800,
                        type            : '[#type#]',
                        position        :'bottom-right',
                        });
                	}, [#timeOut#]);
                	
        	   ");
            
            //Заместваме съобщението на статуса.
            $toastTpl->replace(addslashes($val['statusText']), 'text');
            
            //Заместваме типа на статуса
            $toastTpl->replace($toastType, 'type');
            
            //Заместваме 'лепкавостта' на статуса
            $toastTpl->replace($sticky, 'isSticky');
            
            //Заместваме времето на показване на екрана
            $toastTpl->replace($stayTime, 'stayTime');
            
            //Заместваме времено на закъснение преди показване на статуса
            $toastTpl->replace($timeOut, 'timeOut');

            //Добавяме статуса към шаблона
            $JQuery->run($tpl, $toastTpl);
        }
        
        //Извикваме метода за добавяне на ajax извикване 
        $this->invoke('afterAjaxGetStatuses', array(&$tpl));
                
        //Връщаме FALSE за да не се изпълнява метода
        return FALSE;
    }
    
    
    /**
     * Прихващаме извикването на afterAjaxGetStatuses
     * 
     * @param $mvc
     * @param core_Et $tpl - Шаблона, към който ще се добавя ajax'а
     */
    function on_AfterAjaxGetStatuses($mvc, $tpl)
    {
        //URL до екшъна за вземане на статусите в json формат
        $url = toUrl(array('core_Statuses', 'ajaxGetStatuses'));
        
        //След колко милисекунди да се стартира ajax'а (15секунди)*1000
        $ajaxStartTime = 15000;
        
        //Добавяме към шаблона
        //Проверява дали има статус към текущия потребител, чрез AJAX на всеки 15 секуди
        //Ако отворения прозорец не е активен не се прави проверка, за да не се показват
        //статуси в неактивен прозорец и да останат незабелязани.
        $tpl->appendOnce("time=setTimeout(function(){getStatuses('{$url}', {$ajaxStartTime})}, {$ajaxStartTime});", 'ON_LOAD');
        $tpl->appendOnce("$(window).focus(function() {time=setTimeout(function(){getStatuses('{$url}', {$ajaxStartTime})}, {$ajaxStartTime});}).blur(function() {clearTimeout(time);});", 'ON_LOAD');
    }
    
    
    /**
     * Определя дали е статуса да е лепкав в зависимост от подадения тип
     * 
     * @param string $type - Типа на статуса
     * 
     * @return boolean $sticky - Дали статус съобщението да е лепкаво или не
     */
    function isSticky($type)
    {
        //В зависимост от типа определяме дали е да лепкав или не
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
     * @return integer $time - Колко дълго да се показва статуса на екрана
     */
    function getStayTime($type)
    {
        //В зависимост от типа определяме времето на престой на екрана
        switch ($type) {
            case 'success':
                $time = 5000;
            break;
            
            case 'notice':
                $time = 7000;
            break;
            
            case 'warning':
                $time = 10000;
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