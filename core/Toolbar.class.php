<?php

/**
 * Клас 'core_Toolbar' - Вюър за лента с бутони
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Toolbar extends core_BaseClass
{
    
    
    /**
     * Масив с бутоните на тулбара
     */
    var $buttons = array();
    
    
    /**
     * Добавя бутон, който прехвърля към хипервръзка
     */
    function addBtn($title, $url, $params = array(), $moreParams = array())
    {
        $btn->url = $url;
        $btn->title = $title;
        $btn->params = $title;
        $params = arr::combine($params, $moreParams);
        $btn->warning = $params['warning'];
        $btn->newWindow = $params['target'];
        unset($params['warning']);
        $btn->attr = $params;
        
        if($btn->warning) {
           $btn->order = 30;
        } elseif($btn->newWindow) {
           $btn->order = 20;
        } else {
            $btn->order = 10;
        }
    
        $id = $params['id']?$params['id']:$title;
        $this->buttons[$id] = $btn;
    }
    
    
    /**
     * Добавя 'submit' бутон
     */
    function addSbBtn($title, $cmd = 'default', $params = array(), $moreParams = array())
    {
        $btn->type = 'submit';
        $btn->title = $title;
        $btn->cmd = $cmd;
        $params = arr::combine($params, $moreParams);
        $btn->warning = $params['warning'];
        $btn->newWindow = $params['target'];
        unset($params['newWindow'], $params['warning']);
        $btn->attr = $params;

        if($btn->warning) {
           $btn->order = 30;
        } elseif($btn->newWindow) {
           $btn->order = 20;
        } else {
            $btn->order = 10;
        }
        
        $id = $params['id']?$params['id']:$title;
        $this->buttons[$id] = $btn;
    }
    
    
    /**
     * Добавя бутон, който здейства js функция
     */
    function addFnBtn($title, $function, $params = array(), $moreParams = array())
    {
        $btn->type = 'function';
        $btn->title = $title;
        $btn->fn = $function;
        $params = arr::combine($params, $moreParams);
        $btn->warning = $params['warning'];
        
        unset($params['warning']);
        $btn->attr = $params;
        
        if($btn->warning) {
           $btn->order = 30;
        } elseif($btn->newWindow) {
           $btn->order = 20;
        } else {
            $btn->order = 10;
        }

        $id = $params['id']?$params['id']:$title;
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
            expect(FALSE, 'Непознат бутон за махане');
        }
    }
    
    
    /**
     * Добавя hidden input полета до тулбара
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
     * Връща html - съдържанието на тулбара
     */
    function renderHtml_()
    {
        $toolbar = new ET();
        
        if (!count($this->buttons) > 0) return $toolbar;
        
        if (Mode::is('printing')) return $toolbar;
        
        // Какъв ще бъде изгледа на тулбара?
        if ((!Mode::is('screenMode', 'narrow') && count($this->buttons) < 10) || count($this->buttons) <= 21) {
            // Показваме бутони 
            $btnCnt = 0;

            // Сортираме бутоните


            uasort ( $this->buttons , 'core_Toolbar::cmp' );

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
                
                if ($btn->type == 'submit') {
                    $toolbar->append(ht::createSbBtn($btn->title, $btn->cmd, $btn->warning, $btn->newWindow, $attr));
                } elseif ($btn->type == 'function') {
                    $toolbar->append(ht::createFnBtn($btn->title, $btn->fn, $btn->warning, $attr));
                } else {
                    $toolbar->append(ht::createBtn($btn->title, $btn->url, $btn->warning, $btn->newWindow, $attr));
                }
                
                $btnCnt++;
            }
            
            if($this->id) {
                $id = " id='{$this->id}'";
            } else {
                $id = '';
            }
            $toolbar->prepend("<div class='toolbar'{$id}>");
            $toolbar->append('</div>');
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