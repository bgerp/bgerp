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
	 * Максимален брой сиволи в заглавията на табовете в широк режим
	 * Ако този брой е надвишен, генерира се комбо-бокс
	 */
	var $maxTabsWide = 140;

	/**
	 * Максимален брой сиволи в заглавията на табовете в широк режим
	 * Ако този брой е надвишен - генерира се комбо-бокс
	 */
	var $maxTabsNarrow = 30;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->htmlClass, 'tab-control');
    }
    
    
    /**
     * Задаване на нов таб
     */
    function TAB($tab, $caption = NULL, $url = NULL, $class = NULL)
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
                $url = FALSE;
            }
        }
        
        $this->tabs[$tab] = $url;
        $this->captions[$tab] = $caption ? $caption : $tab;
        $this->classes[$tab] = $class;
    }
    
    
    /**
     * Рендира табове-те
     */
    function renderHtml_($body, $selectedTab = NULL)
    {
		// Изчисляване сумата от символите на всички табове
		foreach($this->captions as $tab => $caption) {
			$sumLen += mb_strlen(strip_tags(trim($caption))) + 1;
		}

		if(Mode::is('screenMode', 'narrow')) {
			$isOptionList = $this->maxTabsNarrow < $sumLen;
		} else {
			$isOptionList = $this->maxTabsWide < $sumLen;
		}


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

            $tabClass = $this->classes[$tab];
            
            if ($isOptionList) {
                if(!$url) continue;
                $options[$url] = $title;
            } else {
                if ($url) {
                    $head .= "<div onclick='document.location=\"{$url}\"' style='cursor:pointer;' class='tab {$selected}'>";
                    $head .= "<a href='{$url}' class='tab-title {$tabClass}'>{$title}</a>";
                } else {
                    $head .= "<div class='tab {$selected}'>";
                    $head .= "<span class='tab-title  {$tabClass}'>{$title}</span>";
                }
                
                $head .= "</div>\n";
            }
        }
        
        if ($isOptionList) {
            $head = new ET("<div class='tab selected'>[#1#]</div>\n", ht::createSelectMenu($options, $selectedUrl, FALSE, array('class' => "tab-control")));
        }
        
        $html = "<div class='tab-control {$this->htmlClass}'>\n";
        $html .= "<div class='tab-row'>\n";
        $html .= "[#1#]\n";
        $html .= "</div>\n";
        $html .= "<div class=\"tab-page clearfix21\" id='{$this->htmlId}'>[#2#]</div>\n";
        $html .= "</div>\n";
        
        $tabsTpl = new ET($html, $head, $body);
        
        return $tabsTpl;
    }
}