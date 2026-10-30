<?php


/**
 * Клас 'keyboard_Plugin' -
 *
 *
 * @category  vendors
 * @package   keyboard
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
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
        
        if (strpos(($attr['ondblclick'] ?? ''), 'showKeyboard') === false) {
            $attr['ondblclick'] ??= '';
            // showKeyboard се дефинира чак на window.load
            $attr['ondblclick'] .= "; if (typeof showKeyboard == 'function') showKeyboard(this, event.clientX);";
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
        
        $version = $conf->VKI_version ?? VKI_version;
        $tpl->push("keyboard/{$version}/keyboard.js", 'JS');
        $tpl->push("keyboard/{$version}/keyboard.css", 'CSS');
        
        if (cls::isSubclass($invoker, 'type_Richtext') && !empty($attr['id'])) {
            $onclick = "event.stopPropagation(); if (typeof showKeyboard == 'function') showKeyboard(document.getElementById(" . json_encode($attr['id']) . "))";
            $icon = ht::createElement('img', array('src' => sbf('keyboard/keyboard.png', ''), 'height' => 15, 'width' => 28, 'alt' => ''));
            $tpl->append(ht::createElement('a', array('class' => 'rtbutton', 'title' => tr('Клавиатура'), 'onclick' => $onclick), $icon), 'TBL_GROUP3');
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
