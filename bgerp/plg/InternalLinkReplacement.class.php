<?php


/**
 * Замества абсолютните линкове в richText полетата, които сочат към системата с тяхното заглавие и икона на файла
 *
 * Замества следните URL-та:
 *
 *     o doc_Containers/list/?threadId=??????
 *     o doc_Threads/list/?folderId=??????
 *     o [mvc]/single/[id]
 *     
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_InternalLinkReplacement extends core_Plugin
{
    
    
    /**
     * 
     */
    function on_BeforeInternalUrl($rt, &$res, $url, $title, $rest)
    {
        $rest = trim($rest, '/');
        
        $restArr = explode('/', $rest);

        $params = array();
        
        $lastPart = $restArr[count($restArr)-1];

        if($lastPart{0} == '?') {
           $lastPart = ltrim($lastPart, '?'); 
           $lastPart = str_replace('&amp;', '&', $lastPart);
           parse_str($lastPart, $params);
           unset($restArr[count($restArr)-1]);
        }

        setIfNot($params['Ctr'], $restArr[0]);

        setIfNot($params['Act'], $restArr[1], 'default');

        if(count($restArr) % 2) {
            setIfNot($params['id'], $restArr[2]);
            $pId = 3;
        } else {
            $pId = 2;
        }
        
        // Добавяме останалите параметри, които са в часта "път"
        while($restArr[$pId]) {
            $params[$restArr[$pId]] = $params[$restArr[$pId+1]];
            $pId++;
        }
        
        // Всички параметри, които проверяваме да са в долния регистър
        $ctr = strtolower($params['Ctr']);
        $act = strtolower($params['Act']);
        $threadId = strtolower($params['threadId']);
        
        // Папки
        if($ctr == 'doc_threads' && ($act == 'list' || $act == 'default')) {
            
            // Вземаме вербалния линка към папката
            $res = doc_Folders::getVerbalLink($params);
            
            // Ако функцията не върне FALSE 
            if ($res !== FALSE) {
                
                // Прекратяваме по нататъшното изпълнени на програмата
                return FALSE;    
            }
        }
        
        // Нишки
        if($ctr == 'doc_containers' && $threadId && ($act == 'list' || $act == 'default')) {
        
            // Вземаме вербалния линка към папката
            $res = doc_Threads::getVerbalLink($params);

            // Ако функцията не върне FALSE 
            if ($res !== FALSE) {
                
                // Прекратяваме по нататъшното изпълнени на програмата
                return FALSE;    
            }   
        }

        // Сингле
        if ($act == 'single') {

            // Вземаме вербалния линка към папката
            $res = doc_Containers::getVerbalLink($params);

            // Ако функцията не върне FALSE 
            if ($res !== FALSE) {
                
                // Прекратяваме по нататъшното изпълнени на програмата
                return FALSE;    
            }       
        }
        
        return ;
    }
}