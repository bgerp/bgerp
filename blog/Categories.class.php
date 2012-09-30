<?php

/**
 * Категории на статиите
 *
 *
 * @category  bgerp
 * @package   blog
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blog_Categories extends core_Master {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Категория';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, blog_Wrapper';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields='id, title';
	
	
	/**
	 * Кой може да добавя 
	 */
	var $canAdd='every_one';
	
	
	/**
	 * Кой може да редактира
	 */
	var $canEdit='every_one';
	
	
	/**
	 * Кой може да изтрива
	 */
	var $canDelete='every_one';
	
	
	/**
	 * Кой може да преглежда списъка с коментари
	 */
	var $canList='every_one';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(40)', 'caption=Категория,notNull');
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Създаване на линк към статиите, филтрирани спрямо избраната категория
	 */
	function on_AfterRecToVerbal($mvc, $row, $rec)
	{
		$row->title = ht::createLink($row->title, array('blog_Articles', 'list', 'cat' => $rec->id));
	}
	
	
	/**
	 * Ако блога е за външен изглед то потребителя няма достъп до blog_Categories
	 */
	static function on_BeforeGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
	{
		if(blog_Articles::BLOG_OUTER_WRAPPER == 'cms_tpl_Page')
		{
			$mvc->canAdd = 'no_one';
			$mvc->canEdit = 'no_one';
			$mvc->canDelete = 'no_one';
			$mvc->canList = 'no_one';
		}
	}
	
	
	/**
	 *  Поставяме Обвивка взависимост каква е тя в blog_Articles
	 */
	static function on_BeforeAction($mvc, $action)
	{
		Mode::set('wrapper', blog_Articles::BLOG_OUTER_WRAPPER);
	}
}