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
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_plg_InternalLinkReplacement extends core_Plugin
{
    /**
     * Запазва зададеното заглавие на [link=...], като сменя само адреса.
     */
    public function on_AfterInternalLink($rt, &$res, $url, $title, $place, $rest)
    {
        if ($this->prepareInternalUrl($url, $rest)) {
            $rt->_htmlBoard[$place] = $url;
        }
    }


    /**
     * Познатите вътрешни действия използват текущия домейн и при кеширан HTML.
     */
    protected function prepareInternalUrl(&$url, &$rest)
    {
        $base = getBoot(false, false, true);
        if (strpos($rest, '//') === 0 || ($base && strpos($rest, $base . '/') !== 0)) return false;
        $local = substr($rest, strlen($base));
        $path = trim(parse_url($local, PHP_URL_PATH) ?? '', '/');
        $route = explode('/', $path);
        $query = array();
        parse_str(parse_url($local, PHP_URL_QUERY) ?? '', $query);
        $ctr = $query['Ctr'] ?? (($path === 'index.php') ? '' : ($route[0] ?? ''));
        $act = $query['Act'] ?? $route[1] ?? 'default';
        if (!is_string($ctr) || !is_string($act) || !preg_match('/^[a-z][a-z0-9_]*$/i', $ctr) || !cls::load($ctr, true)) return false;

        $class = cls::getClassName($ctr);
        $act = strtolower($act);
        $defaults = get_class_vars($class);
        $plugins = implode(',', (array) ($defaults['loadList'] ?? array()));
        if (!is_subclass_of($class, 'core_Manager') || stripos($plugins, 'cms_ExternalWrapper') !== false) return false;
        if (!in_array($act, array('single', 'list', 'default')) && !(strtolower($class) === 'fileman_files' && $act === 'previewfile') && !(strtolower($class) === 'bgerp_portal' && $act === 'show')) return false;
        if (!method_exists($class, 'act_' . $act)) return false;
        if ($act === 'default' && (new ReflectionMethod($class, 'act_Default'))->getDeclaringClass()->getName() !== 'core_Manager') return false;

        $rest = $local;
        $url = ((Mode::isReadOnly() || Mode::is('text', 'plain')) ? getBoot(true, true, true) : $base) . $rest;

        return true;
    }


    /**
     * Изпълнява се преди да се обработят вътрешните URL-та
     */
    public function on_BeforeInternalUrl($rt, &$res, &$url, $title, &$rest)
    {
        if (!$this->prepareInternalUrl($url, $rest)) return;

        // Парсираме URL' то
        $params = type_Richtext::parseInternalUrl($rest);
        
        // Ако няма резултат, връщаме
        if ($params === false) {
            
            return ;
        }
        
        // Всички параметри
        $ctr = strtolower((string) ($params['Ctr'] ?? ''));
        $act = strtolower((string) ($params['Act'] ?? ''));
        $threadId = $params['threadId'] ?? null;
        
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
        
		// Ако е копирано URL на преглед на pdf файл
        if ($act == 'previewfile' && $ctr = 'fileman_files') {
            $act = 'single';
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
                $notAccessMsg = null;
                if(cls::existsMethod($params['Ctr'], 'getNotAccessMsgInRichtext')){
                    $notAccessMsg = $params['Ctr']::getNotAccessMsgInRichtext($params['id'] ?? null);
                }

                $boardRes =  !(empty($notAccessMsg)) ? $notAccessMsg : $rt->getNotAccessMsg();
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
