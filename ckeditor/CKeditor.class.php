<?php


/**
 * Клас 'ckeditor_CKeditor' -
 *
 *
 * @category  vendors
 * @package   ckeditor
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class ckeditor_CKeditor extends core_BaseClass
{
    /**
     * Return the HTML code required to run CKEditor
     *
     * @return string
     */
    public function renderHtml($tpl, $attr = array())
    {
        $id = $attr['id'] ?? '';
        $version = ckeditor_Setup::get('VERSION');
        expect(in_array($version, array('5.0.1', '49.0.1'), true), 'Неподдържана версия на CKEditor');
        if ($version === '49.0.1') {
            return $this->renderModernHtml($tpl, $attr);
        }
//        $tpl = new ET();
//        $tpl->prepend("<style>.ck p {margin:5px;}</style><textarea id=\"{$id}\"></textarea>");
     
          $tpl->appendOnce(
              "\n<script type=\"text/javascript\" src=" .  '/sbf/bgerp/ckeditor/5.0.1/build/ckeditor.js'  . "></script>\n",
              'HEAD'
         );

        //$tpl->appendOnce(
        //    "\n<script src=\"https://cdn.ckeditor.com/ckeditor5/24.0.0/classic/ckeditor.js\"></script>\n",
        //    'HEAD'
        //);
        
        static $a;

        if(!$a) {
            
            $tpl->append("
            <script>
            const editors = {}; 
            function ckeditorRun(id) {
                ClassicEditor
                .create( document.querySelector( '#'+id ), {
                    mention: {
                        feeds: [
                            {
                                marker: '@',
                                feed: [ '@Barney', '@Lily', '@Marshall', '@Robin', '@Ted' ],
                                minimumCharacters: 1
                            }
                        ]
                    },
                    toolbarLocation: 'bottom',
                    toolbar: {
                        items: [
                            'heading',
                            '|',
                            'bold',
                            'italic',
                            'link',
                            'bulletedList',
                            'numberedList',
                            '|',
                            'indent',
                            'outdent',
                            '|',
                            'imageUpload',
                            'blockQuote',
                            'insertTable',
                            'undo',
                            'redo',
                            'highlight',
                            'codeBlock',
                            'code',
                            'fontSize',
                            'fontColor',
                            'fontBackgroundColor',
                            'horizontalLine',
                            'alignment'
                        ]
                    },
                    language: 'bg',
                    image: {
                        toolbar: [ 'imageTextAlternative', '|', 'imageStyle:alignLeft', 'imageStyle:full', 'imageStyle:alignRight' ],
                        styles: [
                 
                'full',
                 
                'alignLeft',

                 'alignRight'
                                ],

                    },
                    table: {
                        contentToolbar: [
                            'tableColumn',
                            'tableRow',
                            'mergeTableCells'
                        ]
                    },
                    licenseKey: '',
                    resize_dir: 'both',
                    
                    
                } )
                .then( editor => {
                    editors[id] = editor;
                } )
                .catch( error => {
                    console.error( 'Oops, something went wrong!' );
                    console.error( 'Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:' );
                    console.warn( 'Build id: pcyqiuwvk4a1-iaq2y33nvkum' );
                    console.error( error );
                } );
            }
        </script>");
            
            if (isDebug()) {
                $tpl->prepend("\n<!-- Начало на CKEDITOR редактора за полето '{$id}' -->\n");
                $tpl->append("<!-- Край на CKEDITOR редактора за полето '{$id}' -->\n");
            }
        
        

            $a = 1;

        }

        jquery_Jquery::run($tpl, 'ckeditorRun(' . json_encode($id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');');
        return $tpl;
    }


    /**
     * Зарежда UMD изданието и превода чрез стандартните статични ресурси.
     *
     * @param core_ET $tpl
     * @param array   $attr
     * @return core_ET
     */
    protected function renderModernHtml($tpl, $attr)
    {
        $path = 'ckeditor/49.0.1';
        $language = $attr['lang'] ?? core_Lg::getCurrent();
        if (!preg_match('/^[a-z]{2}(?:-[a-z]{2,4})?$/D', $language ?? '') || !getFullPath("{$path}/translations/{$language}.umd.js")) {
            $language = 'en';
        }

        $tpl->push("{$path}/ckeditor5.css", 'CSS');
        $tpl->push('ckeditor/css/editor.css', 'CSS');
        $tpl->push("{$path}/ckeditor5.umd.js", 'JS');
        if ($language !== 'en') {
            $tpl->push("{$path}/translations/{$language}.umd.js", 'JS');
        }
        $tpl->push('ckeditor/js/adapter.js', 'JS');
        $options = array('language' => $language, 'readOnly' => !empty($attr['readonly']) || !empty($attr['disabled']));
        $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        jquery_Jquery::run($tpl, 'bgerpCkeditorRun(' . json_encode($attr['id'] ?? '', $flags) . ', ' . json_encode($options, $flags) . ');');

        return $tpl;
    }
}
