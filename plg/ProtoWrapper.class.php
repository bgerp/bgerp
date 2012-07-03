<?php



/**
 * Клас 'bgerp_ProtoWrapper' - Прототип на wrapper за модулите на bgERP
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
    var $title;


    /**
     * Страница от менюто
     */
    var $pageMenu;


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
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    { 
        $this->description();
 
        if($this->pageMenu && !Mode::get('pageMenu')) {
            Mode::set('pageMenu', $this->pageMenu);
        }
        
        // Генерираме титлата на страницата         
        $tpl->prepend(tr($invoker->title) . ' « ' . tr($this->title) . ' « ', 'PAGE_TITLE');
        
        // Проверяваме дали текущия таб не е изрично зададен
        if ($isCurrentTabSet = $invoker->currentTab && isset($this->tabs[$invoker->currentTab])) {
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
                $act = strtolower($rec->url['Act']);
                try {
                    if($act == 'list' || $act == 'default' || empty($act)) {
                        $tabCtr = cls::get($rec->url['Ctr']);
                        if($tabCtr instanceof core_Manager) {
                            $rec->haveRight = $tabCtr->haveRightFor('list');
                        }
                    }
                } catch (core_Exception_Expect $expect) {}
            }

        }
        
        // Създаваме рендер на табове
        $tabs = CLS::get('core_Tabs');

 
        foreach($this->tabs as $name => $rec) {
           
            if($rec->haveRight) {
                 $tabs->TAB($name, $name, $rec->url);
            } elseif($name == $currentTab) {
                 $tabs->TAB($name, $name, array());
            }
        }
        
        $tpl = $tabs->renderHtml($tpl, $currentTab);

    }
}