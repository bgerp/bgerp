<?php

/**
 * Статии
 *
 *
 * @category  bgerp
 * @package   blog
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blog_Articles extends core_Master {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Тестов Блог';
	
	
	/**
	 * Тип на разрешените файлове за качване
	 */
	const FILE_BUCKET = 'productsFiles';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, plg_State2, plg_Printing, blog_Wrapper, plg_Search';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields ='id, tools, title, cat, body, author, createdOn, modifiedOn, state, comments=Брой коментари, average=Средна оценка'; //,created_on,created_by,modified_on
	
	
	/**
	 *  Брой статии на страница 
	 */
	var $listItemsPerPage = "4";
	
	
	/**
	 * Коментари на статията
	 */
	var $details = 'blog_Comments';
	
	
	/** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'title, author';
	
	
	/**
	 * Кой може да добавя статии
	 */
	var $canWrite  = 'every_one';
	
	
	/**
	 * 
	 */
	var $canChangestate='every_one';
	
	
	/**
	 * Кой може да изтрива статии
	 */
	var $canDelete = 'every_one';
	
	
	/**
	 * Кое поле ще съдържа туулбара за единичен изглед, редакция или изтриване на
	 * коментара
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Кой може да разрешава/забранява една статия да бъде коментирана и оценявана
	 */
	var $canChangemode = 'every_one';
	
	
	/**
	 * Кой има достъп до метода Show
	 */
	var $canShow = 'no_one';
	
	
	/**
	 * Кой има достъп до метода Single
	 */
	var $canSingle = 'every_one';
	
	
	/**
	 * Кой може да добавя статии
	 */
	var $canComment = 'every_one';
	
	
	/**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'blog/tpl/SingleArticle.shtml';
	
	
	/**
	 * Обвивка за блога
	 */
	//var $articleWrapper = 'page_Internal';//page_Internal cms_tpl_Page
	const BLOG_OUTER_WRAPPER = 'cms_tpl_Page';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(190)', 'caption=Тема на статията, notNull');
		$this->FLD('author', 'varchar(40)', 'caption=Автор, notNull');
		$this->FLD('body', 'richtext', 'caption=Текст');
		$this->FLD('state', 'enum(active=Активен,closed=Затворен)', 'caption=Видимост');
		$this->FLD('commentsMode', 'enum(enabled=Разрешени,disabled=Забранени,grade=С оценка)', 'caption=Статус на коментарите');
		$this->FLD('fileHnd', 'fileman_FileType(bucket=' . self::FILE_BUCKET . ')', 'caption=Качете Файл');
		$this->FLD('cat', 'keylist(mvc=blog_Categories,select=title)', 'caption=Категории');
		$this->setDbUnique('title');
	}
	
	
	/*
	 * Ако блога е за външен изглед, ние заменяме шаблона, който ще са представени
	 * статиите. Ако блога е за вътрешен изглед, то той не бива променен
	 */
	function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{   
		
		/* Ако блога е за вътрешен достъп, то не променяме неговия изглед, ако е за
		външен то ние заменяме изцяло изгледа */
		if(blog_Articles::haveRightFor('single')) {
			
			return TRUE;
		}
		
		// Създаваме темплейт който ще помести статиите
		$tpl = new ET("[#ARTICLES#]");
		
		/* Тази променлива ще представлява шаблон, който ще натрупа всички статии
		във вида на списък, или ако няма статии, ще покаже съобщение за липсващи
		такива */ 
		$rowList = '';
		
		//  Ако има налични статии ги показваме в лист таблицата
		if(isset($data->rows)) {
			
			// Взимаме съдържанието на шаблона за представяне на статиите 
			$rowList = new ET(getFileContent('blog/tpl/ArticleListElement.shtml'));
			
			// За всеки запис добавяме статията като нов елемент на списъка
			foreach($data->rows as $row) {
				
					$articleLI = $rowList->getBlock("ARTICLE_LI");
					$articleLI->replace($row->title, 'TITLE');
					$articleLI->replace($row->cat, 'CAT');
					$articleLI->replace($row->author, 'AUTHOR');
					$articleLI->replace($row->body, 'BODY');
					$articleLI->replace($row->createdOn, 'CREATEDON');
					$articleLI->replace($row->comments, 'COMMENTS');
					
					/* Ако метода метода за единичен изглед е Show, то създаваме иконка,
					изглеждаща по същия начин като тази за Single  изгледа но тя пренасочва
					към метода Show, така без потребителя да забележе единичния изглед е 
					достъпен чрез метода Show  вместо със Single */
					if(blog_Articles::haveRightFor('show')) 
					{
						// Създаваме елемент, който да покаже изображението
						$img = ht::createElement('img', array('src' => sbf('img/16/view.png', '')));
						
						// Правим иконката като хипервръзка към метода Show 
						$link = ht::createLink($img->content, array('blog_Articles', 'show', $row->id, 'ret_url' => TRUE));
						
						// Добавяме линка към Списъчния изглед
						$articleLI->append($link->content, 'SHOW_LINK');
					}
					
					// Добавяме към шаблона полето с инструменти а единичен изглед и редакция
					$articleLI->replace($row->tools, 'TOOLS');
					$img = ht::createElement('img', array('src' => sbf('img/16/comment.png', '')));
					$articleLI->replace($img->content, 'IMG');
					$articleLI->append2master();
					
					// Ако статията е разрешена да бъде оценявана то поместваме в темплейта нейната средна оценка
					if($row->commentsMode == 'grade?'){
						$articleLI->replace($row->average, 'AVERAGE');
					}
			}
		}
		else {
			
			// Зареждаме изображение което ще покажем
			$img = ht::createElement('img', array('src' => sbf('img/sad2.png', ''), 
												  'width' => '80px', 
												  'height' => '80px',
												  'id' => 'sadface'));
			// Ако няма намерени статии, показваме подходящо съобщение
			$rowList = new ET("<table id='no-articles-found'><tr>
							 <td>[#img#]</td>
							 <td>&nbsp;&nbsp;&nbsp;&nbsp;Няма намерени статии !!!</td>
							 </tr></table>");
			// Поставяме изображението в шаблона
			$rowList->replace($img->content, 'img');
		}
		
		// Заместваме $rowList в шаблона който ще рендираме
		$tpl->replace($rowList, 'ARTICLES');
		
		// Връщаме Лъжа за да не се зареди темплейта по пдоразбиране
		return FALSE;		
	}
	
	
	/**
	 * Създаване на линкове, съкратяване на текста на статията, изчисляване
	 * на средната оценка, и брой на коментарите
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields=array())
	{
		if($mvc::haveRightFor('single')) {
			$singleAction = 'single';
		}
		else {
			$singleAction = 'show';
		}
		/* Създаваме масив, чийто елементи са вербалните имена на категориите 
		от $row->cat, за всяка категория, на която принадлежи статията създаваме линк
		който при кликване филтрира статиите по категория */
		
		// @toDo s $categories = type_Keylist::toArray($rec->cat);
		$categories = explode(", ", $row->cat);
		
		// Изпразваме досегашното съдържание $row->cat
		$row->cat = '';
		
		// Инстанцираме клас, чрез който ще намерим ид-та на категориите
		$catRec = cls::get('blog_Categories');
		
		/* За всяка категория, на която отговаря статията създаваме линк от името
		на категорията, който при натискане подава в url-а ид-то на категорията */
		foreach($categories as $cat)
		{
			// Намираме ид-то на съответната категория
			$id = $catRec->fetchField("#title='" . $cat . "'", 'id');
			
			/* Към  $row->catдобавяме линк за филтриране на статията по категория,
			 накрая в $row->cat имаме по един хиперлинк за съответната категория */
			$row->cat .= ht::createLink($cat, array('blog_Articles', 'list', 'cat' => $id)) ."&nbsp;&nbsp;";
		}
		
		/* Проверява дали екшъна е list ако да, текста на статията бива съкратен 
		 * за представяне в изгледа ако екшъна не е list не се правят промени 
		 * по дължината на текста*/
		if(isset ($fields['-list']))
		{
			$singleUrl = array(
					$mvc->className,
					$singleAction,
					'id' => $rec->id,
					'ret_url' => TRUE
			);
			/* Ако Блога е за вътрешен достъп то намаляваме дължината на тялото на
			статията до не повече от 150 символа, ако е за външен достъп - тялото се
			ограничава до не повече от 500 символа */
			if($mvc::haveRightFor('single')) {
				$num_chars = 110;
			}
			else {
				$num_chars = 500;
			}
			
			/* Ако текста на статията е с над 150 символа то той бива съкратен,
			 в противен случай не се променя */
			if(strlen($row->body->content) > $num_chars){
				
				$shortBody = strip_tags(mb_substr($row->body->content, 0, $num_chars));
				
				// Добавя бутон "още" който отваря единичния изглед на статията
				$shortBody .= "....&nbsp;&nbsp;" . ht::createLink("[още]", $singleUrl)->content;
				$row->body->content = $shortBody;
			}
			$row->commentsMode = type_Enum::toVerbal($rec->commentsMode);
			
			// Ако коментарите на статията са разрешени ( с или без оценка )
			if($rec->commentsMode != 'disabled'){
			
				// Преброява всички коментари към всяка статия и ги представя в таблицата
				$commentNumber = cls::get('blog_Comments')->count("article_id=" . $rec->id);
			
				/* Намира средната оценка на статията като средно-артиметично на броя на 
				всички поставени оценки, ако статията позволява да бъде оценена и ако броя на 
				коментарите е повече от 0 */
				if($commentNumber != 0 && $row->commentsMode == 'grade?') {
					$query = blog_Comments::getQuery();
					$query->XPR('sumGrades', 'int', 'min(#grade)');
					$query->where("article_id = '" . $rec->id . "'");
					$averageGrade=ceil($query->fetch()->sumGrades / $commentNumber );
				}
				else {
					
					/* Ако коментарите са 0 или статията неможе да бъде оценена то, се показва 
					 че тя няма средна оценка  */
					$averageGrade = "няма";
				}
			
				// Вербално представяне на оценката на статията по шестобалната система
				switch($averageGrade) {
					case 1:
						$averageGrade = "Лоша";
						break;
					case 2:
						$averageGrade = "Слаба";
						break;
					case 3:
						$averageGrade = "Средна";
						break;
					case 4:
						$averageGrade = "Добра";
						break;
					case 5:
						$averageGrade = "Мн.Добра";
						break;
					case 6:
						$averageGrade = "Отлична";
						break;
				}
				
				if($averageGrade != "няма"){
					$row->average = '<br><span class="article-average">Средна оценка:&nbsp' . $averageGrade . "</span>";
				}
				else {
					$row->average = "няма";
				}
				
				/* Добавя към хиперлинка за единичен изглед, котва към секцията
				с коментарите */
				$singleUrl['#'] = 'top';
				
				// Създаваме текста на хиперлинка, водещ към секцията с коментари
				$commentlink = "Коментари:&nbsp;&nbsp;" . $commentNumber;
				
				// Поставяне на хиперлинка в $row->comments
				$row->comments = ht::createLink($commentlink, $singleUrl);
			}
			else {
				/* Ако статията е забранена за коментиране то тя няма средна оценка и се показва че 
				 * комнтарите са забранени */
				$row->average = "няма";
				$row->comments = "Коментари:&nbsp;&nbsp;забранени";
			}
		}
	}
	
	
	/**
	 *  извършва филтриране и подреждане на статиите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		// Ако е избрана категория, то се показват само статиите отговарящи на нея
		$cat = Request::get('cat', 'int');
		if(isset($cat)){
				$data->query->where("#cat LIKE '%|{$cat}|%'");
			}
		
		/* Ако е посочено филтриране по име на статията то се показват само онези
		статии които съдържат в името си ключовата дума (думи) */	
		$searchTitle = $data->listFilter->rec->title;
		if($searchTitle){
				$data->query->where("#title LIKE '%{$searchTitle}%'");
			}
		
		// Подреждаме статиите по датата им на публикуане в низходящ ред	
		$data->query->orderBy('createdOn', 'DESC');
		
		/* Ако статията е за външен изглед, то показваме само активните статии,
		не-активните такива ще бъдат достъпни единствено във вътрешния изглед */
		if($mvc::haveRightFor('show')){
			$data->query->where("#state = 'active'");
		}
	}
	
	
	/**
	 * Обработка на заглавието
	 */
	function on_AfterPrepareListTitle($mvc, $data)
	{
		// Проверява имали избрана категория
		$cat = Request::get('cat', 'int');
		
		if(isset($cat)) {
			
			/* Ако е избрана се взима заглавието на категорията, което отговаря
			 на посоченото id на категорията */
			$catRec = blog_Categories::fetch($cat);
			$title = blog_Categories::getVerbal($catRec, 'title');
			
			// В заглавието на list  изгледа се поставя името на избраната категория
			$data->title = 'Статии от категория:&nbsp;&nbsp;&nbsp;&nbsp;' . $title;
		}
	}
	
	
	/**
	 * Ако сме в изгледа то не показваме заглавие на страницата, така заглавие има
	 * единствено ако се показват статии отговарящи на дадена категория
	 */
	static function on_BeforeRenderListTitle($data)
    {
    	$cat = Request::get('cat', 'int');
    	if(!isset($cat)){
    		
    		return FALSE;
    	}
    }
	
	
	/**
	 * Ако блога е за външен изглед, подменяме шаблона за list  изгледа
	 */
	function on_BeforeRenderListLayout($mvc, &$res, $data)
	{
		if(blog_Articles::BLOG_OUTER_WRAPPER == 'cms_tpl_Page') {
			$className = cls::getClassName($this);
			$res = new ET("
				[#ListTitle#]
				<div class='listTopContainer'>
				[#ListFilter#]
				[#ListSummary#]
				</div>
				<div style='border:0px solid red;width:690px'>
					<div style='float:left'>[#ListPagerTop#]</div>
					<div style='float:right;'>[#ListToolbar#]</div>
				</div>
					[#ListTable#]
				<div style='margin-left:25px;'>[#ListPagerBottom#]</div>");
	        
	        return FALSE;
		}
	}
	
	
	/**
	 * Ако класа е за външен изглед, то туулбара се премества по средата
	 */
	static function on_BeforeRenderListToolbar ($mvc, &$tpl, $data)
	{
		if(Mode::is('wrapper', 'cms_tpl_Page')) {
			
			if($mvc->haveRightFor('add')){
				$tpl = new ET('[#SingleToolbar#]');
				$tpl->replace($mvc->renderSingleToolbar($data), 'SingleToolbar');
			}
			
			return FALSE;
		}
	}
	
	
	/**
	 * Ако статията е за външен изглед генерираме новият изглед на страницата
	 */
	static function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data)
	{
		/* Ако класа е за външен изглед то се генерира нова обвивка, както и се добавят категориите
		от страни */
		if(Mode::is('wrapper', 'cms_tpl_Page')) {
			
			// Нов темплейт, който ще помести категориите и статиите за външния изглед
			$res = new ET("<div style='width:140%;margin-left:auto,margin-right:auto;'>
					<div style='float:left;width:28%;margin-top:0%;margin-left:3%'>
					<ul id='categories-menu' style='list-style:none;padding-left:5px'>
							<li id='cat_header'>Категории</li>
					<li>[#search#]</li>
					<li class='categories_list'><a href='/blog_Articles/list'>Всички</a></li>
							[#category#]
					</ul></div>
					<div style='float:right;width:68%'>[#PAGE_CONTENT#]</div>
					</div>");
			
			/* Извлича имената на всички категории, и ги представя във вида на списък, с линкове, които
			ще заредят само статиите на съоветната категория */
			$query = blog_Categories::getQuery();
			
			foreach($query->fetchAll() as $cat) {
				$catTitle = blog_Categories::getVerbal($cat, 'title');
				$catLink = ht::createLink($catTitle, array('blog_Articles', 'list', 'cat'  => $cat->id));
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
	
	
	/**
	 * Преди всеки екшън слагаме wrapper-a  по подразбиране
	 */
	static function on_BeforeAction($mvc, $action)
	{
		Mode::set('wrapper', blog_Articles::BLOG_OUTER_WRAPPER);
	}
	
	
	/**
	 * Ако блога е за външен достъп то заменяме шаблона на формата за добавяне
	 * на нови статии 
	 */
	static function on_AfterPrepareEditForm($mvc, $res, $data){
		
		// Ако блога е за външен изглед, модифицираме формата за добавяне на коментар
		if(blog_Articles::haveRightFor('show')){
			
		$data->form->setField('title', 'width=80%');
		$data->form->setField('author', 'width=30%');
		$data->form->setField('body', 'height=260px');
		
		$data->form->fieldsLayout = new ET("
				<ul style='margin-top:-4px;border:0px solid green;min-height:300px;width:90%;list-style:none;padding-left:0px;margin-left:auto;margin-right:auto'>
					<li>&nbsp;</li>
				<li style='margin-top:8px'><span style=''>Заглавие:</span><span style='margin-left:36px;'>[#title#]</span></li>
					<li style='margin-top:8px'><span style=''>Автор:</span><span style='margin-left:61px;'>[#author#]</span></li>
					<li style='margin-top:8px'>[#body#]</li>
					<li style='margin-top:8px'><span style=''>Категория:</span><span style='width:30%;height:50px'>[#cat#]</span></li>
					<li style='margin-top:8px'><span style=''>Състояние:</span><span style='margin-left:23%;'>[#state#]</span></li>
					<li style='margin-top:8px'><span style=''>Статус на коментарите:</span>
				<span style='margin-left:33px;'>[#commentsMode#]</span></li>
					<li style='margin-top:8px'><span style=''>Качи файл ( опционално ):</span>
				<span style='margin-left:14px;'>[#fileHnd#]</span></li>
				</ul>");
		
		$data->form->layout = new ET(
						"<span style='text-align:center;'><h2>Добавяне на нова статия</h2></span>".
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
				);
		}
	}
	
	
	/**
	 *  Добавяне на и промяна на изгледа на филтър форма за търсене на статия 
	 *  по нейното заглавие
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Показване на полето, по което списъка ще се филтрира
		$data->listFilter->showFields = 'title';
		$data->listFilter->toolbar->addSbBtn('Търси', 'default', 'id=filter, class=btn-filter');
		
		// Нов шаблон за показване на формата
		$data->listFilter->layout = new ET(
				"<form  id='autoElement1' method=\"[#FORM_METHOD#]\" action='[#FORM_ACTION#]'>
				<table border='0' id='blog-article-filter' cellpadding='6px' cellspacing='0px'><tr><td>[#FORM_FIELDS#]</td><td>[#FORM_TOOLBAR#]</td></tr></table>
				</form>");
	}
	
	
	/**
	 *  Екшън за смяна на състоянието на коментарите на статията, той е достъпен
	 *  единствено ако блога е за вътрешен изглед ( от act_Single ), така външни 
	 *  потребители немогат
	 *  да заключват статията за коментиране.
	 */
	static function act_ChangeMode() {
		
		if(blog_Articles::haveRightFor('changeMode')){
		
			// Извлича текущата статия, на която ще се сменя мода
			$article = blog_Articles::fetch(Request::get('articleId', 'int'));
			
			// Старото състояние на коментарите на статията
			$oldMode = $article->commentsMode;
			
			// Новото състояние, което е избрано след изпращането на формата
			$newMode = Request::get('commentsMode', 'varchar');
			
			// Ако старото и новото състояние се различават то променяме записа
			if($oldMode !=  $newMode){
				$article->commentsMode = $newMode;
				
				// Запазваме променената статия в базата
				self::save($article);
				
				/* Пренасочваме потребителя след успешна промяна към Едининия изглед
				на статията */
				Redirect(array('blog_Articles', 'single', $article->id));
			}
			else {
				
				/* Ако не е променен статусът на коментарите, се връщаме на единичната
				страница */
				Redirect(array('blog_Articles', 'single', $article->id));
			}
		}
	}
	
	
	/**
	 *  Създава форма за промяна на състоянието на коментарите на статията
	 *  и  рендира форма за добавяне на коментари, ако те са разрешени
	 */
	static function on_AfterRenderSingleLayout ($mvc, &$tpl, &$data) {
		
		// Взима текущата статия
		$articleId = Request::get('id', 'int');
		$article = blog_Articles::fetch($articleId);
		
		// Ако потребителя има права да променя състоянието на статията
		if($mvc->haveRightFor('changeMode')){
			$id = Request::get('id', 'int');
			if(isset($id)){
			
				// Създава форма която се състои от селект поле и бутон за събмит
				$modeForm = cls::get('core_Form');
				$modeForm->layout = new ET('[#FORM_FIELDS#]');
				$modeForm->fieldsLayout = new ET('
						<form method = \"[#FORM_METHOD#]\" action="[#FORM_ACTION#]"><table cellpadding="0" cellspacing="0"><tr>
						<td>[#commentsMode#][#FORM_HIDDEN#]</td><td>[#FORM_TOOLBAR#]</td>
						</tr></table>
						</form>');
				$modeForm->FLD('commentsMode', 'enum(enabled=Разрешени,disabled=Забранени,grade=С оценка)', 'caption=Статус на коментарите');
				
				// Избраната стойност е тази която е текуща за статията
				$modeForm->setDefaults(array('commentsMode' => $article->commentsMode));
				$modeForm->setHidden('articleId', $articleId);
				$modeForm->setAction(array('Ctr' => $mvc->className,
						'Act' => 'ChangeMode'));
				$modeForm->toolbar->addSbBtn('Запис', array('class' => 'btn-save'));
				
				// Добавяне на формата в изгледа
				$tpl->replace($modeForm->renderHtml(), 'changemode');
			}
		}
		
		/* Ако има зададена форма в $data, ако статията не може да бъде коментирана
		то $data->form не е сетната */
		if(isset($data->form)){
			
		$data->form->fieldsLayout = new ET("
						<ul id='comment-list-add' style='height:230px;list-style:none;padding-left:5px;padding-top:5px;background: rgb(229, 229, 229)'>
							<li style='box-shadow:none;-webkit-box-shadow: none;-moz-box-shadow: none;margin-top:5px;'>
								<label for=author'' style='margin-left:20px;margin-top:0px;font:weight:bold'>От:</label>
								<span style='margin-left:30px;'>[#author#]</span>
							</li>
							<li style='box-shadow:none;-webkit-box-shadow: none;-moz-box-shadow: none;margin-top:5px;'>
								<label for='email' >Имейл:</label>
								<span style='margin-left:21px;'>[#email#] </span>
							</li>
							<li style='box-shadow:none;-webkit-box-shadow: none;-moz-box-shadow: none;margin-top:5px;>
								[#comment#]
							</li>");
		
		// Ако статията може да бъде оценявана то показваме бутоните за оценка
		if($article->commentsMode == 'grade' ){
			$data->form->setField('grade', 'input=radio');
			$data->form->fieldsLayout->append(new ET("<li>[#grade#]</li>"));
		}
		
		// Задаваме изглед за полетата на формата
		$data->form->fieldsLayout->append(new ET("</ul>"));
		
		// Задаваме изглед на формата
		$data->form->layout = new ET(
				"<form style='margin-left:auto;margin-right:auto;width:74%' id='' method=\"[#FORM_METHOD#]\" action='[#FORM_ACTION#]' <!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
				"\n<div class='clearfix21 horizontal' style='margin-top:5px;'>" .
				"<!--ET_BEGIN FORM_ERROR--><div class=\"formError\">[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->" .
				"<!--ET_BEGIN FORM_INFO--><div class=\"formInfo\">[#FORM_INFO#]</div><!--ET_END FORM_INFO-->" .
				"<!--ET_BEGIN FORM_HIDDEN-->[#FORM_HIDDEN#]<!--ET_END FORM_HIDDEN-->" ."\n" .
				"<!--ET_BEGIN FORM_FIELDS--><div class='formFields'>[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->\n" .
				"<!--ET_BEGIN FORM_TOOLBAR--><div class='formToolbar'>[#FORM_TOOLBAR#]</div><!--ET_END FORM_TOOLBAR-->\n" .
				"</div></form>\n" .
				"\n");
		
		// Рендираме формата и я поставяме в шаблона
		$tpl->replace($data->form->renderHtml(), 'COMMENTS_FORM');
		}
	}
	
	
	/**
	 *  Нов Екшън за преглеждане на единична статия и добавяне на форма за 
	 *  коментиране
	 */
	 function act_Show()
	{
		// Очакваме да има зададено "id" на статията
		expect($id = Request::get('id', 'int'));
		
		// Създаваме празен $data обект
		$data = new stdClass();
		
		// Имаме ли въобще права за Show изглед?			
		$this->requireRightFor('show');
		
		// Трябва да има $rec за това $id
		expect($data->rec = $this->fetch($id));
			
	$this->requireRightFor('show', $data->rec);
		
		// Подготвяме данните за единичния изглед
		$this->prepareSingle($data);
		
		// Рендираме изгледа
		$tpl = $this->renderSingle($data);
		
		// Опаковаме изгледа
		$tpl = $this->renderWrapping($tpl, $data);
		
		// Записваме, че потребителя е разглеждал този списък
		$this->log('show: ' . ($data->log ? $data->log : tr($data->title)), $id);
		
		return $tpl;
	}
	 

	/**
	  * Преди да подготвим информацията за Единичния изглед, създаваме форма за
	  * добавяне на коментари, ако статията може да бъде коментирана
	  */
	 function on_BeforePrepareSingle($mvc, $res, &$data)
	{
		/*  Ако статията е предназначена за външен достъп то ще зададем на 
		 $data->form форма, която ще служи за добавяне на коментари, ако статията 
		 е рпедназначена за вътрешен изглед то под коментарите ще има бутон за 
		 добавяне на нов коментар $data->form не е сетната само когато или статията
		 неможе да бъде коментирана  или когато сме във вътрешен изглед*/
		if(blog_Articles::haveRightFor('show')) {
		
			//  Проверяваме дали статията може да бъде коментирана
			if(blog_Comments::haveRightFor('add', $data->rec->commentsMode)) {
				
				// Създаваме форма с подходящите полета за добавяне на коментар
				$form = cls::get('core_Form');
				$form->FLD('author', 'nick', 'caption=Автор');
				$form->FLD('email', 'email', 'caption=Имейл,width=30%');
				$form->FLD('comment', 'richtext', 'caption=Коментар,height=70px');
				$form->FLD('grade',
						 'enum(1=Лоша,2=Слаба,3=Средна,4=Добра,5=Мн. Добра,6=Отлична)',
						 'caption=Оценете статията,maxRadio=6,input=none,columns=6');
				$form->FLD('botCheck', 'int', 'input=hidden');
				$form->FLD('articleId', 'int', 'input=hidden');
				$form->FLD('ret_url', 'varchar', 'input=hidden');
				$curUrl = getCurrentUrl();
				$curUrl['#'] = 'top';
				$form->setHidden('ret_url', toUrl($curUrl, 'local'));
				
				// Задаваме дефолт стойности на скритите и полета
				$form->setDefaults(array(
						'articleId' => $data->rec->id,
						'ret_url' => TRUE
				));
				
				// Добавяме екшъна на формата
				$form->setAction(array(
									'blog_Articles',
									'show',
						 			'id' => $data->rec->id)
								);
				
				// Добавяме бутона за записване на коментара
				$form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
				
				// Въвеждаме данните в формата ако те присъстват в заявката
				$form->input();
				
				// Проверяваме дали е събмитната формата
				if ($form->isSubmitted()) {
					
					/* Ако формата е попълнена успешно и няма грешки в полетата, 
					  запазваме коментара в базата */
					blog_Comments::save($form->rec);
					
					/* Изпразваме рекорда да няма въведенис тойности в надоло 
					 генерираната форма */
					unset($form->rec->comment);
					unset($form->rec->author);
					unset($form->rec->email);
					unset($form->rec->grade);
				}
				
				// Задаваме формата в $data
				$data->form = $form;
			}
		}
	}
	
	
	/**
	 *  Взависимост от това дали блога е за външен или вътрешен достъп, то ние
	 *  определяме, кой ще е главния метод за единичен изглед Show  или Single
	 */
	static function on_BeforeGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
	{
		/* Ако статията е за външен изглед, то заменяма Single екшъна със Show, той
		 е нов метод който изглежда като първия но с тази разлика че извършва в себе
		 си добавянето на коментар към статията а не чрез метода на blog_Comments */
		if(blog_Articles::BLOG_OUTER_WRAPPER == 'cms_tpl_Page') {
			$mvc->canShow = 'every_one';
			$mvc->canSingle = 'no_one';
			$mvc->canChangemode = 'no_one';
			$mvc->canChangestate = 'no_one';
			$mvc->canAdd = 'no_one';
			$mvc->canDelete = 'no_one';
			$mvc->canEdit = 'no_one';
		}
		
		// Ако блога е за вътрешен изглед, ние оставяме екшъна да е достъпен а го забраняваме
		else {
			$mvc->canShow = 'no_one';
			$mvc->canSingle = 'every_one';
			$mvc->canChangemode = 'every_one';
		}
	}
	
	
	/**
	 *  Заменяме принт бутона да пренасочва към текущия екшън, Ако блога е за външен
	 *  изглед пренасочва към принт режи в Show  екшъна, в противен случай -  към
	 *  принт режим в Single екшъна
	 */
	function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
	{
	    // Взимаме текущият адрес
		$url = getCurrentUrl();
		
		// Задаваме че страницата е в принт режим
        $url['Printing'] = 'yes';
        
        // Поставяме бутона за принтиране на страницата в single туулбара на страницата
        $data->toolbar->addBtn('Печат', $url,
            'id=btnPrint,target=_blank,class=btn-print');
	}
}
