<?php


/**
 * Клас 'doc_ThreadRefreshPlg' - Ajax обновяване на нишка
 * 
 * 
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ThreadRefreshPlg extends core_Plugin
{
    
    
    /**
     * Колко дни да стои в лога
     */
    static $logKeepDays = 5;
    
    
    /**
     * Преди рендиране на врапера
     * 
     * @param core_Mvc $mvc
     * @param core_ET $res
     * @param core_ET $tpl
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data=NULL)
    {
        // Ако не се листва, да не се изпълнява
        if($data->action != 'list') return;
        
        // Ако не се вика по AJAX
        if (!Request::get('ajax_mode')) {
            
            // URL-то, което ще се вика по AJAX
            $refreshUrl = getCurrentUrl();
            
            // Локално URL
            $refreshUrlLocal = toUrl($refreshUrl, 'local');
            
            // URL, което ще се вика по AJAX
            $url = array($mvc, 'ajaxThreadRefresh', 'refreshUrl' => $refreshUrlLocal);
            
            // Ако не е зададено, рефрешът се извършва на всеки 60 секунди
            $time = $mvc->refreshRowsTime ? $mvc->refreshRowsTime : 60000;
            
            // Името с което ще се добави в масива
            $name = $mvc->className . '_ThreadRefresh';
            
            // Абонираме URL-то
            core_Ajax::subscribe($tpl, $url, $name, $time);
            
            // Обграждаме с дивове
            $tpl->prepend("<div id='rowsContainer'>");
            $tpl->append("</div>");
            
            $res = $tpl;
        } else {
            
            // Ако се вика по AJAX
            
            $res = $tpl;
            
            return FALSE;
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param array $resStatus
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$resStatus, $action)
    {
        // Ако екшъна не е за обновяване на редовете, да не се изпълнява
        if ($action != 'ajaxthreadrefresh') return ;
        
        $resStatus = array();
        
        $ajaxMode = Request::get('ajax_mode');
        
        // Ако заявката не е по ajax
        if (!$ajaxMode) return FALSE;
        
        // URL-то за рефрешване
        $refreshUrlStr = Request::get('refreshUrl');
        
        // Парсираме URL-то
        $refreshUrl = core_App::parseLocalUrl($refreshUrlStr);
        
        // Добавяме флага
        $refreshUrl['ajax_mode'] = $ajaxMode;
        
        // Вземаме шаблона
        $tpl = Request::forward($refreshUrl);
        
        // Ако липсва шаблона, да не се изпълнява
        if (!$tpl) return FALSE;
        
        // Вземаме съдържанието на шаблона
        $content = static::getContent($tpl);
        
        // Добавяме резултата
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id'=>'rowsContainer', 'html' => $content, 'replace' => TRUE);
        
        $resStatus[] = $resObj;
        
        // Стойности на плейсхолдера
        $runAfterAjaxArr = $tpl->getArray('JQUERY_RUN_AFTER_AJAX');
        
        // Добавя всички функции в масива, които ще се виката
        if (is_array($runAfterAjaxArr) && count($runAfterAjaxArr)) {
            
            // Да няма повтарящи се функции
            $runAfterAjaxArr = array_unique($runAfterAjaxArr);
            
            foreach ((array)$runAfterAjaxArr as $runAfterAjax) {
                $resObj = new stdClass();
                $resObj->func = $runAfterAjax;
                
                $resStatus[] = $resObj;
            }
        }
        
        // Масив с id-тата на всички променени документи
        $docsArr = Mode::get('REFRESH_DOCS_ARR');
        
        // Ако има документи за обновяване
        if ($docsArr) {
            foreach ((array)$docsArr as $docId) {
                
                $flashDocObj = new stdClass();
                $flashDocObj->func = 'flashDoc';
                $flashDocObj->arg = $docId;
                
                $resStatus[] = $flashDocObj;
            }
            
            // Ако е зададено да се скролира до края на нишката
            if (!Mode::get('REFRESH_DOCS_SCROLL_TO_END')) {
                
                // id на полследния документ
                reset($docsArr);
                $docId = end($docsArr);
                
                $scrollToDocId = $docId;
            }
            
            $scrollToObj = new stdClass();
            $scrollToObj->func = 'scrollTo';
            $scrollToObj->arg = $scrollToDocId;
            
            $resStatus[] = $scrollToObj;
        }
        
        // Добавяме в лога
        // core_Logs::add($mvc, NULL, 'AJAX refresh thread: ' . $mvc->title, static::$logKeepDays);
        
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
        if (!$tpl instanceof core_ET || !$tpl) return $tpl;
        
        // Клонираме, за да не променяме оригиналния обект
        $cTpl = clone $tpl;
        
        // Премахваме празните блокове
        $cTpl->removePlaces();
        
        // Вземаме съсъджанието
        $status = $cTpl->getContent();
        
        return $status;
    }
    
    
    /**
     * Преди подготвяне на записите
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $ajaxMode = Request::get('ajax_mode');
        
        // Ако се вика по AJAX
        if (!$ajaxMode) {
            
            // Ако не е бил сетнат
            if (!Mode::get('hitTime')) {
                
                // Записваме времето на извикване
                Mode::set('hitTime', dt::mysql2timestamp());
            }
            
            // Времето на извикване на страницата
            $hitTime = Mode::get('hitTime');
        } else {
            
            // Ако сме по AJAX, вземаме от рекуеста
            $hitTime = Request::get('hitTime');
        }
        
        // Масив с промените по нишката
        $threadsLastModify = Mode::get("THREADS_LAST_MODIFY");
        
        // id на нишката
        $threadId = $data->threadRec->id;
        
        // Време на последна променя на нишката
        $lastModify = $data->threadRec->modifiedOn;
        
        // Ако няма промяна
        if(($threadsLastModify[$hitTime][$threadId] == $lastModify) && $ajaxMode) {
            
            // Вдигаме флага
            $data->noChanges = TRUE;
        } else {
            
            // Време на предишната промяна
            $data->lastRefresh = $threadsLastModify[$hitTime][$threadId];
            
            // Време на последната промяна
            $threadsLastModify[$hitTime][$threadId] = $lastModify;
            
            // Обновяваме данните
            Mode::setPermanent("THREADS_LAST_MODIFY", $threadsLastModify);
        }
    }
    
    
    /**
     * След подготвяне на вербалната стойност на полетата
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        // Масив с променените документи
        $docsArr = array();
        
        // Ако има документи
        if($data->lastRefresh && count($data->recs)) {
            
            // Последния документ в нишката
            $lastRec = end($data->recs);
            
            // Обхождаме всички резултати
            foreach($data->recs as $id => $r) {
                
                // Ако са променени след последно изтегленото време
                if($r->modifiedOn > $data->lastRefresh) {
                    
                    // Добавяме хендълуте в масива
                    $docsArr[] = $data->rows[$id]->ROW_ATTR['id'];
                    
                    // Ако е последния запис
                    if($lastRec->id == $r->id) {
                        
                        // Да се скролира до последния запис
                        Mode::set('REFRESH_DOCS_SCROLL_TO_END', TRUE);
                    }
                }
            }
            
            // Добавяме всички променени документи
            Mode::set('REFRESH_DOCS_ARR', $docsArr);
        }
    }
    
    
    /**
     * Преди рендиране на листовия изглед
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param object $data
     */
    function on_BeforeRenderList($mvc, &$res, $data)
    {
        // Ако няма промени, да не се изпълнява
        if ($data->noChanges) {
            
            $res = new ET($res);
            
            return FALSE;
        }
    }
    
    
    /**
     * Преди вкарване на записив в лога
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $detail
     * @param integer $objectId
     * @param integer $logKeepDays
     */
    static function on_BeforeLog($mvc, &$res, $detail, $objectId = NULL, &$logKeepDays = NULL)
    {
        // Ако заявката е по AJAX
        if (Request::get('ajax_mode')) {
            
            return FALSE;
        }
    }
}
