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
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, blog_Wrapper';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields = 'email, comment, author, grade, createdOn, modifiedOn, createdBy';
	
	
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
	 * Модификация на данните за изглед в нов табличен изглед
	 */
	function on_BeforeRenderDetail($mvc, $res, &$data)
	{
		if(blog_Articles::BLOG_OUTER_WRAPPER == 'cms_tpl_Page'){
		$masterArticle = blog_Articles::fetch($data->masterId);
		$tpl = new ET("");
		// Празен шаблон, който ще събира всеки коментар, разположен в елемент на списъка
		$rowList = new ET('[#COMMENTS#][#ListToolbar#]');
		
		if(isset($data->rows))
		{
			/* Обхождат се всички коментара и техните данни се заместват в новия шаблон
			   за представяне на коментара, после той се добавя към празния шаблон, който трупа всеки коментар
			   като елемент на списъка  */
			$tpl = new ET(getFileContent('blog/tpl/ArticleDetailsTableView.shtml'));
			foreach ($data->rows as $row)
			{
				/* Създава се шаблон за представяне на единичен коментар, и се заместват плейсхолдърите в него с данните
				от текущия $row */
				$cTpl = $tpl->getBlock("COMMENT_LI");
				$cTpl->placeObject($row);
				$cTpl->replace($row->id, 'tools');
				$cTpl->append2master();
				
			}
			
			// Добавяме Пейджъра под и  над коментарите
			$comments = cls::get('blog_Comments');
			$tpl->append(new ET("<div style='margin-left:25px'>[#ListBottomPager#]</div>"));
			$tpl->replace($comments->renderListPager($data), 'ListBottomPager');
			
			if(blog_Articles::haveRightFor('Single') && blog_Comments::haveRightFor('add',$masterArticle->commentsMode))
			{
				$tpl->append(new ET('[#ListToolbar#]'));
				$tpl->replace($comments->renderListToolbar($data), 'ListToolbar');
			}
			elseif(!blog_Comments::haveRightFor('add', $masterArticle->commentsMode)){
				$tpl->append(new ET("<div style='width:300px;margin-left:auto;margin-right:auto'><h3>Коментарите са забранени</h3></div>"));
			}
		}
			
			$commentRec = new stdClass();
			$commentRec->articleId = $masterArticle->id;
			$res = $tpl;
 			
			return FALSE;
			
		}
	}
	
	
	/**
	 * Сортиране на коментарите по дата на създаване в низходящ ред
	 */
	static function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		$data->query->orderBy('createdOn', 'DESC');
	}
	
	
	/**
	 * Ако коментираната статия има опцията да бъде оценяване, то контрола за поставяне на оценка се показва,
	 * в противен случай не се
	 */
	static function on_AfterPrepareEditForm($mvc, &$data)
	{
			$aRec = blog_Articles::fetch($data->form->rec->articleId);
 			
			if($aRec->commentsMode == "grade" ){
 				$data->form->setField('grade', 'input=radio');
			} 
			/*$data->form->fieldsLayout = new ET("
				<ul style='margin-top:-4px;border:0px solid green;min-height:300px;width:90%;list-style:none;padding-left:0px;margin-left:auto;margin-right:auto'>
					<li>&nbsp;</li>
				<li style='margin-top:8px'><span style=''>Автор:</span><span style='margin-left:15%;'>[#author#]</span></li>
					<li style='margin-top:8px'><span style=''>Ел. Поща:</span><span style='margin-left:61px;'>[#email#]</span></li>
					<li style='margin-top:8px'>[#comment#]</li>
					<!-- ET_BEGIN GRADE --><li style='margin-top:8px'>
					<span style='width:30%;height:50px'>[#grade#]</span></li><!-- ET_END GRADE -->
				</ul>
				 ");
			$data->form->layout = new ET(
					"<span style='text-align:center;'><h2>Редактиране на Коментар</h2></span>".
					"<form style='margin-top:0px;margin-left:auto;margin-right:auto;width:120%' id='" .
					$data->form->formAttr['id'] .
					"' method=\"[#FORM_METHOD#]\" action='[#FORM_ACTION#]' <!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
					"\n<div class='clearfix21 horizontal' style='margin-top:0px;'>" .
					"<!--ET_BEGIN FORM_ERROR--><div class=\"formError\">[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->" .
					"<!--ET_BEGIN FORM_INFO--><div class=\"formInfo\">[#FORM_INFO#]</div><!--ET_END FORM_INFO-->" .
					"<!--ET_BEGIN FORM_HIDDEN-->[#FORM_HIDDEN#]<!--ET_END FORM_HIDDEN-->" ."\n" .
					"<!--ET_BEGIN FORM_FIELDS--><div class='formFields' style='width:100%;'>[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->\n" .
					"<!--ET_BEGIN FORM_TOOLBAR--><div class='formToolbar' style='margin-left:30px;'>[#FORM_TOOLBAR#]</div><!--ET_END FORM_TOOLBAR-->\n" .
					"</div><br></form>\n" .
					"\n"
			);*/
		
	}
	
	
	/**
	 *  Ако статията неможе да бъде коментираме, премахваме правото за добавяне на нов коментар
	 */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
	{
		if(isset($rec) && $action == 'add'){
			
		if($rec == 'disabled'){
			$res = 'no_one';
		}
		}
		
	}
	
	static function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
	{
		//bp($rec);
	
	}
	static function on_BeforeAction($mvc, $action)
	{
		Mode::set('wrapper', blog_Articles::BLOG_OUTER_WRAPPER);
	}
	static function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data)
	{
		/* Ако класа е за външен изглед то се генерира нова обвивка, както и се добавят категориите
			от страни */
		if(blog_Articles::BLOG_OUTER_WRAPPER == 'cms_tpl_Page') {
				
		// Нов темплейт, който ще помести категориите и статиите за външния изглед
		$res = new ET("<div style='width:140%;margin-left:auto,margin-right:auto;'>
					<div style='float:left;width:28%;margin-top:0%;margin-left:3%'>
					<ul id='categories-menu' style='list-style:none;padding-left:5px'>
							<li id='cat_header'>Категории</li>
					<li class='categories_list'><a href='/blog_Articles/list'>Всички</a></li>
							[#category#]
					</ul></div>
					<div style='float:right;width:68%'>[#PAGE_CONTENT#]</div>
					</div>"
			);
				
			/* Извлича имената на всички категории, и ги представя във вида на списък, с линкове, които
				ще заредят само статиите на съоветната категория */
			$query = blog_Categories::getQuery();
				
			foreach($query->fetchAll() as $cat) {
				$catTitle = blog_Categories::getVerbal($cat, 'title');
				$catLink = ht::createLink($catTitle, array('blog_Articles', 'list', 'cat' => $cat->id));
				$catTpl = new ET("<li class='categories_list'>[#cat#]</li>");
				$catTpl->replace($catLink->content, 'cat');
				$res->append($catTpl, 'category');
			}
				
			// Новата информация се помества в темплейта
			$res->replace($tpl, 'PAGE_CONTENT');
				
			// Връщаме Лъжа за да не се генерира шаблона по дефолт
			return FALSE;
		}
	}
	
	static function on_BeforeGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
	{
		if(blog_Articles::BLOG_OUTER_WRAPPER == 'cms_tpl_Page')
		{
			$mvc->canList = 'no_one';
		}
	}
	
}