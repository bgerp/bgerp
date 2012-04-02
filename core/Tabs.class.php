<?php



/**
 * Клас 'core_Tabs' - Изглед за табове
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Tabs extends core_BaseClass
{
    
    
    /**
     * @todo Чака за документация...
     */
    function core_Tabs()
    {
        $this->description();
    }
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    }
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->htmlClass, 'tab-control');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function TAB($tab, $caption = NULL, $url = NULL)
    {
        if ($url === NULL) {
            if (!$tab) {
                $url = '';
            } else {
                $url = toUrl(array($tab));
            }
        } elseif (is_array($url)) {
            if(count($url)) {
                $url = toUrl($url);
            } else {
                $url == FALSE;
            }
        }
        
        $this->tabs[$tab] = $url;
        $this->captions[$tab] = $caption ? $caption : $tab;
    }
    
    
    /**
     * Рендира табове-те
     */
    function renderHtml_($body, $selectedTab = NULL)
    {
        //         
        if (!count($this->tabs)) {
            return $body;
        }
        
        //      ,       
        if (!$selectedTab) {
            $selectedTab = Request::get('selectedTab');
        }
        
        //  ,     
        if (!$selectedTab) {
            $selectedTab = key($this->tabs);
        }
        
        foreach ($this->tabs as $tab => $url) {
            
            if ($tab == $selectedTab) {
                $selectedUrl = $url;
                $selected = 'selected';
            } else {
                $selected = '';
            }
            
            $title = tr($this->captions[$tab]);
            
            if (Mode::is('screenMode', 'narrow')) {
                if(!$url) continue;
                $options[$url] = $title;
            } else {
                $head .= "<div class='tab {$selected}'>";
                
                if ($url) {
                    $head .= "<a href='{$url}'><B>{$title}</B></a>";
                } else {
                    $head .= "<b>{$title}</b>";
                }
                
                $head .= "</div>\n";
            }
        }
        
        if (Mode::is('screenMode', 'narrow')) {
            $head = new ET("<div class='tab selected'>[#1#]</div>\n", ht::createSelectMenu($options, $selectedUrl, FALSE, array('class' => "tab-control")));
        }
        
        $html = "<div class='tab-control {$this->htmlClass}'>\n";
        $html .= "<div class='tab-row'>\n";
        $html .= "[#1#]\n";
        $html .= "</div>\n";
        $html .= "<div class=\"tab-page clearfix21\">[#2#]</div>\n";
        $html .= "</div>\n";
        
        $tabsTpl = new ET($html, $head, $body);
        
        return $tabsTpl;
    }
}