<?php


/**
 * Клас 'editwatch_Plugin' - Показвай кой друг освен текущия потребител редактират записа
 *
 *
 * @category  vendors
 * @package   editwatch
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class editwatch_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm(&$mvc, $data)
    {
        if (isset($data->form->rec->id) && haveRole('user')) {
            $data->editedBy = editwatch_Editors::getAndSetCurrentEditors($mvc, $data->form->rec->id);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // id на записа
        if (!($recId = $data->form->rec->id)) return TRUE;
        
        // Съобщението
        $status = static::renderStatus($data->editedBy);
        
        // Ако не е бил сетнат
        if (!Mode::get('hitTime')) {
            
            // Записваме времето на извикване
            Mode::set('hitTime', dt::mysql2timestamp());
        }
        
        // Времето на извикване на страницата
        $hitTime = Mode::get('hitTime');
        
        // Текущото URL, което ще се използва за обновяване
        $refreshUrlLocal = toUrl(getCurrentUrl(), 'local');
        
        // Вземаме кеша за името
        $nameHash = static::getNameHash($refreshUrlLocal, $hitTime);
        
        // Кеша за съобщението
        $statusHash = static::getStatusHash($status);
        
        // Записваме кеша на съдържанието към името
        // за да не се използва след обновяване
        Mode::setPermanent($nameHash, $statusHash);
        
        // Ако не е зададено, рефрешът се извършва на всеки 5 секунди
        $time = $mvc->refreshEditwatchTime ? $mvc->refreshEditwatchTime : 5000;
        
        // Шаблон за информацията
        $info = new ET("<div id='editStatus'>[#1#]</div>", $status);
        
        // Абонираме процеса
        core_Ajax::subscribe($info, array($mvc, 'showEditwatchStatus', $recId, 'refreshUrl' => $refreshUrlLocal), 'editwatch', $time);
        
        // Добавяме информация
        $data->form->info = new core_ET('[#1#][#2#]', $data->form->info, $info);
    }
    
    
    /**
     * HTML стринг с хората, които редактират записа, освен текущия потребител
     * 
     * @param array $editedBy
     * 
     * @return string
     */
    static function renderStatus($editedBy)
    {
        $info = '<span></span>';
        
        if (count($editedBy)) {
            $info = tr("Този запис се редактира също и от|*: ");
            $sign = '';
            
            // Всички потребители, които в момента редактират
            foreach((array)$editedBy as $userId => $last) {
                
                // Линкове към профилите
                $nick = crm_Profiles::createLink($userId);
                $info .= $sign . $nick;
                $sign = ', ';
            }
            
            $info = "<span class='warningMsg'>$info</span>";
        }
        
        return $info;
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction($mvc, &$res, $act)
    {
        if ($act != 'showeditwatchstatus') return;
        
        $res = array();
        
        if (!Request::get('ajax_mode')) return ;
        
        $hitTime = Request::get('hitTime', 'int');
        
        $refreshUrl = Request::get('refreshUrl');
        
        $recId = Request::get('id', 'int');
        
        // Ако не е логнат потребител
        // Понякога и every_one може да редактира запис
        if (!haveRole('user') && !$mvc->haveRightFor('edit', $recId)) {
            $status = tr('Трябва да сте логнати, за да редактирате този запис');
            $status = "<div class='errorMsg'>$status <a href='javascript:void(0)' onclick='w = window.open(\"" . toUrl(array('core_Users', 'login', 'popup' => 1)) . "\",\"Login\",\"width=484,height=303,resizable=no,scrollbars=no,location=0,status=no,menubar=0,resizable=0,status=0\"); if(w) w.focus();'>Login</a></div>";
        } else {
            
            $editedBy = array();
            
            if (isset($recId)) {
                $editedBy = editwatch_Editors::getAndSetCurrentEditors($mvc, $recId);
            }
            
            $status = static::renderStatus($editedBy);
        }
        
        // Хеша на съобщението
        $statusHash = static::getStatusHash($status);
        
        // Хеша на името
        $nameHash    = static::getNameHash($refreshUrl, $hitTime);
        
        // Вземаме съдържанието от предишния запис
        $savedHash = Mode::get($nameHash);
        
        if(empty($savedHash)) $savedHash = md5($savedHash);
        
        // Ако са различни
        if ($statusHash != $savedHash) {
            
            // Записваме в сесията
            Mode::setPermanent($nameHash, $statusHash);
            
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id'=>'editStatus', 'html' => $status, 'replace' => TRUE);
            
            $res = array($resObj);
        }
        
        return FALSE;
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
        $nameHash = "REFRESH_ROWS_" . $nameHash;
        
        return $nameHash;
    }
    
    
    /**
     * Връща хеша за съответния текст
     * 
     * @param string $status
     * 
     * @return string
     */
    static function getStatusHash($status)
    {
        
        return md5($status);
    }
}
