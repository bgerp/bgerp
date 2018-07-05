<?php



/**
 * Клас 'ckeditor_CKeditor' -
 *
 *
 * @category  vendors
 * @package   ckeditor
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
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
    public function renderHtml($tpl, $attr = array(), $options = array())
    {
        $id = $attr['id'];
        
        if (!$tpl) {
            $tpl = ht::createElement('textarea', $attr, $value, true);
        }
        
        if ($attr['style']) {
            $tpl->prepend("<div style=\"{$attr['style']}\">");
            $tpl->append('</div');
        }
        
        $tpl->appendOnce(
            '<script type="text/javascript" src=' . sbf('ckeditor/ckeditor.js') . "></script>\n",
            'HEAD'
        
        );
        
        // $tpl->appendOnce(
        // "<script type=\"text/javascript\" src=" . sbf("ckeditor/_samples/sample.js") . "></script>\n",
        //  'HEAD');
        
        //  $tpl->appendOnce(
        // "<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("ckeditor/_samples/sample.css") . ">\n",
        // 'HEAD');
        
        setIfNot($options['language'], $attr['lang'], core_LG::getCurrent());
        
        $init = json_encode($options);
        
        $tpl->append("
        <script>
            CKEDITOR.replace( '{$id}', {$init} );
        </script>\n");
        
        if (isDebug()) {
            $tpl->prepend("\n<!-- Начало на CKEDITOR редактора за полето '{$id}' -->\n");
            $tpl->append("<!-- Край на CKEDITOR редактора за полето '{$id}' -->\n");
        }
        
        return $tpl;
    }
}
