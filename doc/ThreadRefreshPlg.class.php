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
     * Преди рендиране на врапера
     * 
     * @param core_Mvc $mvc
     * @param core_ET $res
     * @param core_ET $tpl
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data=NULL)
    {
        // Ако не се листва, да не се изпълнява
        if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab')) {
            if ($data->action != 'single') return ;
        } elseif($data->action != 'list') {
            
            return ;
        }
        
        // Ако не се вика по AJAX
        if (!Request::get('ajax_mode')) {
            
            // URL-то, което ще се вика по AJAX
            $refreshUrl = getCurrentUrl();
            
            // Локално URL
            $refreshUrlLocal = toUrl($refreshUrl, 'local');
            
            // URL, което ще се вика по AJAX
            $url = array($mvc, 'ajaxThreadRefresh', 'refreshUrl' => $refreshUrlLocal, 'threadId' => Request::get('threadId', 'int'));
            
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
     * 
     * 
     * @param core_Manager $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    public static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        $hash = self::getDocumentStatesHash($data->recs);
        $hashName = self::getStateHashName($data->threadId, $data->recs);
        
        Mode::setPermanent($hashName, $hash);
        
        if (!Request::get('ajax_mode')) {
            $threadLastSendName = self::getLastSendName($data->threadId);
            Mode::setPermanent($threadLastSendName, dt::now());
        }
    }
    
    
    /**
     * Проверява дали стария и новия хеш си отговарят и ако е зададено сетва новия
     * 
     * @param ingeter $threadId
     * @param array $recsArr
     * @param boolean $setNew
     * 
     * @return boolean
     */
    public static function checkHash($threadId, $recsArr, $setNew = TRUE)
    {
        $hash = self::getDocumentStatesHash($recsArr);
        $hashName = self::getStateHashName($threadId, $recsArr);
        
        $oldHash = Mode::get($hashName);
        
        if ($oldHash == $hash) return TRUE;
        
        if ($setNew) {
            Mode::setPermanent($hashName, $hash);
        }
        
        return FALSE;
    }
    
    
    /**
     * 
     * 
     * @param integer $threadId
     * @param array $recsArr
     * 
     * @return string
     */
    protected static function getStateHashName($threadId, $recsArr)
    {
        if (!$threadId && !empty($recsArr)) {
            $recKey = key($recsArr);
            $threadId = $recsArr[$recKey]->threadId;
        }
        
        $hitTime = Request::get('hitTime');
        if (!$hitTime) {
            $hitTime = Mode::get('hitTime');
        }
        if (!$hitTime) {
            $hitTime = dt::mysql2timestamp();
        }
        
        $hashName = 'ThreadStatesHash_' . $threadId . '_' . $hitTime;
        
        return $hashName;
    }
    
    
    /**
     * 
     * 
     * @param array $recsArr
     * @return string|NULL
     */
    protected static function getDocumentStatesHash($recsArr)
    {
        if (empty($recsArr)) return ;
        
        ksort($recsArr);
        
        $states = '|';
        foreach ($recsArr as $rec) {
            $states .= $rec->state . '|';
        }
        
        $hash = md5($states);
        
        return $hash;
    }
    
    
    /**
     * 
     * 
     * @param integer $threadId
     * 
     * @return string
     */
    protected static function getLastSendName($threadId)
    {
        $hitTime = Request::get('hitTime');
        if (!$hitTime) {
            $hitTime = Mode::get('hitTime');
        }
        if (!$hitTime) {
            $hitTime = dt::mysql2timestamp();
        }
        
        $threadLastSendName = 'LastSendThread_' . $threadId . '_' . $hitTime;
        
        return $threadLastSendName;
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
        
        $threadId = Request::get('threadId', 'int');
        
        if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab')) {
            $tRec = doc_Threads::fetch($threadId);
            colab_Threads::requireRightFor('single', $tRec);
        } else {
            doc_Threads::requireRightFor('single', $threadId);
        }
        
        $hitTime = Request::get('hitTime');
        
        $recsArr = array();
        
        $cQuery = doc_Containers::getQuery();
        $cQuery->where("#threadId = {$threadId}");
        $cQuery->orderBy('#id', 'ASC');
        $cQuery->show('id,state,threadId');
        
        while ($rec = $cQuery->fetch()) {
            $recsArr[$rec->id] = $rec;
        }
        
        if (self::checkHash($threadId, $recsArr)) return FALSE;
        
        // URL-то за рефрешване
        $refreshUrlStr = Request::get('refreshUrl');
        
        // Парсираме URL-то
        $refreshUrl = core_App::parseLocalUrl($refreshUrlStr);
        
        $refreshUrl['ajax_mode'] = $ajaxMode;
        
        // Вземаме шаблона
        $tpl = Request::forward($refreshUrl);
        
        // Ако липсва шаблона, да не се изпълнява
        if (!$tpl) return FALSE;
        
        // Вземаме съдържанието на шаблона
        $content = static::getContent($tpl);
        
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
        $resObj->arg = array('id'=>'rowsContainer', 'html' => $content, 'replace' => TRUE, 'css' => $cssArr, 'js' => $jsArr);
        
        $resStatus[] = $resObj;
        
        // Да предизвикаме релоад след връщане назад
        $resObjReload = new stdClass();
        $resObjReload->func = 'forceReloadAfterBack';
        $resStatus[] = $resObjReload;
        
        // JS функции, които да се пуснат след AJAX
        jquery_Jquery::runAfterAjax($tpl, 'smartCenter');
        jquery_Jquery::runAfterAjax($tpl, 'makeTooltipFromTitle');
        jquery_Jquery::runAfterAjax($tpl, 'sumOfChildrenWidth');
        jquery_Jquery::runAfterAjax($tpl, 'editCopiedTextBeforePaste');
        jquery_Jquery::runAfterAjax($tpl, 'removeNarrowScroll');
        jquery_Jquery::runAfterAjax($tpl, 'getContextMenuFromAjax');
        jquery_Jquery::runAfterAjax($tpl, 'setThreadElemWidth');

        // Стойности на плейсхолдера
        $runAfterAjaxArr = $tpl->getArray('JQUERY_RUN_AFTER_AJAX');
        
        // Добавя всички функции в масива, които ще се виката
        if (!empty($runAfterAjaxArr)) {
            
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
            
            $modifiedDocsArr = array();
            $cu = core_Users::getCurrent();

            foreach ((array)$docsArr as $cid => $docId) {
                $cRec = doc_Containers::fetch($cid);
                if($cRec) {
                    $currUrl = getCurrentUrl();
                    $currUrl['#'] = $docId;
                    $link = ht::createLink('#' . $docId, $currUrl, NULL, array('onclick' => "getEO().scrollTo('$docId'); return false;"));
                    
                    if($cu == $cRec->modifiedBy) continue;

                    $user = crm_Profiles::createLink($cRec->modifiedBy);
                    $action = ($cRec->modifiedOn == $cRec->createdOn) ? tr("добави") : tr("промени");
                    $msg = "{$user} {$action} {$link}";
                    
                    $statusData = array();
                    $statusData['text'] = $msg;
                    $statusData['type'] = 'notice';
                    $statusData['timeOut'] = 700;
                    $statusData['isSticky'] = 0;
                    $statusData['stayTime'] = 15000;
                    
                    $statusObj = new stdClass();
                    $statusObj->func = 'showToast';
                    $statusObj->arg = $statusData;

                    $resStatus[] = $statusObj;
                }
            }
        }
        
        $threadLastSendName = self::getLastSendName($threadId);
        Mode::setPermanent($threadLastSendName, dt::now());
        
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
     * След подготвяне на вербалната стойност на полетата
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if (Request::get('ajax_mode')) {
            // Масив с променените документи
            $docsArr = array();
            
            $threadId = Request::get('threadId', 'int');
            
            $threadLastSendName = self::getLastSendName($threadId);
            
            $lastSend = Mode::get($threadLastSendName);
            
            // Намира всички документи, които са променени
            if ($lastSend && count($data->recs)) {
            
                foreach($data->recs as $id => $r) {
            
                    // Ако са променени след последно изтегленото време
                    if($r->modifiedOn >= $lastSend) {
            
                        // Добавяме хендълуте в масива
                        $docsArr[$id] = $data->rows[$id]->ROW_ATTR['id'];
                    }
                }
            }
            
            // Добавяме всички променени документи
            Mode::set('REFRESH_DOCS_ARR', $docsArr);
        }
    }
}
