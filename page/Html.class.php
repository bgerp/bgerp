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
    function page_Html() {
        
        $this->core_ET(
            //"<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n \"http://www.w3.org/TR/html4/loose.dtd\">" .
            
            (Mode::is('screenMode', 'narrow') ?
                "<!DOCTYPE html PUBLIC \"-//WAPFORUM//DTD XHTML Mobile 1.0//EN\" \"http://www.wapforum.org/DTD/xhtml-mobile10.dtd\">" .
                "<html xmlns=\"http://www.w3.org/1999/xhtml\">" :
                
                "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n \"http://www.w3.org/TR/html4/strict.dtd\">" .
                "<html>") .
            
            "\n<head>" .
            "\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=[#ENCODING#]\">" .
            "<!--ET_BEGIN META_DESCRIPTION-->\n<meta name=\"description\" content=\"[#META_DESCRIPTION#]\"><!--ET_END META_DESCRIPTION-->" .
            "<!--ET_BEGIN META_KEYWORDS-->\n<meta name=\"keywords\" content=\"[#META_KEYWORDS#]\"><!--ET_END META_KEYWORDS-->" .
            "<!--ET_BEGIN PAGE_TITLE-->\n<title>[#PAGE_TITLE#]</title><!--ET_END PAGE_TITLE-->" .
            "<!--ET_BEGIN STYLE_IMPORT-->\n<style type=\"text/css\">[#STYLE_IMPORT#]\n</style><!--ET_END STYLE_IMPORT-->" .
            "<!--ET_BEGIN STYLES-->\n<style type=\"text/css\">[#STYLES#]\n</style><!--ET_END STYLES-->" .
            "<!--ET_BEGIN HEAD-->[#HEAD#]<!--ET_END HEAD-->" .
            "<!--ET_BEGIN SCRIPTS-->\n<script type=\"text/javascript\">[#SCRIPTS#]\n</script><!--ET_END SCRIPTS-->" .
            "\n</head>" .
            "\n<body<!--ET_BEGIN ON_LOAD--> onload=\"[#ON_LOAD#]\"<!--ET_END ON_LOAD-->" .
            (Mode::is('screenMode', 'narrow') ? " class=\"narrow\"" : "") . ">" .
            "<!--ET_BEGIN PAGE_CONTENT-->[#PAGE_CONTENT#]<!--ET_END PAGE_CONTENT-->" .
            "<!--ET_BEGIN JQRUN-->\n<script type=\"text/javascript\">[#JQRUN#]\n</script><!--ET_END JQRUN-->" .
            "\n</body>" .
            "\n</html>");
    }
    
    
    /**
     * Прихваща събитието 'output' на ЕТ, за да добави стиловете и javascripts
     */
    static function on_Output(&$invoker)
    {
        $css = $invoker->getArray('CSS');
        
        if(count($css)) {
            foreach($css as $file) {
                if(!strpos($file, '://')) {
                    $file = sbf($file, '');
                }
                
                $invoker->appendOnce("\n@import url(\"{$file}\");", "STYLE_IMPORT", TRUE);
            }
        }
        
        $js = $invoker->getArray('JS');
        
        if(count($js)) {
            foreach($js as $file) {
                if(!strpos($file, '://')) {
                    $file = sbf($file, '');
                }
                $invoker->appendOnce("\n<script type=\"text/javascript\" src=\"{$file}\"></script>", "HEAD", TRUE);
            }
        }
    }
}