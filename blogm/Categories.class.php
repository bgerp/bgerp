<?php

/**
 * Категории на статиите
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blogm_Categories extends core_Manager {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Категории в блога';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, blogm_Wrapper';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields='id, title, description';
	
	
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
		$this->FLD('title', 'varchar(40)', 'caption=Заглавие,mandatory');
		$this->FLD('description', 'text', 'caption=Описание');

		$this->setDbUnique('title');
	}
	
	
	/**
	 * Създаване на линк към статиите, филтрирани спрямо избраната категория
	 */
	function on_AfterRecToVerbal($mvc, $row, $rec)
	{
		$row->title = ht::createLink($row->title, array('blogm_Articles', 'list', 'category' => $rec->id));
	}
	
	
	/**
	 * Метод за извличане на всички Категории и съхраняването им в масив от обекти
	 */
	static function prepareCategories(&$data)
	{
		// Взимаме Заявката към Категориите
		$query = static::getQuery();
			
		// За всеки запис създаваме клас, който натрупваме в масива $data
		while($rec = $query->fetch()) {
            
			// Добавяме категорията като нов елемент на $data
			$data->categories[$rec->id] = static::getVerbal($rec, 'title');
		}
	}
	
	
	/**
	 * Статичен метод за рендиране на меню със всички категории, връща шаблон
	 */
	static function renderCategories_($data)
    {
		// Шаблон, който ще представлява списъка от хиперлинкове към категориите
		$tpl = new ET();
 
        if(!$data->categories) {
            $data->categories = array();
        }

        $cat = array('' => 'Всички') + $data->categories;
		
		// За всяка Категория, създаваме линк и го поставяме в списъка
		foreach($cat as $id => $title){

            if($data->selectedCategories[$id] || (!$id && !count($data->selectedCategories))) {
                $attr = array('class' => 'nav_item sel_page level2');
            } else {
                $attr = array('class' => 'nav_item level2');
            }
			
			// Създаваме линк, който ще покаже само статиите от избраната категория
			$title = ht::createLink(tr($title), $id ? array('blogm_Articles', 'browse', 'category'  => $id) : array('blogm_Articles'));
			
            // Див-обвивка
            $title = ht::createElement('div', $attr, $title);

			// Създаваме шаблон, после заместваме плейсхолдъра със самия линк
			$tpl->append($title);
		}
	    
 
		// Връщаме вече рендираният шаблон
		return $tpl;
	}



}