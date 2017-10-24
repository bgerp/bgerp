<?php



/**
 * Клас 'core_RowToolbar' - Dropdown toolbar за листовия изглед
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_RowToolbar extends core_BaseClass
{
    
    
    /**
     * Масив с връзките
     */
    var $links = array();
    
    
    /**
     * Добавя бутон, който прехвърля към хипервръзка
     * 
     * @param string $title
     * @param mixed $url
     * @param string|array $params
     * @param array $moreParams
     */
    function addLink($title, $url, $params = array(), $moreParams = array())
    {
        $btn = new stdClass();
        $btn->url = $url;
        $btn->title = $title;
        $this->add($btn, $params, $moreParams);
    }
    
     
    /**
     * Добавя бутон, който задейства js функция
     */
    function addFnLink($title, $function, $params = array(), $moreParams = array())
    {
        $btn = new stdClass();
    	$btn->type = 'function';
        $btn->title = $title;
        $btn->fn = $function;
        $this->add($btn, $params, $moreParams);
    }
    
    
    /**
     * Добавя описание на бутон във вътрешния масив
     */
    function add(&$btn, &$params, &$moreParams)
    {
        $params = arr::combine($params, $moreParams);
        
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
        
        $btn->order += count($this->links) / 10000;
        
        $btn->attr = $params;
        
        $id = $params['id'] ? $params['id'] : $btn->title;
        
        $this->links[$id] = $btn;
    }
    
    
    /**
     * Преименува заглавието на бутона
     * 
     * @param string $id - ид на бутона
     * @param string $name - новото му име
     * @return void
     */
    function renameLink($id, $name)
    {
    	expect($this->links[$id]);
    	$this->links[$id]->title = $name;
    }
    
    
    /**
     * Премахва посочения бутон/бутони в полето $ids
     * Запазва бутоните посочени в $remains 
     */
    function removeBtn($ids, $remains = NULL)
    {
        $ids = arr::make($ids, TRUE);
        $remains = arr::make($remains, TRUE);
        foreach($this->links as $id => $btn) { 
            if(($ids['*'] || $ids[$id]) && !$remains[$id]) {
                unset($this->links[$id]); 
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
    	
    	$buttons = (isset($ids['*'])) ? $this->links : $ids;
    	foreach($buttons as $id => $btn){
    		expect($this->links[$id]);
    	 	$this->links[$id]->warning = $warning;
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
    	
    	$buttons = (isset($ids['*'])) ? $this->links : $ids;
    	foreach($buttons as $id => $btn){
    		expect($this->links[$id]);
    	 	$this->links[$id]->error = $error;
    	}
    }
    

    /**
     * Връща броя на бутоните на тулбара
     */
    public function count()
    {
        return count($this->links);
    }
    
    
    /**
     * Връща html - съдържанието на лентата с инструменти
     * 
     * @param int $showWithoutToolbar - при колко линка минимум да не се показва дропдауна
     * @return core_ET $layout - рендирания тулбар
     */
    function renderHtml_($showWithoutToolbar = NULL)
    {
        if (!count($this->links) > 0) return;
        
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain')) return;
        
        setIfNot($showWithoutToolbar, 1);
        if(count($this->links) <= $showWithoutToolbar) {
        	$layout = new core_ET("<span>[#ROW_TOOLS#]</span>");
        	foreach ($this->links as $linkObj){
        		setIfNot($linkObj->attr['hint'], $linkObj->title);
        		$linkObj->attr['title'] = $linkObj->attr['title'];
        		$btn = ht::createLink('', $linkObj->url, tr($linkObj->error ? $linkObj->error : $linkObj->warning), $linkObj->attr);
        		$layout->append($btn, 'ROW_TOOLS');
        	}
        } else {
            $dropDownIcon = sbf("img/16/rowtools-btn.png", '');
            $layout = new ET("\n" . 
                            "<div class='modal-toolbar rowtoolsGroup'>[#ROW_LINKS#]</div>" .
                            "<img class='more-btn toolbar-btn button' src='{$dropDownIcon}' alt=''>");
            // Сортираме бутоните
            arr::order($this->links);            
            
            foreach($this->links as $id => $linkObj) {
                $attr = $linkObj->attr;

                ht::setUniqId($attr);
                
                $link = ht::createLink(tr($linkObj->title), $linkObj->url, $linkObj->error ? $linkObj->error : $linkObj->warning, $attr); 
                $layout->append($link, 'ROW_LINKS');
            }

            $layout->push('context/'. context_Setup::get('VERSION') . '/contextMenu.css', "CSS");
            $layout->push('context/'. context_Setup::get('VERSION') . '/contextMenu.js', "JS");

            jquery_Jquery::run($layout,'prepareContextMenu();', TRUE);
            jquery_Jquery::runAfterAjax($layout, 'prepareContextMenu');
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
    	return isset($this->links[$id]);
    }
    
    
    /**
     * Подменя урл-то на бутон, ако съществува
     * 
     * @param int $id - ид на бутон
     * @param array $newUrl - нов бутон
     */
    public function replaceBtnUrl($id, $newUrl)
    {
    	if($this->hasBtn($id)){
    		if(is_array($this->links[$id]->url)){
    			expect(is_array($newUrl));
    			$this->links[$id]->url = $newUrl;
    		}
    	}
    }
}