<?php



/**
 * Рефрешване на рендиранията на нотификации и последни
 *
 * @category  ef
 * @package   plg
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see plg_RefreshRows
 */
class bgerp_RefreshRowsPlg extends core_Plugin
{
    
    /**
     * Колко време да стои в лога записа
     */
    public static $logKeepDays = 5;
    
    
    /**
     * След извикване на `render`.
     * Абонира извикването на функцията по AJAX
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     */
    function on_AfterRender($mvc, &$tpl)
    {
        // Ако не се тегли по AJAX
        if (!Request::get('ajax_mode')) {
            
            // Клонираме шаблона
            $tplClone = clone $tpl;
            
            // URL-то, което ще се вика по AJAX
            $refreshUrl = $mvc->getRefreshRowsUrl(getCurrentUrl());
            
            // Ако не е зададено, рефрешът се извършва на всеки 60 секунди
            $time = $mvc->bgerpRefreshRowsTime ? $mvc->bgerpRefreshRowsTime : 60000;
            
            // Името с което ще се добави в масива
            $name = $mvc->className . '_BGERPRefreshRows';
            
            // Абонираме URL-то
            core_Ajax::subscribe($tpl, $refreshUrl, $name, $time);
            
            // Ако не е бил сетнат
            if (!Mode::get('hitTime')) {
                
                // Записваме времето на извикване
                Mode::set('hitTime', dt::mysql2timestamp());
            }
            
            // Вземаме блока със съдържанието
            $hashTpl = $tplClone->getBlock('PortalTable');
            
            // Вземаме съдържанието от шаблона
            $content = static::getContent($hashTpl);
            
            // Вземаме кеша на съдържанието
            $contentHash = $mvc->getContentHash($content);
            
            // Времето на извикване на страницата
            $hitTime = Mode::get('hitTime');
            
            // Вземаме кеша за името
            $nameHash = static::getNameHash($refreshUrl, $hitTime);
            
            // Записваме кеша на съдържанието към името
            // за да не се използва след обновяване
            Mode::setPermanent($nameHash, $contentHash);
        }
    }
    
    
    /**
     * Преди извикване на екшъна
     *
     * @param core_Mvc $mvc
     * @param array $res
     * @param string $action
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        // Ако няма да се рендира
        if ($action != 'render') return ;
        
        $ajaxMode = Request::get('ajax_mode');
        
        // Ако заявката не е по ajax
        if (!$ajaxMode) return ;
        
        $res = array();
        
        // Изискваме да е логнат потребител
        requireRole('user');
        
        // Времето на извикване на страницата
        $hitTime = Request::get('hitTime');
        
        // Рендираме резултата
        $tpl = $mvc->render();
        
        // Ако липсва шаблона, да не се изпълнява
        if (!$tpl) return FALSE;
        
        // Вземаме съдържанието на шаблона
        $status = static::getContent($tpl);
        
        // Вземаме кеша на съдържанието
        $statusHash = $mvc->getContentHash($status);
        
        // Времето на отваряне на страницата
        $hitTime = Request::get('hitTime');
        
        // Текущото URL
        $currUrl = $mvc->getRefreshRowsUrl(getCurrentUrl());
        
        // Кеша зе името
        $nameHash = static::getNameHash($currUrl, $hitTime);
        
        // Вземаме съдържанието от предишния запис
        $savedHash = Mode::get($nameHash);
        
        if(empty($savedHash)) $savedHash = md5($savedHash);
        
        // Ако има промяна
        if($statusHash != $savedHash) {
            
            // Записваме новата стойност, за да не се извлича следващия път за този таб
            Mode::setPermanent($nameHash, $statusHash);
            
            $divId = $mvc->getDivId();
            
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => $divId, 'html' => $status, 'replace' => TRUE);
            
            $res = array($resObj);
            
            // Стойности на плейсхолдера
            $runAfterAjaxArr = $tpl->getArray('JQUERY_RUN_AFTER_AJAX');
            
            // Добавя всички функции в масива, които ще се виката
            if (!empty($runAfterAjaxArr)) {
            
                // Да няма повтарящи се функции
                $runAfterAjaxArr = array_unique($runAfterAjaxArr);
            
                foreach ((array)$runAfterAjaxArr as $runAfterAjax) {
                    $jqResObj = new stdClass();
                    $jqResObj->func = $runAfterAjax;
            
                    $res[] = $jqResObj;
                }
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща URL-то за рефрешване
     *
     * @param core_Mvc $mvc
     * @param array $res
     * @param array $url
     *
     * @return array
     */
    function on_AfterGetRefreshRowsUrl($mvc, &$res, $url)
    {
        $url['Ctr'] = $mvc;
        $url['Act'] = 'render';
        unset($url['id']);
        
        $res = $url;
    }
    
    
    /**
     * Връща съдържанието на шаблона
     *
     * @param core_ET $tpl
     *
     * @return string
     */
    static function getContent($tpl)
    {
        // Ако не е обект или няма съдържание
        if (!$tpl instanceof core_ET || !$tpl) return $tpl;
        
        // Клонираме, за да не променяме оригиналния обект
        $cTpl = clone $tpl;
        
        // Премахваме празните блокове
        $cTpl->removePlaces();
        
        // Вземаме съсъджанието
        $status = $cTpl->getContent();
        
        $status = preg_replace('/\<\!\-\-.+?\-\-\>/', '', $status);
        
        return $status;
    }
    
    
    /**
     * Връща хеша от URL-то и времето на извикване на страницата
     *
     * @param array $refreshUrl
     * @param integer $hitTime
     */
    static function getNameHash($refreshUrl, $hitTime)
    {
        // От URL-то и hitTime генерираме хеша за името
        $nameHash = md5(toUrl($refreshUrl) . $hitTime);
        
        // Името на хеша, с който е записан в сесията
        $nameHash = "BGERP_REFRESH_ROWS_" . $nameHash;
        
        return $nameHash;
    }
    
    
    /**
     * Функция по подразбиране, за връщане на хеша на резултата
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $status
     */
    function on_AfterGetContentHash($mvc, &$res, &$status)
    {
        $res = md5(trim($status));
    }
    
    
    /**
     * Връща id, което ще се използва за обграждащия div на таблицата, който ще се замества по AJAX
     *
     * @param core_Mvc $mvc
     * @param string $res
     */
    function on_AfterGetDivId($mvc, &$res)
    {
        $res = $mvc->className . '_PortalTable';
    }
}
