<?php

/**
 * Клас 'core_Tabs' - Вюър за табове
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Tabs extends core_BaseClass
{
    
    
    /**
     *
     */
    function core_Tabs()
    {
        $this->description();
    }
    
    
    /**
     * ()
     */
    function description()
    {
    }
    
    
    /**
     *  Инициализиране на обекта
     */
    function init($params)
    {
        parent::init($params);
        
        setIfNot($this->htmlClass, 'tab-control');
    }
    
    
    /**
     * -
     */
    function TAB($tab, $caption = NULL, $url = NULL)
    {
        if ($url === NULL) {
            if (!$tab) {
                $url = '';
            } else {
                $url = toUrl(array($tab));
            }
        } elseif ($url) {
            $url = toUrl($url);
        }
        $this->tabs[$tab] = $url;
        $this->captions[$tab] = $caption ? $caption : $tab;
    }
    
    
    /**
     *
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
            $tabsTpl = new ET("\n<div class='tab-page clearfix21'>\n");
            $tabsTpl->append(ht::createSelectMenu($options, $selectedUrl, FALSE, array('class' => "tab-control {$this->htmlClass}")));
            $tabsTpl->append("<div>\n");
            $tabsTpl->append($body);
            $tabsTpl->append("</div></div>\n");
        } else {
            $html = "<div class='tab-control {$this->htmlClass}'>\n";
            $html .= "<div class='tab-row'>\n";
            $html .= "[#1#]\n";
            $html .= "</div>\n";
            $html .= "<div class=\"tab-page clearfix21\">[#2#]</div>\n";
            $html .= "</div>\n";
            
            $tabsTpl = new ET($html, $head, $body);
        }
        
        return $tabsTpl;
    }
}