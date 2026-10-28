<?php


/**
 * Клас 'tinymce_Plugin' - добавя редактов към HTML инпут полета
 *
 * @category  bgerp
 * @package   tinymce
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://www.tinymce.com/
 */
class tinymce_Plugin extends core_Plugin
{
    /**
     * Изпълнява се преди рендирането на input
     *
     * @param type_Key          $invoker
     * @param core_ET           $tpl
     * @param string            $name
     * @param string|array|NULL $value
     * @param array             $attr
     */
    public static function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     * Извиква се след рендирането на HTML input
     */
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr, $options = array())
    {
        $invoker->params['tinyEditor'] ??= 'yes';
        
        if ($invoker->params['tinyEditor'] == 'no') {
            
            return ;
        }
        
        if (tinymce_Setup::get('VERSION') === '8.10.1') {
            $this->renderModernInput($invoker, $tpl, $attr);

            return;
        }

        $tinyPlugins = '';
        if (!empty($invoker->params['tinyPlugins'])) {
            $tinyPlugins = $invoker->params['tinyPlugins'];
        }
        
        $tinyToolbars = '';
        if (!empty($invoker->params['tinyToolbars'])) {
            $tinyToolbars = $invoker->params['tinyToolbars'] . ' | ';
            
            if (!$tinyPlugins) {
                $tinyPlugins = $invoker->params['tinyToolbars'];
            }
        }
        
        $fs = '';
        if (!empty($invoker->params['tinyFullScreen'])) {
            $fs = "setup: function(editor) {editor.on('init', function(e) {editor.execCommand('mceFullScreen');});},";
        }
        
        if (!empty($invoker->params['tinySaveCallback'])) {
            $fs .= "save_onsavecallback: function(editor){
                    
                    var content = editor.getContent();
                                        
                    getEfae().process({url: '{$invoker->params['tinySaveCallback']}'}, {data: content});
                                    
                },";
        }
        
        $tpl->push('tinymce/4.7.13/tinymce.min.js', 'JS');
        
        if (core_Lg::getCurrent() == 'bg') {
            $locale = 'bg_BG';
        } else {
            $locale = 'en_GB';
        }
        jquery_Jquery::run($tpl, "tinymce.init({ {$fs} selector: '#{$attr['id']}', language: '{$locale}', relative_urls: false, remove_script_host: false, convert_urls: true, branding: false,  plugins : 'advlist autolink link image lists charmap textcolor codemirror {$tinyPlugins}', toolbar: '{$tinyToolbars}redo undo | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright  alignjustify  | numlist bullist outdent indent  | code', removed_menuitems: 'newdocument',
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
        
        });", true);
    }

    /**
     * Зарежда TinyMCE 8 със съществуващите параметри на HTML полето.
     */
    protected function renderModernInput($invoker, $tpl, $attr)
    {
        $path = 'tinymce/8.10.1';
        $locale = core_Lg::getCurrent() === 'bg' ? 'bg-BG' : 'en';
        $tpl->push($path . '/tinymce.min.js', 'JS');
        $cmPath = 'tinymce/codemirror-5.65.21';
        foreach (array('lib/codemirror', 'addon/dialog/dialog') as $file) {
            $tpl->push($cmPath . '/' . $file . '.css', 'CSS');
        }
        foreach (array('lib/codemirror', 'mode/xml/xml', 'mode/javascript/javascript', 'mode/css/css',
            'mode/htmlmixed/htmlmixed', 'mode/clike/clike', 'mode/php/php', 'addon/edit/matchbrackets',
            'addon/selection/active-line', 'addon/dialog/dialog', 'addon/search/searchcursor', 'addon/search/search') as $file) {
            $tpl->push($cmPath . '/' . $file . '.js', 'JS');
        }
        $tpl->push('tinymce/js/adapter.js', 'JS');
        $config = array(
            'id' => $attr['id'] ?? '',
            'base_url' => dirname(sbf($path . '/tinymce.min.js', '')),
            'language' => $locale,
            'plugins' => $invoker->params['tinyPlugins'] ?? '',
            'toolbar' => $invoker->params['tinyToolbars'] ?? '',
            'fullscreen' => !empty($invoker->params['tinyFullScreen']),
            'saveCallback' => $invoker->params['tinySaveCallback'] ?? '',
        );
        if ($locale !== 'en') {
            $config['language_url'] = sbf($path . '/langs/' . $locale . '.js', '');
        }
        $json = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        jquery_Jquery::run($tpl, "bgerpTinyMceRun({$json});", true);
    }

}
