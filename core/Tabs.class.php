<?php


/**
 * Клас 'core_Tabs' - Изглед за табове
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Tabs extends core_BaseClass
{
    /**
     * Масив с табове
     */
    protected $tabs = array();
    
    
    /**
     * Масив с табове
     */
    protected $urlParam = 'Tab';
    
    
    /**
     * Дали да се показва винаги първия таб ако няма избран
     */
    public $showFirstIfNotSelected = false;
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->htmlClass, 'tab-control');
    }
    
    
    /**
     * Задаване на нов таб
     */
    public function TAB($tab, $caption = null, $url = null, $class = null)
    {
        if ($url === null) {
            if (!$tab) {
                $url = '';
            } else {
                $url = toUrl(array($tab));
            }
        } elseif (is_array($url)) {
            if (count($url)) {
                $url = toUrl($url);
            } else {
                $url = false;
            }
        }
        
        $this->tabs[$tab] = $url;
        $this->captions[$tab] = $caption ? $caption : $tab;
        $this->classes[$tab] = $class;
    }
    
    
    /**
     * Рендира табове-те
     */
    public function renderHtml_($body, $selectedTab = null, $hint = null, $hintBtn = null)
    {
        // Ако няма конфигурирани табове, рендираме само тялото
        if (!count($this->tabs)) {
            
            return $body;
        }
        
        // Изчисляване сумата от символите на всички табове
        foreach ($this->captions as $tab => $caption) {
            $sumLen += mb_strlen(strip_tags(trim($caption))) + 1;
        }
        
        //      ,
        if (!$selectedTab) {
            $selectedTab = Request::get('selectedTab');
        }
    
        if (!$selectedTab) {
            $selectedTab = $this->getSelected();
        }

      
        
        //  ,
        if (!$selectedTab) {
            $selectedTab = key($this->tabs);
        }

        if(!$selectedTab && $this->tabGroup) {
            core_Settings::setValues('TABS::' . $this->tabGroup, array('DEFAULT_TABS' => $selectedTab));

        }

        if (!function_exists('getallheaders'))
        {
            function getallheaders()
            {
                $headers = [];
                foreach ($_SERVER as $name => $value)
                {
                    if (substr($name, 0, 5) == 'HTTP_')
                    {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }
        
        $headers = getallheaders();
        
        $head = '';
        
        $isAjax = defined('EF_AJAX_TAB') && $headers['Ajax-Mode'] && !empty($this->htmlId) && $this->htmlId == $headers['Html-Part-Id']; 
        foreach ($this->tabs as $tab => $url) {
            if ($tab == $selectedTab) {
                $selectedUrl = $url;
                $selected = 'selected';
            } else {
                $selected = '';
            }
            
            $title = tr($this->captions[$tab]);
            
            $tabClass = $this->classes[$tab];
        
            if ($url) {
                $url = ht::escapeAttr($url);
                if($this->htmlId && defined('EF_AJAX_TAB')) {
                    $head .= "<div onclick=\"updateTab('{$this->htmlId}', '{$url}'); return false;\" style='cursor:pointer;' class='tab {$selected}'>";
                    $head .= "<a onclick=\"return; updateTab('{$this->htmlId}', '{$url}');  preventDefault(); return false;\"  class='tab-title {$tabClass}'>{$title}</a>";
                } else {
                    $head .= "<div onclick=\"openUrl('{$url}', event)\" style='cursor:pointer;' class='tab {$selected}'>";
                    $head .= "<a onclick=\"return openUrl('{$url}', event);\" href='{$url}' class='tab-title {$tabClass}'>{$title}</a>";
                }
                if ($selected) {
                    $head .= $hintBtn;
                }
            } else {
                $head .= "<div class='tab {$selected}'>";
                $head .= "<span class='tab-title  {$tabClass}'>{$title}</span>";
            }
            
            $head .= "</div>\n";
        }
        if ($this->htmlId) {
            $idAttr = " id=\"head-{$this->htmlId}\"";
        }
 
        $html = "<div class='tab-control {$this->htmlClass}'>\n";
 
        $html .= "<div class='tab-row'><div class='row-holder'>\n";
        $html .= "<div {$idAttr}>[#1#]</div>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";
     
        
        if ($this->htmlId) {
            $idAttr = " id=\"{$this->htmlId}\"";
        }
        $html .= "<div class=\"tab-page clearfix21\"{$idAttr}>{$hint}[#2#]</div>\n";
        $html .= "</div>\n";

         
        if ($isAjax) {
            $res = new stdClass();
            $res->head = $head;

            $body = new ET("[#1#]<!--ET_BEGIN JQRUN-->\n<script type=\"text/javascript\">[#JQRUN#]\n[#ON_LOAD#]</script><!--ET_END JQRUN-->" .
            "<!--ET_BEGIN SCRIPTS-->\n<script type=\"text/javascript\">[#SCRIPTS#]\n</script><!--ET_END SCRIPTS-->", $body);
            $res->css = array_keys(array_flip($body->getArray('CSS')));
            foreach ($res->css as $key => $file) {
                $res->css[$key] = sbf($file, '');
            }

            $res->js = array_keys(array_flip($body->getArray('JS')));
        
            foreach ($res->js as $key => $file) {
                $res->js[$key] = sbf($file, '');
            }

            $res->body = $hint . $body;

            core_App::outputJson($res);
        }
        
        $tabsTpl = new ET($html, $head, $body);

        return $tabsTpl;
    }
    
    
    /**
     * Дали в таба има таб с посочено име
     *
     * @param string $name - име на таб, за който проверяваме
     *
     * @return bool - дали е в таба или не
     */
    public function hasTab($name)
    {
        return array_key_exists($name, $this->tabs);
    }
    
    
    /**
     * Кой е избрания таб от урл-то
     */
    public function getSelected()
    {
        $selected = Request::get($this->urlParam);
        
        return $selected;
    }
    
    
    /**
     * Кой е първия добавен таб
     */
    public function getFirstTab()
    {
        // Ако има запазена информация, кой е таба по подразбиране за този потребител, вадим него
        if($this->tabGroup) { 
            $storedTab = core_Settings::fetchPersonalConfig('DEFAULT_TABS', 'TABS::' . $this->tabGroup);
            $selectedTab = array_pop($storedTab);
        }

        if(!$selectedTab) {
            $selectedTab = key($this->tabs);
        }

        return $selectedTab;
    }
    
    
    /**
     * Кой е урл параметъра
     */
    public function getUrlParam()
    {
        return $this->urlParam;
    }
    
    
    /**
     * Кои са зададените табове в обекта
     */
    public function getTabs()
    {
        return $this->tabs;
    }
    
    
    /**
     * Какъв е броя на табовете
     */
    public function count()
    {
        return count($this->tabs);
    }
}
