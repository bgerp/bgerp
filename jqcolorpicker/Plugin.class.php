<?php



/**
 * Клас 'jqdatepick_Plugin' - избор на дата
 *
 *
 * @category  vendors
 * @package   jqcolorpicker
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class jqcolorpicker_Plugin extends core_Plugin {
    
    
    /**
     * Изпълнява се преди рендирането на input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {

        $options = $invoker->options;
        
        if(!count($options)) {
            $options = $this->getDefaultOpt();
        }
        
        if($value) {
            $cObj = new color_Object($value);
            $hCol = $cObj->getHex();
            $options[substr($hCol, 1)] = $value;
            $selected = substr($hCol, 1);
        }
 
        $tpl = ht::createSelect($name, $options, $selected, $attr);
        
        $tpl->push("jqcolorpicker/2.0/jquery.colourPicker.css", "CSS");
        $tpl->push("jqcolorpicker/2.0/jquery.colourPicker.js", "JS");

        // custom стилове за плъгина
        $tpl->push("jqcolorpicker/css/jqcolorpicker-custom.css", "CSS");


        jquery_Jquery::run($tpl,
            
            "\n$('#" . $attr['id'] . "').colourPicker({" .
            "\n    ico:     '" . sbf('jqcolorpicker/2.0/jquery.colourPicker.gif', '') . "'," .
            "\n    title:    false" .
            "\n});"
        
        );
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDefaultOpt()
    {
        $cs = array('00', '33', '66', '99', 'cc', 'ff');
        
        for($i = 0; $i<6; $i++) {
            for($j = 0; $j<6; $j++) {
                for($k = 0; $k<6; $k++) {
                    $c = $cs[$i] . $cs[$j] . $cs[$k];
                    $opt[$c] = "#" . $c;
                }
            }
        }
        
        return $opt;
    }
}
