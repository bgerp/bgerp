<?php

/**
 * Линкове
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blogm_Links extends core_Manager {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Ние четем';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_State, blogm_Wrapper, plg_Created, plg_Modified';
	

   /**
	 * Полета за листов изглед
	 */
	var $listFields =' id, name, url, state';
	

	/**
	 * Кой може да листва линковете
	 */
	var $canRead = 'cms, ceo, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива линк
	 */
	var $canWrite = 'cms, ceo, admin';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('name', 'varchar(50)', 'caption=Наименование, mandatory, notNull');
		$this->FLD('url', 'url', 'caption=Адрес, mandatory, notNull');
		$this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,mandatory');
		
		// Уникални полета
		$this->setDbUnique('name');
		$this->setDbUnique('url');
	}
	
	
	/**
	 * Метод за извличане на всички Линкове и съхраняването им в масив от обекти
	 */
	static function prepareLinks(&$data)
	{
		// Взимаме Заявката към Линковете
		$query = static::getQuery();
		
		// Избираме само активните линкове
		$query->where("#state = 'active'");
		
		// За всеки запис създаваме клас, който натрупваме в масива $data
		while($rec = $query->fetch()) {
            $link = new stdClass();
			$link->name = static::getVerbal($rec, 'name');
			$link->url = $rec->url;
			
			// Добавяме линка като нов елемент на $data
			$data->links[$rec->id] = $link;
		}
	}
	
	
	/**
	 *  Метод за рендиране на линковете
	 */
	static function renderLinks($data) {
		
		$tpl = new ET();
		
		if($data->links) {
			foreach($data->links as $link){
				// Създаваме линк от заглавието и урл-то 
				$name = ht::createLink(tr($link->name), $link->url);
				$name = ht::createElement('div', array('class' => 'nav_item level2'), $name);
				
				// Добавяме линка към шаблона
				$tpl->append($name);
			}
		}
		
		return $tpl;
	}
}