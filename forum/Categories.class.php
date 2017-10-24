<?php

/**
 * Категории на дъските
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class forum_Categories extends core_Manager {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Категории';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools2, forum_Wrapper';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields ='id, title, order, boardCnt';
	
	
	/**
	 * Кой може да добавя 
	 */
	var $canAdd = 'forum, cms, ceo, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'forum, ceo, admin, cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'forum, ceo, admin, cms';
	
	
	/**
	 * Кой може да редактира
	 */
	var $canEdit = 'forum, cms, ceo, admin';
	
	
	/**
	 * Кой може да изтрива
	 */
	var $canDelete = 'forum, cms, ceo, admin';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(40)', 'caption=Заглавие, mandatory');
		$this->FLD('order', 'int', 'caption=Подредба');
		$this->FLD('boardCnt', 'int', 'caption=Дъски, input=none, value=0');
		
        // Към кой домейн е дадената категория
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=*)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');

		// Поставяне на уникални индекси
		$this->setDbUnique('title, order');
	}
	
	
	/**
	 * Подреждаме категориите по полето им order
	 */ 
	function on_AfterPrepareListFilters($mvc, &$data)
	{
		$data->query->orderBy('#order');
	}
	
	
	/**
	 * Подготвяме всички категории в $data
	 */
	static function prepareCategories(&$data)
	{
		// Взимаме Заявката към Категориите
		$query = static::getQuery();
		
		// Подреждаме категориите според тяхната последователност
		$query->orderBy("#order");
		
		// Ако е сетнато $data->category, то връщаме само тази категория
		if($data->category) {
			$query->where($data->category);
		} else {
            $domainId = cms_Domains::getPublicDomain('id');
            $query->where("#domainId = {$domainId}");
        }
		
		while($rec = $query->fetch()) {
           
		   // Добавяме категорията като нов елемент на $data
		   $cat = new stdClass();
		   $cat->id = $rec->id;
		   $cat->title = static::getVerbal($rec, 'title');
		   $url = array('forum_Boards', 'Forum', 'cat' => $cat->id);
		   $cat->title = ht::createLink($cat->title, $url);
		   $data->categories[] = $cat;
		}
	}
	
	
	/**
	 * Създаване на линк към дъските, филтрирани спрямо избраната категория
	 */
	function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
	{
		if($fields['-list']) {
			$row->title = ht::createLink($row->title, array('forum_Boards', 'list', 'cat' => $rec->id));
		}
	}
	
	
	/**
	 *  Обновяваме броя на дъските в подадената категория
	 *  @param int $id
	 *  @return void
	 */
	static function updateCategory($id)
	{
		$rec = static::fetch($id);
		$query = forum_Boards::getQuery();
		$query->where("#category = {$id}");
		
		// Преброяваме дъските от тази категория
		$rec->boardCnt = $query->count();
		
		// Обновяваме записа
		static::save($rec);
	}
}