<?php



/**
 * Клас 'core_Toolbar' - Изглед за лента с бутони
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
class core_Toolbar extends core_BaseClass
{
    
    
    /**
     * Масив с бутоните на лентата с инструменти
     */
    var $buttons = array();
    
    
    /**
     * Добавя бутон, който прехвърля към хипервръзка
     */
    function addBtn($title, $url, $params = array(), $moreParams = array())
    {
        $btn = new stdClass();
        $btn->url = $url;
        $btn->title = $title;
        $this->add($btn, $params, $moreParams);
    }
    
    
    /**
     * Добавя 'submit' бутон
     */
    function addSbBtn($title, $cmd = 'default', $params = array(), $moreParams = array())
    {
        $btn = new stdClass();
        
        $btn->type = 'submit';
        $btn->title = $title;
        $btn->cmd = $cmd;
        $this->add($btn, $params, $moreParams);
    }
    
    
    /**
     * Добавя бутон, който задейства js функция
     */
    function addFnBtn($title, $function, $params = array(), $moreParams = array())
    {
        $btn->type = 'function';
        $btn->title = $title;
        $btn->fn = $function;
        $this->add($btn, $params, $moreParams);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function add(&$btn, &$params, &$moreParams)
    {
        $params = arr::combine($params, $moreParams);
        
        if($params['target']) {
            $btn->newWindow = $params['target'];
            unset($params['target']);
        }
        
        if($params['warning']) {
            $btn->warning = $params['warning'];
            unset($params['warning']);
        }
        
        if($params['order']) {
            $btn->order = $params['order'];
            unset($params['order']);
        } elseif($btn->warning) {
            $btn->order = 30;
        } elseif($btn->newWindow) {
            $btn->order = 20;
        } else {
            $btn->order = 10;
        }
        
        $btn->order += count($this->buttons) / 10000;
        
        $btn->attr = $params;
        
        $id = $params['id'] ? $params['id'] : $btn->title;
        
        $this->buttons[$id] = $btn;
    }
    
    
    /**
     * Премахва посочения бутон. Ако не е посочен бутон, премахва всичките
     */
    function removeBtn($id)
    {
        if(isset($this->buttons[$id])) {
            unset($this->buttons[$id]);
        } elseif ($id == '*') {
            $this->buttons = array();
        } else {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Добавя hidden input полета до лентата с инструменти
     */
    function setHidden($arr)
    {
        $this->hidden = $arr;
    }
    
    
    /**
     * Сравняваща функция, за подредба на бутоните
     */
    static function cmp($a, $b)
    {
        if ($a->order == $b->order) {
            return 0;
        }
        
        return ($a->order < $b->order) ? -1 : 1;
    }


    /**
     * Връща броя на бутоните на тулбара
     */
    public function count()
    {
        return count($this->buttons);
    }
    
    
    /**
     * Връща html - съдържанието на лентата с инструменти
     */
    function renderHtml_()
    {
        $toolbar = new ET();
        
        if (!count($this->buttons) > 0) return $toolbar;
        
        if (Mode::is('printing')) return $toolbar;
        
        // Какъв ще бъде изгледа на лентата с инструменти?
        if ((!Mode::is('screenMode', 'narrow') && count($this->buttons) < 5) || count($this->buttons) <= 10) {
            // Показваме бутони 
            $btnCnt = 0;
            
            foreach($this->buttons as $k => $b) {
                if(Mode::is('screenMode', 'narrow')) {
                    if($b->order > 100) {
                        $this->buttons[$k]->order = $b->order - 100;
                    }
                }
            }
            
            // Сортираме бутоните
            arr::order($this->buttons);            
 
            $attr = array('id' => $this->id);
            
            ht::setUniqId($attr);

            $rowId = $attr['id'];

            $toolbar = new ET("<div class='clearfix21 toolbar'{$id}><div>[#ROW1#]</div>" . 
                "<!--ET_BEGIN ROW2--><div style='margin-top:10px;' class='toolbarHide' id='Row2_{$rowId}'>[#ROW2#]</div><!--ET_END ROW2--></div>");
 
            foreach ($this->buttons as $id => $btn) {
                
                $attr = $btn->attr;
                
                if(Mode::is('screenMode', 'narrow') && count($this->buttons) > 1) {
                    if($btnCnt == 0) {
                        $attr['class'] .= " btn-left";
                    } elseif($btnCnt == count($this->buttons)-1) {
                        $attr['class'] .= " btn-right";
                    } else {
                        $attr['class'] .= " btn-middle";
                    }
                }
                
                $place = ($btn->order < 100) ? 'ROW1' : 'ROW2';

                if ($btn->type == 'submit') {
                    $toolbar->append(ht::createSbBtn($btn->title, $btn->cmd, $btn->warning, $btn->newWindow, $attr), $place);
                } elseif ($btn->type == 'function') {
                    $toolbar->append(ht::createFnBtn($btn->title, $btn->fn, $btn->warning, $attr), $place);
                } else {
                    $toolbar->append(ht::createBtn($btn->title, $btn->url, $btn->warning, $btn->newWindow, $attr), $place);
                }
                
                $btnCnt++;
            }
            
            if($place == 'ROW2') {
                // $toolbar->append("<a href=\"javascript:toggleDisplay('Row2_{$rowId}')\" style=\"font-weight:bold; background-image:url(" . sbf('img/16/plus.png', "'") . ");\" class=\"linkWithIcon\">Още...</a>", "ROW1");

                $toolbar->append(ht::createFnBtn('Още...', "toggleDisplay('Row2_{$rowId}');", NULL, 'ef_icon=img/16/toggle-expand.png'), "ROW1");
            }

            
        } else {
            // Показваме селект меню
            $options['default'] = tr('Действие') . ' »';
            
            foreach ($this->buttons as $btn) {
                if ($btn->newWindow === TRUE) {
                    $btn->newWindow = '_blank';
                }
                
                if ($btn->type == 'submit') {
                    $b = $btn->cmd . "|" . tr($btn->warning) . "|" . $btn->newWindow . "|s";
                } elseif ($btn->type == 'function') {
                    $b = str_replace("'", "\'", $btn->fn) . "|" . tr($btn->warning) . "||f";
                } else {
                    $b = toUrl($btn->url) . "|" . tr($btn->warning) . "|" . $btn->newWindow;
                }
                $options[$b] = tr($btn->title);
            }
            static $i;
            $attr['onchange'] = "  selectToolbar(this); ";
            $attr['class'] = "button";
            $i++;
            $name = "Cmd";
            $attr['id'] = $name;
            $toolbar = ht::createSelect($name, $options, '', $attr);
            
            $toolbar->appendOnce("
                function selectToolbar( se ) {
                    var str = se.value;
                    if( str == 'default' ) return;
                    var param = str.split('|');
                    if( param[1] ) {
                        if (!confirm(param[1])) return false;
                    }
                    if( param[3] == 's' ) {

                        if(param[2]) {
                            se.form.target = param[2];
                        }

                        return se.form.submit();    
                    } else if (param[3] == 'f') {
                        return eval(param[0]);
                    } else {
                        if(param[2]) {
                            window.open(param[0],param[2]);
                        } else {
                            document.location = param[0];
                        }
                    }
                    se.value = '';
                }
            ", "SCRIPTS");
        }
        
        $toolbar->prepend(ht::createHidden($this->hidden));
        
        return $toolbar;
    }
}