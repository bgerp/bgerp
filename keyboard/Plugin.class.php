<?php



/**
 * @todo Чака за документация...
 */
defIfNot('VKI_version', '1.28');


/**
 * Клас 'keyboard_Plugin' -
 *
 *
 * @category  vendors
 * @package   keyboard
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class keyboard_Plugin extends core_Plugin {
    
    
    /**
     * Извиква се преди рендирането на HTML input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr, $options = array())
    {
        if($this->doNotUse($invoker)) return;
        
        if(VKI_version == '1.28') {
            if(strpos($attr['ondblclick'], 'showKeyboard') === FALSE) {
                $attr['ondblclick'] .= "; showKeyboard(this, event.clientX);";
            }
        }
    }
    
    
    /**
     * Извиква се след рендирането на HTML input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr, $options = array())
    {
        if($this->doNotUse($invoker)) return;
        
        if(VKI_version == '1.28') {
            $tpl->push("keyboard/1.28/keyboard.js", 'JS');
            $tpl->push("keyboard/1.28/keyboard.css", 'CSS');
        } else {
            $tpl->push("keyboard/" . VKI_version . "/keyboard.js", 'JS');
        }
        
        if(cls::isSubclass($invoker, 'type_Richtext')) {
            $tpl->append("<a class=rtbutton1 title='Клавиатура' onclick=\"showKeyboard( document.getElementById('{$attr[id]}'))\"><img src=" . sbf('keyboard/keyboard.png') . " height=15 width=28 border=0 align=top></a>", 'LEFT_TOOLBAR');
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function doNotUse($invoker)
    {
        //      
        if(Mode::is('screenMode', 'narrow')) return TRUE;
        
        //      SELECT
        if(strtolower(get_class($invoker)) == 'type_enum') return TRUE;
    }
}