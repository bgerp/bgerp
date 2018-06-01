<?php




/**
 * Клас 'tinymce_Plugin' - добавя редактов към HTML инпут полета
 *
 * @category  bgerp
 * @package   tinymce
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see       https://www.tinymce.com/
 */
class tinymce_Plugin extends core_Plugin
{
    
    /**
     * Изпълнява се преди рендирането на input
     * 
     * @param type_Key $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    public static function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
    }


    /**
     * Извиква се след рендирането на HTML input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr, $options = array())
    {
        $tinyPlugins = '';
        if ($invoker->params['tinyPlugins']) {
            $tinyPlugins = $invoker->params['tinyPlugins'];
        }
        
        $tinyToolbars = '';
        if ($invoker->params['tinyToolbars']) {
            $tinyToolbars = $invoker->params['tinyToolbars'] . ' | ';
            
            if (!$tinyPlugins) {
                $tinyPlugins = $invoker->params['tinyToolbars'];
            }
        }
        
		$fs = '';
        if ($invoker->params['tinyFullScreen']) {
            $fs = "setup: function(editor) {editor.on('init', function(e) {editor.execCommand('mceFullScreen');});},";
        }
        
        
        $tpl->push("tinymce/4.7.13/tinymce.min.js", 'JS');
        
        if(core_Lg::getCurrent() == 'bg') {
            $locale = 'bg_BG';
        } else {
            $locale = 'en_GB';
        }
        jquery_Jquery::run($tpl, "tinymce.init({ {$fs} selector: '#{$attr['id']}', language: '{$locale}', branding: false,  plugins : 'advlist autolink link image lists charmap textcolor codemirror {$tinyPlugins}', toolbar: '{$tinyToolbars}redo undo | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright  alignjustify  | numlist bullist outdent indent  | code', removed_menuitems: 'newdocument',
            codemirror: {
                indentOnInit: true, 
                fullscreen: true,  
                path: 'CodeMirror', 
                config: { 
                   mode: 'text/html',
                },
                width: 800,
                height: 600,
                saveCursorPosition: true,
                jsFiles: [ 
                   'mode/clike/clike.js',
                   'mode/php/php.js'
                ]
            }
        
        });", TRUE);
  
   
    }
}