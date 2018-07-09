<?php


/**
 * Замества абсолютните линкове в richText полетата, които сочат към системата с тяхното заглавие и икона на файла
 *
 * Замества следните URL-та:
 *
 * o doc_Containers/list/?threadId=??????
 * o doc_Threads/list/?folderId=??????
 * o [mvc]/single/[id]
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_plg_InternalLinkReplacement extends core_Plugin
{
    /**
     * Изпълнява се преди да се обработят вътрешните URL-та
     */
    public function on_BeforeInternalUrl($rt, &$res, $url, $title, $rest)
    {
        // Парсираме URL' то
        $params = type_Richtext::parseInternalUrl($rest);
        
        // Ако няма резултат, връщаме
        if ($params === false) {
            
            return ;
        }
        
        // Всички параметри
        $ctr = strtolower($params['Ctr']);
        $act = strtolower($params['Act']);
        $threadId = $params['threadId'];
        
        if (($act == 'list' || $act == 'default') && $ctr == 'colab_threads') {
            $ctr = 'doc_threads';
        }
        
        // Папки
        if ($ctr == 'doc_threads' && ($act == 'list' || $act == 'default')) {
            
            // Вземаме място
            $place = $rt->getPlace();
            
            // Добавяме в шаблона
            $res = "[#{$place}#]";
            
            // Вземаме вербалния линка към папката
            $boardRes = doc_Folders::getVerbalLink($params);
            
            // Ако функцията не върне FALSE
            if ($boardRes === false) {
                
                // Текста указващ, че нямаме достъп до системата
                $boardRes = $rt->getNotAccessMsg();
            }
            
            // Добавяме в борда
            $rt->_htmlBoard[$place] = $boardRes;
            
            // Прекратяваме по нататъшното изпълнени на програмата
            return false;
        }
        
        if ($ctr == 'colab_threads' && $act == 'single') {
            $ctr = 'doc_containers';
            $act = 'list';
        }
        
        // Нишки
        if ($ctr == 'doc_containers' && $threadId && ($act == 'list' || $act == 'default')) {
            
            // Вземаме място
            $place = $rt->getPlace();
            
            // Добавяме в шаблона
            $res = "[#{$place}#]";
            
            // Вземаме вербалния линка към папката
            $boardRes = doc_Threads::getVerbalLink($params);
            
            // Ако функцията не върне FALSE
            if ($boardRes === false) {
                
                // Текста указващ, че нямаме достъп до системата
                $boardRes = $rt->getNotAccessMsg();
            }
            
            // Добавяме в борда
            $rt->_htmlBoard[$place] = $boardRes;
            
            // Прекратяваме по нататъшното изпълнени на програмата
            return false;
        }
        
        // Сингъл
        if ($act == 'single' && isset($params['id'])) {
            
            // Вземаме място
            $place = $rt->getPlace();
            
            // Добавяме в шаблона
            $res = "[#{$place}#]";
            
            // Вземаме вербалния линка към папката
            $boardRes = doc_Containers::getVerbalLink($params);
            
            // Ако функцията не върне FALSE
            if ($boardRes === false) {
                
                // Текста указващ, че нямаме достъп до системата
                $boardRes = $rt->getNotAccessMsg();
            }
            
            // Добавяме в борда
            $rt->_htmlBoard[$place] = $boardRes;
            
            // Прекратяваме по нататъшното изпълнени на обработката на събитието
            return false;
        }
    }
    
    
    /**
     * Съобщението, което ще се показва ако нямаме достъп до обекта
     */
    public static function on_AfterGetNotAccessMsg($mvc, $res)
    {
        $res = tr('Липсващ обект');
    }
}
