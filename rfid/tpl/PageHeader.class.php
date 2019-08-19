<?php


/**
 * Клас 'tpl_PageHeader' -
 *
 *
 * @category  bgerp
 * @package   rfid
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class rfid_tpl_PageHeader extends core_ET
{
    /**
     * @todo Чака за документация...
     */
    public function __construct()
    {
        $this->header = new ET('
                    <IMG style="float:right;padding-right:10px;" SRC=' . sbf('img/sourcingbg.png') . ' WIDTH="278" HEIGHT="64" ID="logo" align="absmiddle" ALT=""> 
                    <div class="menuRow" >[#MENU_ROW#]</div>');
        
        $this->addMenuItem('Четци', 'MENU_ROW', array('Readers'));
        $this->addMenuItem('Карти', 'MENU_ROW', array('Cards'));
        $this->addMenuItem('Събития', 'MENU_ROW', array('Events'));
        $this->addMenuItem('About', 'MENU_ROW', 'About');
        
        if (haveRole('admin')) {
            $this->addMenuItem('Система', 'MENU_ROW', 'core_Users');
        }
        
        parent::__construct($this->header);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function addMenuItem($title, $row, $url, $rights = 'visitor')
    {
        static $noFirst = array();
        
        if (!Mode::is('screenMode', 'narrow')) {
            if ($noFirst[$row]) {
                $this->header->append('  ', $row);
            } else {
                $this->header->append(' ', $row);
            }
            
            $bullet = '';
        } else {
            if ($noFirst[$row]) {
                $this->header->append("\n", $row);
            } else {
                $this->header->append("\n", $row);
            }
            
            $bullet = '» ';
        }
        
        $noFirst[$row] = true;
        
        if (Mode::get('pageMenu') == $title) {
            $attr = array('class' => 'menuItem selected');
        } else {
            $attr = array('class' => 'menuItem');
        }
        
        if (is_string($url)) {
            $url = array($url);
        }
        
        if (Users::haveRole($rights)) {
            $this->header->append(HT::getLink($bullet . tr($title), $url, false, $attr), $row);
        } else {
            $this->header->append($bullet . tr($title), $row);
        }
        
        $this->header->append('', $row);
    }
}
