<?php

/**
 * Архив на Блога
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blogm_Archives extends core_Manager {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Архив на блога';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, blogm_Wrapper';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields='id, title, startDate, endDate';
	
	
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
		$this->FLD('title', 'varchar(40)', 'caption=Име на Архива,mandatory');
		$this->FLD('startDate', 'datetime', 'caption=Начална Дата,mandatory');
		$this->FLD('endDate', 'datetime', 'caption=Крайна Дата,mandatory');

		$this->setDbUnique('title');
	}
	
	
	/**
	 * Създаване на линк към статиите, филтрирани спрямо избрания архив
	 */
	function on_AfterRecToVerbal($mvc, $row, $rec)
	{
		$row->title = ht::createLink($row->title, array('blogm_Articles', 'list', 'archive' => $rec->id));
	}
	
	
	/**
	 * 
	 */
	static function prepareArchives(&$data){
		
		// Взимаме Заявката към Архива
		$query = static::getQuery();
			
		// За всеки запис създаваме клас, който натрупваме в масива $data
		while($rec = $query->fetch()) {
			
			$data->archives[$rec->id] = static::getVerbal($rec, 'title');
		}
	}
	
	
	/**
	 * 
	 */
	static function renderArchives(&$data){
			
		// Шаблон, който ще представлява списъка от хиперлинкове към Архивите
		$tpl = new ET(getFileContent($data->theme . '/ArchiveList.shtml'));
		
		// За вдсеки Архив, създаваме линк и го поставяме в списъка
		foreach($data->archives as $id => $title){

            $archRowTpl = $tpl->getBlock('ROW');
            $titleDate = preg_replace('/\s+/', "-", $title);
            $title = ht::createLink($title, array('blogm_Articles', 'archive', $titleDate, 'aId' => $id));
			
			if($data->selectedArchive[$id]) {
                $attr = array('class' => 'nav_item sel_page level2');
            } else {
                $attr = array('class' => 'nav_item level2');
            }
            
            // Див-обвивка
            $title = ht::createElement('div', $attr, $title);
            $archRowTpl->replace($title, 'title');

			$archRowTpl->append2master();
		}
		
		return $tpl;
	}
}