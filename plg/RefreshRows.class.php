<?php


/**
 * Клас 'plg_RefreshRows' - Ajax обновяване на табличен изглед
 * 
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_RefreshRows extends core_Plugin
{
    
    
    /**
     * Колко дни да стои в лога
     */
    public static $logKeepDays = 5;
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        // Ако не се тегли по AJAX
        if (!Request::get('ajax_mode')) {
            
            // URL-то, което ще се вика по AJAX
            $refreshUrl = $mvc->prepareRefreshRowsUrl(getCurrentUrl());
            
            // Локално URL
            $refreshUrlLocal = toUrl($refreshUrl, 'local');
            
            // Генерираме уникално id от името
            $attr = array('name' => 'rowsContainer');
            ht::setUniqId($attr);
            
            // Вземаме съдържанието от шаблона
            // Трябва да се определи преди добавянето на дива
            $content = static::getContent($tpl);
            
            // Добавяме текста в див с уникално id
            $tpl->prepend("<div id='{$attr['id']}'>");
            $tpl->append("</div>");
            
            // URL, което ще се вика по AJAX
            $url = array($mvc, 'ajaxRefreshRows', 'divId' => $attr['id'], 'refreshUrl' => $refreshUrlLocal);
            
            // Ако не е зададено, рефрешът се извършва на всеки 60 секунди
            $time = $mvc->refreshRowsTime ? $mvc->refreshRowsTime : 60000;
            
            // Името с което ще се добави в масива
            $name = $mvc->className . '_RefreshRows';
            
            // Ако страницата ще се обновява ръчно по AJAX
            if ($mvc->manualRefreshCnt) {
                $hitId = Request::get('hit_id');
                if (!$hitId) {
                    $hitId = str::getRand('######');
                }
                jquery_Jquery::run($tpl, "getEfae().setHitId('{$hitId}');", TRUE);
            }
            
            // Абонираме URL-то
            core_Ajax::subscribe($tpl, $url, $name, $time);
            
            // Ако не е бил сетнат
            if (!Mode::get('hitTime')) {
                
                // Записваме времето на извикване
                Mode::set('hitTime', dt::mysql2timestamp());
            }
            
            // Вземаме кеша на съдържанието
            $contentHash = $mvc->getContentHash($content);
            
            // Времето на извикване на страницата
            $hitTime = Mode::get('hitTime');
            
            // Вземаме кеша за името
            $nameHash = static::getNameHash($refreshUrlLocal, $hitTime);
            
            // Записваме кеша на съдържанието към името
            // за да не се използва след обновяване
            Mode::setPermanent($nameHash, $contentHash);
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        // Ако екшъна не е за обновяване на редовете, да не се изпълнява
        if ($action != 'ajaxrefreshrows') return ;
        
        $res = array();
        
        $ajaxMode = Request::get('ajax_mode');
        
        // Ако заявката не е по ajax
        if (!$ajaxMode) return FALSE;
        
        // URL-то за рефрешване
        $refreshUrlStr = Request::get('refreshUrl');
        
        // Времето на отваряне на страницата
        $hitTime = Request::get('hitTime');
        
        // Кеша зе името
        $nameHash = static::getNameHash($refreshUrlStr, $hitTime);
        
        $manualNameHash = static::getManualNameHash($nameHash);
        
        // Ако ще се обновява ръчно и вече е обновен n пъти
        $stopRefresh = $mvc->stopМanualRefresh($manualNameHash);
        
        if ($stopRefresh === TRUE) {
        
            return FALSE;
        }
        
        // Парсираме URL-то
        $refreshUrlOrig = $refreshUrl = core_App::parseLocalUrl($refreshUrlStr);
        
        // Добавяме флага
        $refreshUrl['ajax_mode'] = $ajaxMode;
        
        // Вземаме шаблона
        $tpl = Request::forward($refreshUrl);
        
        // Ако липсва шаблона, да не се изпълнява
        if (!$tpl) return FALSE;
        
        // Вземаме съдържанието на шаблона
        $status = static::getContent($tpl->getBlock('ListTable'));
        
        // Вземаме кеша на съдържанието
        $statusHash = $mvc->getContentHash($status);
        
        // Вземаме съдържанието от предишния запис
        $savedHash = Mode::get($nameHash);
        
        if(empty($savedHash)) $savedHash = md5($savedHash);
        
        // Ако има промяна
        if($statusHash != $savedHash) {
            
            // Ако ще се обновява ръчно
            if ($mvc->manualRefreshCnt) {
                
                $refeshCnt = (int)Mode::get($manualNameHash);
                $refeshCnt++;
                
                $type = 'notice';
                if ($mvc->manualRefreshCnt == $refeshCnt) {
                    $type = 'notice';
                }
                
                $res = $mvc->manualRefreshRes($refreshUrlOrig, $type);
                
                Mode::setPermanent($manualNameHash, $refeshCnt);
                
                return FALSE;
            }
            
            // Записваме новата стойност, за да не се извлича следващия път за този таб
            Mode::setPermanent($nameHash, $statusHash);
            
            $divId = Request::get('divId');
            
            // Масив с добавения CSS
            $cssArr = array();
            $allCssArr = (array)$tpl->getArray('CSS');
            $allCssArr = array_unique($allCssArr);
            foreach ($allCssArr as $css) {
                $cssArr[] = page_Html::getFileForAppend($css);
            }
            
            // Масив с добавения JS
            $jsArr = array();
            $allJsArr = (array)$tpl->getArray('JS');
            $allJsArr = array_unique($allJsArr);
            foreach ($allJsArr as $js) {
                $jsArr[] = page_Html::getFileForAppend($js);
            }
            
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id'=>$divId, 'html' => $status, 'replace' => TRUE, 'css' => $cssArr, 'js' => $jsArr);
            
            $res = array($resObj);
            
            // Да предизвикаме релоад след връщане назад
            $resObjReload = new stdClass();
            $resObjReload->func = 'forceReloadAfterBack';
            $res[] = $resObjReload;
            
            jquery_Jquery::runAfterAjax($tpl, 'getContextMenuFromAjax');
            jquery_Jquery::runAfterAjax($tpl, 'smartCenter');
            
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
     * Връща съдържанието на шаблона
     * 
     * @param core_ET $tpl
     * 
     * @return string
     */
    static function getContent($tpl)
    {
        // Ако не е обект или няма съдържание
        if (!$tpl) return ;
        
        // Ако не е шаблон
        if (!$tpl instanceof core_ET) {
            
            // Създаваме шаблон
            $tpl = new ET($tpl);
        }
        
        // Клонираме, за да не променяме оригиналния обект
        $cTpl = clone $tpl;
        
        jquery_Jquery::runAfterAjax($cTpl, 'makeTooltipFromTitle');
        jquery_Jquery::runAfterAjax($cTpl, 'smartCenter');
        
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
     * 
     * @return string
     */
    static function getNameHash($refreshUrl, $hitTime)
    {
        // От URL-то и hitTime генерираме хеша за името
        $nameHash = md5(toUrl($refreshUrl) . $hitTime);
        
        // Името на хеша, с който е записан в сесията
        $nameHash = "REFRESH_ROWS_" . $nameHash;
        
        return $nameHash;
    }
    
    
    /**
     * 
     * @param core_Mvc $mvc
     * @param boolean|NULL $res
     */
    public static function on_AfterStopМanualRefresh($mvc, &$res, $nameHash)
    {
        if (!$mvc->manualRefreshCnt) return ;
        
        $maxRefreshTimes = $mvc->manualRefreshCnt;
        
        $res = FALSE;
        
        if ((int)Mode::get($nameHash) >= $maxRefreshTimes) {
            $res = TRUE;
        }
    }
    
    
    /**
     * 
     * @param core_Mvc $mvc
     * @param array|NULL $res
     */
    public static function on_AfterManualRefreshRes($mvc, &$res, $refreshUrl, $type)
    {
        if (!isset($res)) {
            $res = array();
        }
        
        core_Statuses::newStatus('|Има промени в страницата|* - '. ht::createLink('опресняване', $refreshUrl), $type, NULL, 300, Request::get('hitId'));
    }
    
    
    /**
     * Връща хеша от URL-то и времето на извикване на страницата
     * 
     * @param string `fileman_data`.`file_len`
     * 
     * @return string
     */
    static function getManualNameHash($nameHash)
    {
        // Името на хеша, с който е записан в сесията
        $nameHash = "MANUAL_" . $nameHash;
        
        return $nameHash;
    }
    
    
    /**
     * Функция по подразбиране, за връщане на хеша на резултата
     * 
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $status
     * @param object $data
     */
    function on_AfterGetContentHash($mvc, &$res, &$status)
    {
        $res = md5(trim($status));
    }
    
    
    /**
     * Подготвя URL-то, което ще се вика по AJAX
     * 
     * @param core_Mvc $mvc
     * @param unknown_type $res
     * @param unknown_type $url
     */
    function on_AfterPrepareRefreshRowsUrl($mvc, &$res, $url)
    {
        $res = $url;
    }
    
    
    /**
     * Преди рендиране на листовия изглед
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    function on_BeforeRenderList($mvc, &$tpl, $data)
    {
        // Ако се вика по AJAX, викаме само функциите, които ни трябват
        // и спираме по нататъшното извикване на другите функции
        if (Request::get('ajax_mode')) {
            
            // Рендираме общия лейаут
            $tpl = $mvc->renderListLayout($data);
            
            setIfNot($data->listTableMvc, clone $mvc);
            
            // Попълваме таблицата с редовете
            $tpl->append($mvc->renderListTable($data), 'ListTable');
            
            return FALSE;
        }
    }
    
    
    /**
     * Преди рендиране на врапера
     * 
     * @param core_Mvc $mvc
     * @param core_ET $res
     * @param core_ET $tpl
     */
    function on_BeforeRenderWrapping($mvc, &$res, $tpl)
    {
        // Ако се вика по AJAX, да не се рендира
        if (Request::get('ajax_mode')) {
            
            $res = $tpl;
            
            return FALSE;
        }
    }
}
