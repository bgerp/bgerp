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
	var $canAdd='cms, ceo, admin';
	
	
	/**
	 * Кой може да редактира
	 */
	var $canEdit='cms, ceo, admin';
	
	
	/**
	 * Кой може да изтрива
	 */
	var $canDelete='cms, ceo, admin';
	
	
	/**
	 * Кой може да преглежда списъка с коментари
	 */
	var $canList='cms, ceo, admin';
	
	
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
	 * Метод за извличане на всички Категории и съхраняването им в масив от обекти
	 */
	static function prepareNavigation($data)
	{
		
		// Взимаме Заявката към Категориите
		$query = static::getQuery();
			
		// За всеки запис създаваме клас, който натрупваме в масива $data
		foreach($query->fetchAll() as $rec) {
			
			// Празен стандартен клас
			$cat = new stdClass();
			
			// Задаване ид-то и заглавието на статията на обекта
			$cat->id = $rec->id;
			$cat->title = static::getVerbal($rec, 'title');
			
			// Добавяме категорията като нов елемент на $data
			$data[] = $cat;
		}
		
		// Връщаме масива
		return $data;
	}
	
	
	/**
	 * Статичен метод за рендиране на меню със всички категории, връща шаблон
	 */
	static function renderNavigation($data) {
		
		// Шаблон, който ще представлява списъка от хиперлинкове към категориите
		$tpl=new ET("<ul id='categories-menu' style='list-style:none;padding-left:5px'>
							<li id='cat_header'>Категории</li>
							<li class='categories_list'>
								<a href='/blog_Articles/browse'>Всички</a>
							</li>
							[#category#]
					</ul>");
		
		// За всяка Категория, създаваме линк и го поставяме в списъка
		foreach($data as $row){
			
			// Създаваме линк, който ще покаже само статиите от избраната категория
			$link = ht::createLink($row->title, array('blog_Articles', 'browse', 'cat'  => $row->id));
			
			// Създаваме шаблон, после заместваме плейсхолдъра със самия линк
			$catTpl = new ET("<li class='categories_list'>[#cat#]</li>");
			$catTpl->replace($link->content, 'cat');
			
			// Натрупваме линковете в $tpl
			$tpl->append($catTpl, 'category');
		}
		
		// Връщаме вече рендираният шаблон
		return $tpl;
	}
}