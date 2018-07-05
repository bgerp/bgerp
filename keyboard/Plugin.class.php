<?php




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
class keyboard_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се преди рендирането на HTML input
     */
    public function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr, $options = array())
    {
        $conf = core_Packs::getConfig('keyboard');
        
        if ($this->doNotUse($invoker)) {
            return;
        }
        
        if (strpos($attr['ondblclick'], 'showKeyboard') === false) {
            $attr['ondblclick'] .= '; showKeyboard(this, event.clientX);';
        }
    }
    
    
    /**
     * Извиква се след рендирането на HTML input
     */
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr, $options = array())
    {
        $conf = core_Packs::getConfig('keyboard');
        
        if ($this->doNotUse($invoker)) {
            return;
        }
        
        $tpl->push("keyboard/{$conf->VKI_version}/keyboard.js", 'JS');
        $tpl->push("keyboard/{$conf->VKI_version}/keyboard.css", 'CSS');
        
        if (cls::isSubclass($invoker, 'type_Richtext')) {
            $tpl->append("<a class=rtbutton1 title='Клавиатура' onclick=\"showKeyboard( document.getElementById('{$attr[id]}'))\"><img src=" . sbf('keyboard/keyboard.png') . ' height=15 width=28 alt=""></a>', 'LEFT_TOOLBAR');
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function doNotUse($invoker)
    {
        //
        if (Mode::is('screenMode', 'narrow')) {
            return true;
        }
        
        //      SELECT
        if (strtolower(get_class($invoker)) == 'type_enum') {
            return true;
        }
    }
}
