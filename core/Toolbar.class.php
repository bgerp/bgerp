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
     * 
     * @param string $title
     * @param mixed $url
     * @param string|array $params
     * @param array $moreParams
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
        $btn = new stdClass();
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
        
        // Ако е от частна мрежа сетваме грешката
        if ($params['checkPrivateHost'] && !$params['error']) {
            if (core_App::checkCurrentHostIsPrivate()) {
                if ($params['checkPrivateHost'] == 'warning') {
                    $params['warning'] = 'За правилна работа, bgERP трябва да е на публичен Интернет домейн';
                } else {
                    $params['error'] = 'За да работи тази услуга, bgERP трябва да е на публичен Интернет домейн';
                }
            }
            unset($params['checkPrivateHost']);
        }
        
        if($params['target']) {
            $btn->newWindow = $params['target'];
            unset($params['target']);
        }
        
        if($params['warning']) {
            $btn->warning = $params['warning'];
            unset($params['warning']);
        }
        
    	if($params['error']) {
            $btn->error = $params['error'];
            unset($params['error']);
        }
        
        if($params['order']) {
            $btn->order = $params['order'];
            unset($params['order']);
        } elseif($btn->error){
        	$btn->order = 40;
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
     * Преименува заглавието на бутона
     * 
     * @param string $id - ид на бутона
     * @param string $name - новото му име
     * @return void
     */
    function renameBtn($id, $name)
    {
    	expect($this->buttons[$id]);
    	$this->buttons[$id]->title = $name;
    }
    
    
    /**
     * Премахва посочения бутон/бутони в полето $ids
     * Запазва бутоните посочени в $remains 
     */
    function removeBtn($ids, $remains = NULL)
    {
        $ids = arr::make($ids, TRUE);
        $remains = arr::make($remains, TRUE);
        foreach($this->buttons as $id => $btn) { 
            if(($ids['*'] || $ids[$id]) && !$remains[$id]) {
                unset($this->buttons[$id]); 
                $cnt++;
            }
        }

        return $cnt;
    }
    
    
    /**
     * Добавя атрибут 'warning' на избраните бутони
     * 
     * @param mixed $ids - масив с ид-та на бутони
     * @param string $error - съобщение за грешка
     */
    function setWarning($ids, $warning)
    {
    	$ids = arr::make($ids, TRUE);
    	expect(count($ids));
    	
    	$buttons = (isset($ids['*'])) ? $this->buttons : $ids;
    	foreach($buttons as $id => $btn){
    		expect($this->buttons[$id]);
    	 	$this->buttons[$id]->warning = $warning;
    	}
    }
    
    
    /**
     * Добавя атрибут 'error' на избраните бутони
     * 
     * @param mixed $ids - масив с ид-та на бутони
     * @param string $error - съобщение за грешка
     */
	function setError($ids, $error)
    {
    	$ids = arr::make($ids, TRUE);
    	expect(count($ids));
    	
    	$buttons = (isset($ids['*'])) ? $this->buttons : $ids;
    	foreach($buttons as $id => $btn){
    		expect($this->buttons[$id]);
    	 	$this->buttons[$id]->error = $error;
    	}
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
        $layout = new ET();
        
        if (!count($this->buttons) > 0) return $layout;
        
        if (Mode::isReadOnly() || Mode::is('text', 'plain')) return $layout;
        
        // Какъв ще бъде изгледа на лентата с инструменти?
      //  if ((!Mode::is('screenMode', 'narrow') && count($this->buttons) < 5) || count($this->buttons) <= 10) {
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

            $onRow2 =0;
            $hiddenBtns = 0;

            $layout = $this->getToolbarLayout($rowId);
            foreach ($this->buttons as $id => $btn) {
                if($btn->attr['row'] == 2) {
                    $onRow2++;

                }
                if($btn->attr['row'] == 3) {
                    $hiddenBtns++;
                }
            }

            foreach ($this->buttons as $id => $btn) {
                $place = ($btn->attr['row'] == 2 && $onRow2 != 1 ) ? 'ROW2' : (($hiddenBtns > 1 && $btn->attr['row'] == 3) ? 'HIDDEN' : 'ROW1') ;

                if($place == 'ROW2'){
					$flagRow2 = TRUE;
				}
				unset($btn->attr['row']);
                $attr = $btn->attr;
				if($btn->error){
					$layout->append(ht::createErrBtn($btn->title, $btn->error, $attr), $place);
				} elseif ($btn->type == 'submit') {
                    $layout->append(ht::createSbBtn($btn->title, $btn->cmd, $btn->warning, $btn->newWindow, $attr), $place);
                } elseif ($btn->type == 'function') {
                    $layout->append(ht::createFnBtn($btn->title, $btn->fn, $btn->warning, $attr), $place);
                } else {
                    $layout->append(ht::createBtn($btn->title, $btn->url, $btn->warning, $btn->newWindow, $attr), $place);
                }
                
                $btnCnt++;
            }
           
            if($flagRow2) {
            	$this->appendSecondRow($layout, $rowId);
            }
            
        $layout->prepend(ht::createHidden($this->hidden));
        
        return $layout;
    }
    
    
    /*
     * Добавя бутона за показване на втория ред от тулбара
     */
    function appendSecondRow_($toolbar, $rowId)
    {
        $toolbar->prepend(ht::createFnBtn(' ', "toggleDisplay('Row2_{$rowId}');", NULL, array('class'=>'arrowDown more-btn', 'title'=>'Други действия с този документ')), "ROW0");
    }
    
    
    /*
     * Връща лейаута на тулбара
     */
    function getToolbarLayout_($rowId)
    {
    	if(count($this->buttons) > 5 && !Mode::is('screenMode', 'narrow') || count($this->buttons) > 3 && Mode::is('screenMode', 'narrow')) {
            $layout = new ET("\n<div class='toolbar'><div class='toolbar-first clearfix21'>[#ROW0#][#ROW1#]</div>" .

                              "<!--ET_BEGIN ROW2--><div style='display:none' class='toolbarHide clearfix21' id='Row2_{$rowId}'>[#ROW2#]</div><!--ET_END ROW2--></div>");
    	
        } else {
            $layout = new ET("\n<div class='toolbar'><div class='clearfix21'>[#ROW1#][#ROW2#]</div></div>");
    	
        }
    	
    	return $layout;
    }
    
    
    /**
     * Проверява дали даден бутон го има в тулбара
     * 
     * @param int $id - ид на бутон
     * @return boolean TRUE/FALSE - имали го бутона или не
     */
    public function hasBtn($id)
    {
    	return isset($this->buttons[$id]);
    }
}