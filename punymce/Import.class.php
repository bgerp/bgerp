<?php



/**
 * Клас 'punymce_PunyMCE' -
 *
 *
 * @category  vendors
 * @package   punymce
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class punymce_Import {
    
    
    /**
     * Return the HTML code required to run PunyMCE.
     *
     * @return string
     */
    function renderHtml_($tpl, $attr = array(), $options = array())
    {
        
        ht::setUniqId($attr);
        $id = $attr['id'];
        
        if(!$tpl) {
            $tpl = ht::createElement('textarea', $attr, $value, TRUE);
        }
        
        $cfg = $attr['#PunyMCE'];
        
        switch($attr['#config']) {
            case 'simple1' :
                $config = array();
                break;
            default :
            $config = array (
                'toolbar' => 'bold,italic,underline,strike,increasefontsize,decreasefontsize,ul,ol,indent,outdent,left,center,right,style,textcolor,removeformat,link,unlink,image,emoticons,editsource',
                'plugins' => 'Paste,Image,Emoticons,Link,ForceBlocks,Protect,TextColor,EditSource,Safari2x',
                'min_width' => 400,
                'entities' => 'numeric'
            );
        }
        
        setIfNot($config, $cfg);
        
        $config['id'] = $id;
        
        $init = json_encode($config);
        
        $tpl->appendOnce("<script type=\"text/javascript\" src=" . sbf("punymce/js/punymce/puny_mce_full_new.js") . "></script>\n", 'HEAD');
        
        $tpl->append("
            <script type=\"text/javascript\">
                var PunyMCE_editor_{$id} = new punymce.Editor({$init});
            </script>    
        ");
        
        if(isDebug()) {
            $tpl->prepend("\n<!-- Начало на PunyMCE редактора за полето '{$id}' -->\n");
            $tpl->append("<!-- Край на PunyMCE редактора за полето '{$id}' -->\n");
        }
        
        return $tpl;
    }
}
