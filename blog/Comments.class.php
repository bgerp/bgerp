<?php

/**
 * Коментари на статиите
 *
 *
 * @category  bgerp
 * @package   blog
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blog_Comments extends core_Detail {

	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Коментари';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, blog_Wrapper, plg_State2';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields = 'email, comment, author, grade, createdOn, modifiedOn, createdBy,state';
	
	
	/**
	 * Кой може да добавя коментари
	 */
	var $canAdd = 'every_one';
	
	
	/**
	 * Кой може да изтрива коментари
	 */
	var $canDelete = 'no_one';
	
	
	/**
	 * Кой има достъп до Спосъка с коментати
	 */
	var $canList = 'every_one';
	
	
	/**
	 * Мастър ключ към статиите
	 */
	var $masterKey = 'articleId';
	
	
	/**
	 * Брой на коментари на страница
	 */
	var $listItemsPerPage = "3";
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('articleId', 'key(mvc=blog_Articles, select=title)', 'caption=Тема, input=hidden, silent');
		$this->FLD('author', 'nick', 'caption=Автор');
		$this->FLD('email', 'email', 'caption=Имейл');
		$this->FLD('comment', 'richtext', 'caption=Коментар');
		$this->FLD('grade', 'enum(1=Лоша,2=Слаба,3=Средна,4=Добра,5=Мн. Добра,6=Отлична)', 'caption=Оценете статията, columns=2, maxRadio=6, input=none');
		$this->FLD('botCheck', 'int', 'input=hidden');
	}
	
	
	/**
	 * Нова функция която се извиква blog_Articles - act_Show
	 * от и рендира коментарите в нов шаблон
	 */
	function renderShowDetail($data)
	{
		// Намираме мастър статията
		$masterArticle = blog_Articles::fetch($data->masterId);
		
		// Празен шаблон в, в който ще върнем резултата
		$tpl = new ET("");
		
		// Празен шаблон, който ще събира всеки коментар, разположен в елемент на списъка
		$rowList = new ET('[#COMMENTS#][#ListToolbar#]');
		
		// Ако има коментари към статията
		if(isset($data->rows))
		{
			// Взимаме шаблона за представяне на коментарите за външен достъп
			$tpl = new ET(getFileContent('blog/tpl/ArticleDetailsTableView.shtml'));
			
			// За всеки коментар
			foreach ($data->rows as $row)
			{
				// Създава се шаблон за представяне на единичен коментар, и се 
				// заместват плейсхолдърите в него с данните от текущия $row 
				$cTpl = $tpl->getBlock("COMMENT_LI");
				$cTpl->placeObject($row);
				$cTpl->replace($row->id, 'tools');
				
				// Добавяме шаблона към неговия мастър шаблон
				$cTpl->append2master();
			}
				
			// Добавяме Пейджъра под и  над коментарите
			$comments = cls::get('blog_Comments');
			$tpl->append(new ET("<div style='margin-left:25px'>[#ListBottomPager#]</div>"));
			$tpl->replace($comments->renderListPager($data), 'ListBottomPager');
		}
			
		// Ако статията е заключена 
		if(!blog_Comments::haveRightFor('add', $masterArticle->commentsMode)){
			
			// Добавяме съобщение, че статията е заключена
			$tpl->append(new ET("<div style='width:300px;margin-left:auto;margin-right:auto'><h3>Коментарите са забранени</h3></div>"));
		}
		
		
		// Връщаме шаблона
		return $tpl;
	}
	
	
	/**
	 * Обработка на заявката с резултатите
	 */
	static function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		// Ако екшъна е 'show'
		if(Request::get('Act') == 'show') {
			
			// Показваме само коментарите, които са активирани
			$data->query->where("#state = 'active'");
		}
		
		// Сортираме коментарите по дата на създаване в низходящ ред
		$data->query->orderBy('createdOn', 'DESC');
	}
	
	
	/**
	 * Ако коментираната статия има опцията да бъде оценяване, то контрола за поставяне на
	 * оценка се показва, в противен случай не се
	 */
	static function on_AfterPrepareEditForm($mvc, &$data)
	{
			// Извличаме мастър статията
			$aRec = blog_Articles::fetch($data->form->rec->articleId);
 			
			// Ако е с възможност за оценяване то радио бутоните се показват в формата
			if($aRec->commentsMode == "grade" ){
 				$data->form->setField('grade', 'input=radio');
 			} 
 			$data->form->setField('state', 'input=hidden');
 			
 			// По подразбиране, всеки коментар няма да бъде активен, чак след като се активира ръчно
 			// ще бъде достъпен за преглед от екшъна 'show'
			$data->form->setHidden('state', 'closed');
	}
	
	
	/**
	 *  Ако статията неможе да бъде коментираме, премахваме правото за добавяне на 
	 *  нов коментар
	 */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
	{
		// Проверяваме имаме ли запис и дали екшъна е 'add'
		if(isset($rec) && $action == 'add'){
			
			// Ако записа е то статията е заключена за коментиране
			if($rec == 'disabled'){
				
				// Връщаме  'no_one' за да забраним статията за коментиране
				$res = 'no_one';
			}
		} 
	}
}