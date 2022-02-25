<?php


/**
 * Клас 'ckeditor_CKeditor' -
 *
 *
 * @category  vendors
 * @package   ckeditor
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
        $id = $attr['id'];
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
        
        setIfNot($lg, $attr['lang'], core_LG::getCurrent());
        
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

        jquery_Jquery::run($tpl, "ckeditorRun('{$id}');");
        return $tpl;
    }
}
