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
        
        // Ако няма резултат, връщаме
        if ($params === FALSE) return ;
        
        // Всички параметри
        $ctr = strtolower($params['Ctr']);
        $act = strtolower($params['Act']);
        $threadId = $params['threadId'];
        
        // Папки
        if($ctr == 'doc_threads' && ($act == 'list' || $act == 'default')) {
            
            // Вземаме място
            $place = $rt->getPlace();
            
            // Добавяме в шаблона
            $res = "[#{$place}#]";
            
            // Вземаме вербалния линка към папката
            $boardRes = doc_Folders::getVerbalLink($params);
            
            // Ако функцията не върне FALSE 
            if ($boardRes === FALSE) {
                
                // Текста указващ, че нямаме достъп до системата
                $boardRes = static::getNotAccessMsg();
            }
            
            // Добавяме в борда
            $rt->_htmlBoard[$place] = $boardRes;
            
            // Прекратяваме по нататъшното изпълнени на програмата
            return FALSE;    
        }
        
        // Нишки
        if($ctr == 'doc_containers' && $threadId && ($act == 'list' || $act == 'default')) {
            
            // Вземаме място
            $place = $rt->getPlace();
            
            // Добавяме в шаблона
            $res = "[#{$place}#]";
            
            // Вземаме вербалния линка към папката
            $boardRes = doc_Threads::getVerbalLink($params);

            // Ако функцията не върне FALSE 
            if ($boardRes === FALSE) {
                
                // Текста указващ, че нямаме достъп до системата
                $boardRes = static::getNotAccessMsg();
            }
            
            // Добавяме в борда
            $rt->_htmlBoard[$place] = $boardRes;
            
            // Прекратяваме по нататъшното изпълнени на програмата
            return FALSE;    
        }

        // Сингъл
        if ($act == 'single') {
            
            // Вземаме място
            $place = $rt->getPlace();
            
            // Добавяме в шаблона
            $res = "[#{$place}#]";
            
            // Вземаме вербалния линка към папката
            $boardRes = doc_Containers::getVerbalLink($params);

            // Ако функцията не върне FALSE 
            if ($boardRes === FALSE) {
                
                // Текста указващ, че нямаме достъп до системата
                $boardRes = static::getNotAccessMsg();
            }
            
            // Добавяме в борда
            $rt->_htmlBoard[$place] = $boardRes;
            
            // Прекратяваме по нататъшното изпълнени на обработката на събитието
            return FALSE;         
        }
        
        return ;
    }
    
    
    /**
     * Съобщението, което ще се показва ако нямаме достъп до обекта
     */
    static function getNotAccessMsg()
    {
        $text = tr('Липсващ обект');
        if (Mode::is('text', 'plain')) {
            
            // 
            $str = $text;
            
        } else {
            // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            // Иконата за линка
            $sbfIcon = sbf('img/16/link_break.png','"', $isAbsolute);
            
            // Съобщението
            $str = "<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> {$text} </span>"; 
                
        }
        
        return $str;
    }
}