<?php



/**
 * Клас 'plg_ProtoWrapper' - Прототип на wrapper за модулите на bgERP
 * Показва няколко таба, свързани с различни пакети
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_ProtoWrapper extends core_Plugin
{

    /**
     * Наименованието на групата
     */
    public $title;


    /**
     * Страница от менюто
     */
    var $pageMenu;
    
    
    /**
     * 
     */
    public $tabs = array();
    
    
    /**
     * Добавяне на таб. Изпълянва се в ->description()
     */
    function TAB($url, $name, $roles = 'user')
    {
        if(is_string($url)) {
            $url = array('Ctr' => $url);
        } elseif(is_array($url)) {
            if($url[0]) {
                $url['Ctr'] = $url[0];
            }
            if($url[1]) {
                $url['Act'] = $url[1];
            }
            if($url[2]) {
                $url['id'] = $url[2];
            }
        } else {
            expect(empty($url), $url);
        }
        
        $rec = new stdClass();
        
        $rec->url = $url;
        $rec->roles = $roles;
        $this->tabs[$name] = $rec;
    }


    /**
     * Връща заглавието на HTML страницата
     */
    function getHtmlPageTitle($invoker, $data)
    {
        // Генерираме титлата на страницата
        if(isset($data->pageTitle)) {
            $title = $data->pageTitle;
        } else {
            $title = tr($invoker->title);
            if($this->title) {
                $title .= ' « ' . tr($this->title);
            }
        }

        return $title;
    }


    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl, $blankTpl, $data = NULL)
    {
        $tpl= new ET($tpl);
       
        $this->invoke('beforeDescription');
        $this->description();
        $this->invoke('afterDescription');

        if($this->pageMenu && !Mode::get('pageMenu')) {
            Mode::set('pageMenu', $this->pageMenu);
        }
        
        // Добавяме титлата на страницата
        $tpl->prepend($this->getHtmlPageTitle($invoker, $data) . ' « ', 'PAGE_TITLE');
        
        // Проверяваме дали текущия таб не е изрично зададен
        if ($isCurrentTabSet = $invoker->currentTab  ) {
            $currentTab = $invoker->currentTab;
        }  

        $ctr = cls::getClassName(Request::get('Ctr'));
        $act = Request::get('Act');
        $id  = Request::get('id');
        
        // Масимално добрата оценка за подходящ таб
        $maxScore = 0;

        foreach($this->tabs as $name => $rec) {
            if (!$isCurrentTabSet) {
                // Ако текущия таб не е изрично зададен, опитваме да го определим евристично
                $score = 0;
                if($rec->url['Ctr'] == $ctr && !empty($ctr)) {
                    $score = 1;
                    if(strtolower($rec->url['Act']) == strtolower($act) && !empty($act)) {
                        $score = 2;
                        if($rec->url['id'] == $id && !empty($id)) {
                            $score = 3;
                        }
                    }
                }

                if($score > $maxScore) { 
                    $currentTab = $name; 
                    $maxScore   = $score;
                }
            }

            // Контрол да достъпа до табовете
            if($rec->haveRight = haveRole($rec->roles)) {
                if(isset($rec->url['Act'])) {
                    $act = strtolower($rec->url['Act']);
                }
                try {
                    if($act == 'list' || $act == 'default' || empty($act)) {
                        $tabCtr = cls::get($rec->url['Ctr']);
                        if($tabCtr instanceof core_Manager) {
                            $rec->haveRight = $tabCtr->haveRightFor('list');
                        }
                    }
                } catch (core_Exception_Expect $expect) {
                    // Не се добавя нищо
                }
            }
        }
        
        // Създаваме рендер на табове
        if($this->htmlClass){
        	$tabs = cls::get('core_Tabs', array('htmlClass' => $this->htmlClass));
        } else {
        	$tabs = cls::get('core_Tabs');
        }
        
        $subTabs = array();
 
        $tabs->htmlId = 'packWrapper';
        
        $hint = '';
        $hintBtn = '';
        
        foreach($this->tabs as $name => $rec) {
            
            // Дали ще правим един или два таб контрола?
            list($mainName, $subName) = explode('->', $name);
            
            // Добавяме към главния таб
            if(!$usedNames[$mainName]) {
                if($rec->haveRight) {
                     $tabs->TAB($mainName, $mainName, $rec->url);
                     if($name == $currentTab && (!$subName)) {
                         $this->invoke('afterSetCurrentTab', array($mainName, $rec->url, &$hint, &$hintBtn, $tpl));  
                     }
                } elseif($name == $currentTab) {
                     $tabs->TAB($mainName, $mainName, array());
                }
                $usedNames[$mainName] = TRUE;
            }

            // Добавяме към подчинения таб, ако има нужда
            if($subName) {
                if(!$subTabs[$mainName]) {
                    $subTabs[$mainName] = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
                }
                if($rec->haveRight) {
                     $subTabs[$mainName]->TAB($subName, $subName, $rec->url);
                     if($name == $currentTab) {
                         $this->invoke('afterSetCurrentTab', array($mainName, $rec->url, &$hint, &$hintBtn, $tpl));  
                     }
                } elseif($name == $currentTab) {
                     $subTabs[$mainName]->TAB($subName, $subName, array());
                }
            }
        }
        
        list($currentMainTab, $currentSubTab) = explode('->', $currentTab);
 
        if($subTabs[$currentMainTab]) {
            $tpl = $subTabs[$currentMainTab]->renderHtml($tpl, $currentSubTab, $hint, $hintBtn);
            $tpl = $tabs->renderHtml($tpl, $currentMainTab);
        } else {
            $tpl = $tabs->renderHtml($tpl, $currentMainTab, $hint, $hintBtn);
        }
    }


    /**
     * Заменя първия срещант wrapper на mvc клас с нов
     */
    public static function changeWrapper($mvc, $newWrapper)
    {
        foreach($mvc->_plugins as $key => $plg) {
            if(is_a($plg, 'plg_ProtoWrapper')) {
                $mvc->_plugins[$key] = cls::get($newWrapper);
            }
        }
    }
}
