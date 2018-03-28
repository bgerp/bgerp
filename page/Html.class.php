<?php 


/**
 * Клас 'page_Html' - Общ шаблон за всички HTML страници
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class page_Html extends core_ET {
    
    
    /**
     * Конструктор, който генерира лейаута на шаблона
     */
    function __construct() {
        
        $bodyClass = Mode::is('screenMode', 'narrow') ? "narrow narrow-scroll" : "wide";
        
        $bodyId = str::getRand();

        $manifestUrl = toUrl(array('core_PwaManifest'));

        parent::__construct(
            "<!doctype html>" .
            
            (Mode::is('screenMode', 'narrow') ?
                "\n<html xmlns=\"http://www.w3.org/1999/xhtml\" [#OG_PREFIX#]>" :
                "\n<html [#OG_PREFIX#]>") . 
                
            "\n<head>" .
            "\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=2\">" .
            "\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=[#ENCODING#]\">" .
            "<!--ET_BEGIN META_OGRAPH-->[#META_OGRAPH#]<!--ET_END META_OGRAPH-->".
            "<!--ET_BEGIN META_DESCRIPTION-->\n<meta name=\"description\" content=\"[#META_DESCRIPTION#]\"><!--ET_END META_DESCRIPTION-->" .
            "<!--ET_BEGIN META_KEYWORDS-->\n<meta name=\"keywords\" content=\"[#META_KEYWORDS#]\"><!--ET_END META_KEYWORDS-->" .
            "<!--ET_BEGIN PAGE_TITLE-->\n<title>[#NOT_CNT#][#PAGE_TITLE#]</title><!--ET_END PAGE_TITLE-->" .
            "<!--ET_BEGIN STYLE_IMPORT-->\n<style type=\"text/css\">[#STYLE_IMPORT#]\n</style><!--ET_END STYLE_IMPORT-->" .
            "<!--ET_BEGIN STYLES-->\n<style type=\"text/css\">[#STYLES#]\n</style><!--ET_END STYLES-->" .
            "<!--ET_BEGIN MANIFEST-->\n<link rel=\"manifest\" href=\"{$manifestUrl}\">\n<!--ET_END MANIFEST-->" .
            "<!--ET_BEGIN HEAD-->[#HEAD#]<!--ET_END HEAD-->" .
            "\n</head>" .
            "\n<body<!--ET_BEGIN ON_LOAD--> onload=\"[#ON_LOAD#]\"<!--ET_END ON_LOAD--> id= \"{$bodyId}\" class=\"{$bodyClass} [#BODY_CLASS_NAME#]\">" .
        	"<!--ET_BEGIN START_SCRIPTS-->\n<script type=\"text/javascript\">[#START_SCRIPTS#]\n</script><!--ET_END START_SCRIPTS-->" .
            "<!--ET_BEGIN PAGE_CONTENT-->[#PAGE_CONTENT#]<!--ET_END PAGE_CONTENT-->" .
            "<!--ET_BEGIN JQRUN-->\n<script type=\"text/javascript\">[#JQRUN#]\n</script><!--ET_END JQRUN-->" .
            "<!--ET_BEGIN SCRIPTS-->\n<script type=\"text/javascript\">[#SCRIPTS#]\n</script><!--ET_END SCRIPTS-->" .
            "<!--ET_BEGIN BROWSER_DETECT-->[#BROWSER_DETECT#]<!--ET_END BROWSER_DETECT-->" .
            "[#page_Html::addJs#]" .
            "\n</body>" .
            "\n</html>");
    }
    
    
    /**
     * Прихваща събитието 'output' на ЕТ, за да добави стиловете и javascripts
     */
    static function on_Output(&$invoker)
    {
        // Добавяне на хедърите
        $headers = $invoker->getArray('HTTP_HEADER');
        if (!empty($headers)) {
            foreach($headers as $hdr) {
                if($hdr{0} == '-') {
                    header_remove(substr($hdr, 1));
                } else {
                    header($hdr, TRUE);
                }
            }
        }

        // Добавяне на файловете
        $files = (object) array(
                            'css' => $invoker->getArray('CSS'),
                            'js' => $invoker->getArray('JS'),
                            'invoker' => $invoker
                          );
            
        $inst = cls::get(get_called_class());

        $inst->appendFiles($files);
    }
    
    
    /**
     * Интерфейсен метод
     * 
     * @see core_page_WrapperIntf
     */
    function prepare()
    {
        
    }
    
    
    /**
     * Добавя JS
     * 
     * @return core_ET
     */
    static function addJs()
    {
        $tpl = new ET();
        
        // Показване на статус съобщения
        static::subscribeStatus($tpl);
        
        // Записване на избрания текст с JS
        static::saveSelTextJs($tpl);
        
        // Вземане на времето на бездействие в съответния таб
        static::idleTimerJs($tpl);

        $tpl->push('context/'.  context_Setup::get('VERSION') . '/contextMenu.css', "CSS");
        $tpl->push('context/'.  context_Setup::get('VERSION') . '/contextMenu.js', "JS");
        
        jquery_Jquery::run($tpl, "getContextMenuFromAjax();", TRUE);
        jquery_Jquery::runAfterAjax($tpl, 'getContextMenuFromAjax');
        
        jquery_Jquery::run($tpl, 'scrollLongListTable();');
        jquery_Jquery::run($tpl, 'editCopiedTextBeforePaste();');
        jquery_Jquery::run($tpl, 'smartCenter();');
        jquery_Jquery::run($tpl, 'showTooltip();');
        jquery_Jquery::run($tpl, 'makeTooltipFromTitle();');
        
        $url = json_encode(toUrl(array('bgerp_A', 'wp'), 'local'));
        $tpl->appendOnce("var wpUrl = {$url};", 'SCRIPTS');
        
        if (Mode::is('screenMode', 'narrow')) {
            jquery_Jquery::run($tpl, "detectScrollAndWp();");
        }
        
        return $tpl;
    }
    
    
    /**
     * Показва статус съобщението
     * 
     * @param core_ET $tpl
     */
    static function subscribeStatus(&$tpl)
    {
        // Ако не е сетнато времето на извикване
        if (!$hitTime = Mode::get('hitTime')) {
            
            // Използваме текущото
            $hitTime = dt::mysql2timestamp();
        }
        
        // Добавяме в JS timestamp на извикване на страницата
        $tpl->appendOnce("var hitTime = {$hitTime};", 'SCRIPTS');
        
        // Извикваме показването на статусите
        $tpl->append(core_Statuses::subscribe(), 'STATUSES');
    }
    
    
    /**
     * Маркиране на избрания текст с JS
     * 
     * @param core_ET $tpl
     */
    static function saveSelTextJs(&$tpl)
    {
        // Скрипт, за вземане на инстанция на efae        
        jquery_Jquery::run($tpl, "getEO().saveSelText();", TRUE);
    }
    
    
    /**
     * Функция за вземане на времето на бездействие в съответния таб
     * 
     * @param core_ET $tpl
     */
    static function idleTimerJs(&$tpl)
    {
        jquery_Jquery::run($tpl, "\n getEO().runIdleTimer();", TRUE);
    }
    
    
    /**
     * Ако няма кой да прихване извикването на функцията
     * 
     * @param array $css
     */
    function appendFiles_($files)
    { 
        // Дали връзките да са абсолютни
        $absolute = (boolean)(Mode::is('text', 'xhtml'));
      
        if(is_array($files->css)) {
            foreach($files->css as $file) {
                $file = $this->getFileForAppend($file, $absolute);
                
                $files->invoker->appendOnce("\n@import url(\"{$file}\");", "STYLE_IMPORT", TRUE); 
            }
        }
        
        if(is_array($files->js)) {
            foreach($files->js as $file) {
                $file = $this->getFileForAppend($file, $absolute);
                
                $files->invoker->appendOnce("\n<script type=\"text/javascript\" src=\"{$file}\"></script>", "HEAD", TRUE);
            }
        } 
    }
    
    
    /**
     * Връща файла за добавя. Ако е от системата минава през sbf.
     * Ако е външен линк, не го променя
     * 
     * @param string $filePath
     * @param NULL|boolean $absolute
     * 
     * @return string
     */
    public static function getFileForAppend($filePath, $absolute = NULL)
    {
        if(preg_match('#^[^/]*//#', $filePath)) return $filePath;
        
        if (!isset($absolute)) {
            $absolute = (boolean)(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'));
        }
        
        $filePath = sbf($filePath, '', $absolute);
        
        return $filePath;
    }
    
    
    /**
     * Ако няма кой да прихване извикването на функцията
     * 
     * @param array $css
     */
    function prepareJsFiles_($js)
    {
        return $js;
    }
}
