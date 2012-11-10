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
        // Парсираме URL' то
        $params = type_Richtext::parseInternalUrl($rest);
        
        // Всички параметри
        $ctr = $params['Ctr'];
        $act = $params['Act'];
        $threadId = $params['threadId'];
        
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