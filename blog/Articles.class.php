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
	* Интерфейси, поддържани от този мениджър
	*/
    var $interfaces = 'doc_DocumentIntf';
	
	
	/**
	 * Тип на разрешените файлове за качване
	 */
	const FILE_BUCKET = 'productsFiles';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'doc_DocumentPlg, plg_RowTools, plg_State2, plg_Printing, blog_Wrapper, plg_Search';
	
	
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
	var $canWrite  = 'cms, ceo, admin';
	
	
	/**
	 * Кой може да сменя статуса на статията
	 */
	var $canChangestate='cms, ceo, admin';
	
	
	/**
	 * Кой може да редактира записи
	 */
	var $canEdit='cms, ceo, admin';
	
	
	/**
	 * Кой може да изтрива статии
	 */
	var $canDelete = 'cms, ceo, admin';
	
	
	/**
	 * Кой може да оттегля документа
	 */
	var $canReject='every_one';
	
	
	/**
	 * Кое поле ще съдържа туулбара за единичен изглед, редакция или изтриване на
	 * коментара
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Кой може да разрешава/забранява една статия да бъде коментирана и оценявана
	 */
	var $canChangemode = 'cms, ceo, admin';
	
	
	/**
	 * Кой има достъп до метода Show, за външен достъп до единичния изглед
	 */
	var $canShow = 'every_one';
	
	
	/**
	 * Кой има достъп до метода Browse, за външен достъп до статиите
	 */
	var $canBrowse='every_one';
	
	
	/**
	 * Кой има достъп до метода Single
	 */
	var $canSingle = 'cms, ceo, admin';
	
	
	/**
	 * Кой може да добавя статии
	 */
	var $canComment = 'every_one';
	
	
	/**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'blog/tpl/SingleArticle.shtml';
	
	
	/**
	 * Кой има достъп до бутона за включване/изключване на бутоните за редакция
	 */
	var $canToggle = 'cms, ceo, admin';
	
	
	/**
	 * Обвивка за блога
	 */
	static $articleWrapper = 'page_Internal';
	
	
	/**
	 * Единично заглавие на документа
	 */
	var $singleTitle = 'Блог Статия';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(190)', 'caption=Тема на статията, notNull');
		$this->FLD('author', 'varchar(40)', 'caption=Автор, notNull');
		$this->FLD('body', 'richtext(bucket=' . self::FILE_BUCKET . ')', 'caption=Текст');
		$this->FLD('state', 'enum(active=Активен,closed=Затворен)', 'caption=Видимост');
		$this->FLD('commentsMode', 'enum(enabled=Разрешени,disabled=Забранени,grade=С оценка)', 'caption=Статус на коментарите');
		$this->FLD('cat', 'keylist(mvc=blog_Categories,select=title)', 'caption=Категории');
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Обработка на вербалното представяне на статиите
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields=array())
	{
		// Намираме, кой Екшън за единичен изглед ще присъства в линковете,
		// Ако сме в Browse екшъна то това ще е Show а ако сме в List ще е Single
		if(Request::get('Act') == 'browse') {
			$singleAction = 'show';
		}
		else {
			$singleAction = 'single';
		}
		
		// Взимаме заглавието на статията
		$title = $row->title;
	
		// Създаваме хиперлинк от заглавието, който отваря статията 
		$row->title = ht::createLink($title, array('blog_Articles', $singleAction, $row->id, 'ret_url' => TRUE ))->content;
		
		// Създаваме масив, чийто елементи са вербалните имена на категориите 
		$categories = type_Keylist::toArray($rec->cat);
		
		// Изпразваме досегашното съдържание $row->cat
		$row->cat = '';
		
		// Инстанцираме клас, чрез който ще намерим ид-та на категориите
		$catRec = cls::get('blog_Categories');
		
		// За всяка категория, на която отговаря статията създаваме линк от името
		foreach($categories as $cat)
		{
			// Намираме името на съответната категория
			$title = $catRec->fetchField("#id='" . $cat . "'", 'title');
			
			// Към  $row->cat добавяме линк за филтриране на статията по категория
			$row->cat .= ht::createLink($title, array('blog_Articles', Request::get('Act'), 'cat' => $cat)) ."&nbsp;&nbsp;";
		}
		
		// Изпразваме досегашното съдържание на
		unset($row->tools);
		
		// Създаваме нов шаблон, който ще натрупа новия туулбар
		$tools = new ET("");
		
		if($mvc->haveRightFor('view')){
			// Създаваме елемент, който да покаже изображението за единичен изглед
			$imgView = ht::createElement('img', array('src' => sbf('img/16/view.png', '')));
			
			// Правим иконката като хипервръзка към метода за единичен изглед
			$linkView = ht::createLink($imgView->content, array('blog_Articles', $singleAction, $row->id, 'ret_url' => TRUE));
			
			// Добавяме линка шаблона
			$tools->append($linkView->content."&nbsp;&nbsp;&nbsp;");
		}
		// Ако имаме права да редактираме статии
		if($mvc->haveRightFor('edit',$row)) {
			
			// Създаваме елемент, който да покаже изображението за редакция
			$imgEdit = ht::createElement('img', array('src' => sbf('img/16/edit-icon.png', '')));
			
			// Правим иконката като хипервръзка към метода за редактиране
			$linkEdit = ht::createLink($imgEdit->content, array('blog_Articles', 'edit', $row->id, 'ret_url' => TRUE));
			
			// Добавяме линка към шаблона
			$tools->append($linkEdit->content . "&nbsp;&nbsp;&nbsp;");
		}
		
		// Ако имаме права да изтриваме статии
		if($mvc->haveRightFor('delete')){
			
			// Създаваме елемент, който да покаже изображението за изтриване
			$imgDelete = ht::createElement('img', array('src' => sbf('img/16/delete-icon.png', '')));
			
			// Правим иконката като хипервръзка към метода за зитриване
			$linkDelete = ht::createLink($imgDelete->content, array('blog_Articles', 'delete', $row->id, 'ret_url' => TRUE));
			
			// Добавяме линка към шаблона
			$tools->append($linkDelete->content);
		}
		
		// Добавяме новия шаблон в, $row->tools вместо този рендиран от plg_RowTools
		$row->tools = $tools;
		
		// Проверява дали екшъна е list ако да, текста на статията бива съкратен 
		if(isset ($fields['-list']))
		{
			$singleUrl = array(
					$mvc->className,
					$singleAction,
					'id' => $rec->id,
					'ret_url' => TRUE
			);
			
			// Ако метода е 'browse' то ограничаваме текста на статията до 500 символа иначе се съкращава до 110
			if(Request::get('Act') == 'browse') {
				$num_chars = 500;
			}
			else {
				$num_chars = 110;
			}
			
			// Ако текста на статията е с над 150 символа то той бива съкратен
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
			
				// Намира средната оценка на статията като средно-артиметично на броя на 
				// всички поставени оценки, ако статията позволява да бъде оценена и ако броя на 
				// коментарите е повече от 0 
				if($commentNumber != 0 && $row->commentsMode == 'grade?') {
					$query = blog_Comments::getQuery();
					$query->XPR('sumGrades', 'int', 'min(#grade)');
					$query->where("article_id = '" . $rec->id . "'");
					$averageGrade = ceil($query->fetch()->sumGrades / $commentNumber );
				}
				else {
					
					// Ако коментарите са 0 или статията неможе да бъде оценявана то,тя няма средна оценка  
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
				
				// Добавя към хиперлинка за единичен изглед, котва към секцията с коментарите 
				$singleUrl['#'] = 'top';
				
				// Създаваме текста на хиперлинка, водещ към секцията с коментари
				$commentlink = "Коментари:&nbsp;&nbsp;" . $commentNumber;
				
				// Поставяне на хиперлинка в $row->comments
				$row->comments = ht::createLink($commentlink, $singleUrl);
			}
			else {
				
				// Ако статията е забранена за коментиране то тя няма средна оценка
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
		
		// във вида на списък, или ако няма статии, ще покаже съобщение за липсващи такива 
		if(isset($cat)){
				$data->query->where("#cat LIKE '%|{$cat}|%'");
			}
		
		// Ако е посочено филтриране по име на статията то се показват само онези
		// статии които съдържат в името си ключовата дума (думи) 	
		$searchTitle = $data->listFilter->rec->title;
		
		if($searchTitle){
				$data->query->where("#title LIKE '%{$searchTitle}%'");
			}
		
		// Подреждаме статиите по датата им на публикуане в низходящ ред	
		$data->query->orderBy('createdOn', 'DESC');
		
		// Ако метода е 'browse' показваме само активните статии
		if(Request::get('Act') == 'browse'){
			
			// Показваме само статиите които са активни
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
		
		// Проверяваме имали избрана категория
		if(isset($cat)) {
			
			// Ако е избрана се взима заглавието на категорията, което отговаря на посоченото id 
			$catRec = blog_Categories::fetch($cat);
			$title = blog_Categories::getVerbal($catRec, 'title');
			
			// В заглавието на list  изгледа се поставя името на избраната категория
			$data->title = 'Статии от категория:&nbsp;&nbsp;&nbsp;&nbsp;' . $title;
		}
		
		// ако няма избрана категория, блога няма да има заглавие
		else {
			unset($data->title);
		}
	}
	
	
	/**
	 * Ако блога е за външен достъп то заменяме шаблона на формата за добавяне
	 * на нови статии 
	 */
	static function on_AfterPrepareEditForm($mvc, $res, $data){
		
		// Ако блога е за външен изглед, модифицираме формата за добавяне на коментар
		if(blog_Articles::haveRightFor('edit')){
			
		$data->form->setField('title', 'width=80%');
		$data->form->setField('author', 'width=30%');
		$data->form->setField('body', 'height=260px');
		
		// Модифицираме изгледа на полетата на формата
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
		
		// Задаваме новия шаблон на формата
		$data->form->layout = new ET(
						"<h2>Добавяне на нова статия</h2>".
						"<form style='margin-top:0px;width:50%' id='" .
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
		if(Request::get('Act') == 'browse' || Request::get('Act') == 'show')
		{
			$data->listFilter->setField('title', 'width=160px,caption=');
		}
		
		// Добавяне на туулбар за търсене
		$data->listFilter->toolbar->addSbBtn('Търси', 'default', 'id=filter, class=btn-filter');
		
		// Нов шаблон за показване на формата
		$data->listFilter->layout = new ET(
				"<form  id='autoElement1'  style='width:100px;' method=\"[#FORM_METHOD#]\" 
				action='[#FORM_ACTION#]'>
				<table border='0' id='blog-article-filter' cellpadding='6px' cellspacing='0px'>
				<tr><td>[#FORM_FIELDS#]</td></tr><tr><td >[#FORM_TOOLBAR#]</td></tr></table></form>");
		
		// Задаваме екшъна, към който ще пренасочва формата след нейното изпращане
		$data->listFilter->setAction(array('blog_Articles', 'browse'));
	}
	
	
	/**
	 *  Единствено потребители с роли cms, ceo, admin могат
	 *  да заключват статията за коментиране.
	 */
	/*static function act_ChangeMode() {
		
		// Ако потребителят може да заключва статията за коментиране
		if(blog_Articles::haveRightFor('changeMode')){
		
			// Извлича текущата статия, на която ще се сменя мода
			$article = blog_Articles::fetch(Request::get('articleId', 'int'));
			
			// Старото състояние на коментарите на статията
			$oldMode = $article->commentsMode;
			
			// Новото състояние, което е избрано след изпращането на формата
			$newMode = Request::get('commentsMode', 'varchar');
			
			// Ако старото и новото състояние се различават то променяме записа
			if($oldMode !=  $newMode){
				
				// Задаваме състоянието на статията да отговаря на новото
				$article->commentsMode = $newMode;
				
				// Запазваме променената статия в базата
				self::save($article);
				
				// Пренасочваме потребителя след успешна промяна към Едининия изглед на статията 
				Redirect(array('blog_Articles',  Request::get('singleAct'), $article->id));
			}
			else {
				
				// Ако не е променен статусът на коментарите, се връщаме на единичната страница 
				Redirect(array('blog_Articles', Request::get('singleAct'), $article->id));
			}
		}
	}*/
	
	
	/**
	 *  Създава форма за промяна на състоянието на коментарите на статията
	 *  и  рендира форма за добавяне на коментари, ако те са разрешени
	 */
	/*static function on_AfterRenderSingleLayout ($mvc, &$tpl, &$data) {
		// Взима текущата статия
		/$articleId = $data->rec->id;
		$article = blog_Articles::fetch($articleId);
		
		// Ако потребителя има права да променя състоянието на статията
		if($mvc->haveRightFor('changeMode')){
			
			// Взимаме ид-то на статията
			$id = $data->rec->id;
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
				$url=getCurrentUrl();
				$modeForm->setHidden('singleAct', $url['Act'] );
				$modeForm->setAction(array('Ctr' => $mvc->className,
						'Act' => 'ChangeMode'));
				$modeForm->toolbar->addSbBtn('Запис', array('class' => 'btn-save'));
				
				// Добавяне на формата в изгледа
				$tpl->replace($modeForm->renderHtml(), 'changemode');
			}
		}
	} */
	
	
	/**
	 *  Нов Екшън за преглеждане на единична статия и добавяне на форма за 
	 *  коментиране
	 */
	function act_Show()
	{
		// Поставяме шаблона за външен изглед
		Mode::set('wrapper', 'cms_tpl_Page');
		
		// Очакваме да има зададено "id" на статията
		expect($id = Request::get('id', 'int'));
		
		// Създаваме празен $data обект
		$data = new stdClass();
		$data->query = $this->getQuery();
		
		// Имаме ли въобще права за Show изглед?			
		$this->requireRightFor('show');
		
		// Трябва да има $rec за това $id
		expect($data->rec = $this->fetch($id));
			
		$this->requireRightFor('show', $data->rec);
		
		// Проверяваме дали статията може да бъде коментирана
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
			$form->FLD('state','varchar', 'input=hidden');
			$curUrl = getCurrentUrl();
			$curUrl['#'] = 'top';
			$form->setHidden('ret_url', toUrl($curUrl, 'local'));
			$form->setHidden('state', 'closed');
			
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
					
				// Ако формата е попълнена успешно запазваме коментара в базата 
				blog_Comments::save($form->rec);
					
				// Изпразваме записа да няма въведени стойности в надоло
				// генерираната форма 
				unset($form->rec->comment);
				unset($form->rec->author);
				unset($form->rec->email);
				unset($form->rec->grade);
			}
		
			// Задаваме формата в $data
			$data->form = $form;
		}
		
		// Подготвяме данните за единичния изглед
		$this->prepareSingle($data);
		
		// Подготвяме лист филтъра
		$this->prepareListFilter($data);
		
		// Рендираме единичен изглед специфичен за Show по аналогия на renderSingle
		$tpl = $this->renderShowSingle($data);
		
		// Рендираме формата за добавяне на коментари, която се показва в Show метода
		$tpl = $this->renderShowCommentForm($data, $tpl);
		
		// Опаковаме изгледа
		$tpl = $this->renderOuterWrapping($this, $tpl, $data);
		
		// Записваме, че потребителя е разглеждал този списък
		$this->log('show: ' . ($data->log ? $data->log : tr($data->title)), $id);
		
		// Връщаме вече рендирания шаблон
		return $tpl;
	}
	
	
	/**
	 * Рендираме формата за коментари, показваща се под статията от метода 'show' 
	 */
	function renderShowCommentForm($data, $tpl)
	{
		// Ако има зададена форма в $data, ако статията не може да бъде коментирана
		// то $data->form не е сетната
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
		
		// Връщаме шаблона
		return $tpl;
	}
	
	
	/**
	 *  Нов метод имитиращ renderSingle, но рендирайки детайлите в нов дизайн с новия 
	 *  метод на  blog_Comments - renderShowDetail, който се извиква вместо наследения
	 *  renderDetail от core_Detail
	 */
	function renderShowSingle($data, $tpl = NULL)
	{
		// Рендираме общия лейаут
		if(!$tpl) {
			$tpl = $this->renderSingleLayout($data);
		}
		
		// Рендираме заглавието
		$data->row->SingleTitle = $this->renderSingleTitle($data);
	
		// Рендираме лентата с инструменти
		$data->row->SingleToolbar = $this->renderSingleToolbar($data);
	
		// Поставяме данните от реда
		$tpl->placeObject($data->row);
		
		// Рендираме коментарите в нов темплейт с новия метод renderShowDetail, който 
		// се изпозлва вместо renderDetail
		$commentTpl = $this->blog_Comments->renderShowDetail($data->blog_Comments);
		
		// Заместваме шаблона с коментарите в шаблона за единичен изглед
		$tpl->append($commentTpl, 'DETAILS');
		
		// Рендираме лист филтъра
		$tpl->append($this->renderListFilter($data), 'ListFilter');
		
		
		// Връщаме шаблона за единичен изглед
		return $tpl;
	}
	
	
	/**
	 *  Екшън еквивалент  на list който е за външен достъп до блога и генерира 
	 *  дизайна на външния вид
	 */
	function act_Browse() {
		
		// Задаваме Обвивката за външен изглед
		Mode::set('wrapper', 'cms_tpl_Page');
		
		if(Request::get('Print')) {
			Mode::set('printing');
		}
		
		// Проверяваме дали потребителя може да вижда списък с тези записи
		$this->requireRightFor('browse');
		
		// Създаваме обекта $data
		$data = new stdClass();
		$data->action = 'browse';
		
		// Създаваме заявката
		$data->query = $this->getQuery();
		
		// Подготвяме полетата за показване
		$this->prepareListFields($data);
		
		// Подготвяме формата за филтриране
		$this->prepareListFilter($data);
		
		// Подготвяме навигацията по страници
		$this->prepareListPager($data);
		
		// Подготвяме записите за таблицата
		$this->prepareListRecs($data);
		
		// Подготвяме редовете на таблицата
		$this->prepareListRows($data);
		
		// Подготвяме заглавието на таблицата
		$this->prepareListTitle($data);
		
		// Подготвяме лентата с инструменти
		$this->prepareListToolbar($data);
		
		// За да рендираме изгледа за външен достъп използваме новия метод renderBrowseList
		$tpl=$this->renderBrowseList($this,$data);
		
		// Опаковаме изгледа
		$tpl = $this->renderOuterWrapping($this,$tpl, $data);
		
		// Записваме, че потребителя е разглеждал този списък
		$this->log('List: ' . ($data->log ? $data->log : tr($data->title)));
		
		// Връщаме готовия шаблон
		return $tpl;
	}
	
	
	/**
	 * Нов екшън, който рендира листовия списък на статиите за външен достъп, Той връща 
	 * нов темплейт, който представя таблицата в подходящия нов дизайн, създаден е по
	 * аналогия на renderList  с заменени методи които да рендират в новия изглед
	 */
	function renderBrowseList($mvc, $data){
		
		// Рендираме новия листов изглед
		$tpl = $mvc->renderBrowseListLayout($mvc, $data);
		
		// Рендираме заглавието на списъка
		$tpl->append($mvc->renderListTitle($data), 'ListTitle');
		
		// Попълваме формата-филтър
		$tpl->append($mvc->renderListFilter($data), 'ListFilter');
		
		// Попълваме обобщената информация
		$tpl->append($mvc->renderListSummary($data), 'ListSummary');
		
		// Попълваме горния страньор
		$tpl->append($mvc->renderListPager($data), 'ListPagerTop');
		
		// Попълваме долния страньор
		$tpl->append($mvc->renderListPager($data), 'ListPagerBottom');
		
		// Рендираме таблицата с резултатите
		$tpl->append($mvc->renderBrowseListTable($mvc, $data), 'ListTable');
		
		// Рендираме новия туулбар
		$tpl->append($mvc->renderBrowseListToolbar($mvc, $data), 'ListToolbar');
		
		
		// Връщаме вече готовия шаблон
		return $tpl;
	}
	
	
	/**
	 * Нов метод, който рендира нов списъчен изглед подходящ за външен достъп, така
	 * рендирането на изгледа използва този метод вместо наследения renderListLayout
	 */
	function renderBrowseListLayout($mvc, $data){
		
		// Шаблон за нов списъчен изглед
		$res = new ET("
				[#ListTitle#]
				<div class='listTopContainer'>
				
				[#ListSummary#]
				</div>
				<div style='border:0px solid red;width:690px'>
					<div style='float:left'>[#ListPagerTop#]</div>
					<div style='float:right;'>[#ListToolbar#]</div>
				</div>
					[#ListTable#]
				<div style='margin-left:25px;'>[#ListPagerBottom#]</div>");
		
		// Връщаме шаблона
		return $res;
	}
	
	
	/**
	 * Нов метод за рендиране на таблицата със статиите от метода Browse, той се
	 * използва вместо наследения renderListTable
	 */
	function renderBrowseListTable($mvc, $data){
		
		$tpl = new ET("[#ARTICLES#]");
		
		// Тази променлива ще представлява шаблон, който ще натрупа всички статии
		$rowList = '';
		
		//  Ако има налични статии ги показваме в лист таблицата
		if(isset($data->rows)) {
				
			// Взимаме съдържанието на шаблона за представяне на статиите
			$rowList = new ET(getFileContent('blog/tpl/ArticleListElement.shtml'));
				
			// За всеки запис добавяме статията като нов елемент на списъка
			foreach($data->rows as $row) {
		
				// Взимаме Блока, който ще използваме за шаблон
				$articleLI = $rowList->getBlock("ARTICLE_LI");
				
				// Заместваме в шаблона плейсхолдърите
				$articleLI->replace($row->title, 'TITLE');
				$articleLI->replace($row->cat, 'CAT');
				$articleLI->replace($row->author, 'AUTHOR');
				$articleLI->replace($row->body, 'BODY');
				$articleLI->replace($row->createdOn, 'CREATEDON');
				$articleLI->replace($row->comments, 'COMMENTS');
				
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
		
		// Връщаме новия шаблон
		return $tpl;
	}
	
	
	/**
	 * Нов метод за рендиране на туулбара към списъка
	 */
	function renderBrowseListToolbar($mvc, $data)
	{
		// Създаваме нов празен шаблон
		$tpl = new ET('');
		
		// Добавяме плейсхолдър за туулбара
		$tpl->append(new ET('[#SingleToolbar#]'));https://www.facebook.com/
			
		// рендираме бутона за показване на уредите за редакция, ако имаме права
		//$mvc->prepareToolModeBtn($data);
			
		// Рендираме туулбара и го заместваме в шаблона
		$tpl->replace($mvc->renderSingleToolbar($data), 'SingleToolbar');
		
		
		// Връщаме новия шаблон
		return $tpl;
	}
	
	
	/**
	 * Нов метод който се извиква от Browse, за рендиране на обвивката. Той се 
	 * използва да рендира обвивката подходяща за външен изглед вместо тази
	 * рендирана от наследения метод renderWrapping
	 */
	function renderOuterWrapping($mvc, $tpl, $data)
	{
		// Нов темплейт, който ще помести категориите и статиите за външния изглед
		$res = new ET("<div style='width:140%;margin-left:auto,margin-right:auto;'>
					<div style='float:left;width:28%;margin-top:0%;margin-left:3%'>
					[#CATEGORIES#]<br>
					[#ListFilter#]
					</div>
					<div style='float:right;width:68%'>[#PAGE_CONTENT#]</div>
					</div>");
			
		// Извличане на масив съдържащ имената на всички категории с техните ид-та
		$catData = array();
		
		$allCategories = blog_Categories::prepareNavigation($catData);
		
		// Рендиране на шаблона в който категориите ще бъдат поставени
		$categoryTpl = blog_Categories::renderNavigation($allCategories);
			
		// Добавяне на Менюто с категориите в Обвивката
		$res->replace($categoryTpl, 'CATEGORIES');
		
		// Новата информация се помества в темплейта
		$res->replace($tpl, 'PAGE_CONTENT');
		
		
		// Връщаме новия шаблон
		return $res;
	}
	
	
	/**
	 *  Заменяме принт бутона да пренасочва към текущия екшън, Ако блога е за
	 *  външен изглед пренасочва към принт режи в Show  екшъна, в противен случай
	 *  -  към принт режим в Single екшъна
	 */
	function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
	{
	    // Взимаме текущият адрес
		$url = getCurrentUrl();
		
		// Задаваме че страницата е в принт режим
        $url['Printing'] = 'yes';
        
        // Поставяме бутона за принтиране на страницата в single туулбара 
        $data->toolbar->addBtn('Печат', $url,
            'id=btnPrint,target=_blank,class=btn-print');

        // Рендираме бутона за смяна на режима за редактиране, ако имаме права
        $this->prepareToolModeBtn($data);
    }
	
	
	/**
	 *  Добавяне на Бутон, който изключва или включва опциите за редактиране, изтриване,
	 *   добавяне etc
	 */
	function prepareToolModeBtn($data){
		
		// Ако имаме права за достъп до бутона за преминаване/излизане  в/от работилницата
		if($this->haveRightFor('toggle')) {
			
			// Кой екшън е зададен в заявката
		 	$uAct=Request::get('Act');
		 	
		 	// Ако екшъна е шоу пренасочваме към вътрешния изглед на статията като документ
			if($uAct == 'show') {
				$url=array('doc_Containers','list','threadId' =>$data->rec->threadId, 'docId'=>$data->rec->id);
				
				// Име на бутона
				$btnName='Работилница';
			}
			
			// Ако сме в списъчен изглед то пренасочваме към вътрешния изглед
			elseif($uAct == 'list'){
				$url=array('blog_Articles', 'show', 'id' => $data->rec->id);
				
				// Име на бутона
				$btnName='Витрина';
			}
			
		 	// Бутона за преминаване във  работилницата и за излизане от нея
			$data->toolbar->addBtn($btnName, $url);
			/*
			// Взимаме Текущия адрес
			$url = getCurrentUrl();
		 	
			// Извличаме дали сме в режим за показване на иснтрументите
			$toolMode = Request::get('tools');
		
			// Ако сме в режим за показване
	        if(isset($toolMode)){
	        	
	        	// ънсетваме режима за показваме и добавяме бутон към туулбара
	        	unset($url['tools']);
		        $data->toolbar->addBtn('Инструментите', $url);
	        }
	        else {
	        	
	        	// Посочваме че ще сме в режим на показване на бутоните
	        	$url['tools'] = 'on';
	        	
	        	// Добавяме бутона към туулбара
	        	$data->toolbar->addBtn('Инструментите', $url);
	        }
	        */
		 }
		   
	    // Връщаме $data
        return $data;
	}
	
	
	/**
	 *  Ако сме в работилницата то имат права потребителите с правилните роли
	 */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action)
    {
   		// Намираме с кой контролер сме
    	$ctr = Request::get('Ctr');
    	
    	// Ако сме в работилницата 
   		if($ctr == 'doc_Containers' && $action != 'toggle') {
   			$res='cms, ceo, admin';
   		}
   		
   		// Ако сме извън нея и екшъните за който се проверява не са toggle & show
   		elseif($ctr == 'blog_Articles' && $action != 'toggle' && $action != 'show') {
   			$res='no_one';
   		}
    	/*
    	// Извличаме състоянието на tools
    	$mode = Request::get('tools');
    	
    	// Взимаме екшъна от урл-то
   		$urlAction = Request::get('Act');
   		$ctr=Request::get('Ctr');
   		if($action=='reject'){
   			$res='cms, ceo, admin';
   		}
   		
   		// Ако екшъна на урл-то е browse или show добавяме модифицираме правата на екшъна за който
   		// се проверяват
   		if($urlAction == 'browse' || $urlAction == 'show' || $ctr =='doc_Containers'){
   			
   			// Ако е сетнато tools и екшъна не е browse или show то връщаме 
   			if(isset($mode) && $action != 'browse' && $action != 'show' && $action != 'toggle')
   			{
   				$res = 'every_one';
   			}
   			
   			// В противен случай връщаме
   			elseif(!isset($mode) && $action != 'browse' && $action != 'show' && $action != 'toggle') {
   				$res = 'no_one';
   				
   			}
   		}
   		else {
   			$res = 'cms, ceo, admin';
   		} 
   		*/
   	 }
	
	
	/**
	 *  Екшън за периодично изпълнение, който заключва статиите некоментирани
	 *  повече от определен брой дни(  дефиниран като константа, в Сетъп файла )
	 */
	function cron_LockOldArticles() {
		
		// Изчличаме заявката за всички статии
		$queryArt = blog_Articles::getQuery();
		
		// Извличаме масив с всички статии
		$allArticles = $queryArt->fetchAll();
		
		// За всяка статия Проверяваме датата на последния и коментар
		foreach($allArticles as $art) {
			
			// Извличаме заявката за коментарите
			$queryCom = blog_Comments::getQuery();
			
			// Избираме тези коментари, принадлежащи на избраната статия
			$queryCom->where("#articleId='" . $art->id . "'");
			
			// Експрешън поле, което ще намери коментарът, който е добавен последно
			$queryCom->XPR('lastComment', 'int', 'max(#createdOn)');
			
			// Извличаме коментара, чиято дата на създаване е най-скорошна
			$lastComment = $queryCom->fetch()->lastComment;
			
			// Вземане на конфигурационните данни за пакета
			$conf = core_Packs::getConfig('blog');
			
			// Намираме дните между сега и датата от добавянето на последния
			// коментар 
			$daysBetween = dt::daysBetween(dt::now(), $lastComment->createdOn);
			
			// Ако статията не е коментирана за последните 5 дни, и не е 
			// заключена, то ние я заключваме 
			if($daysBetween >= $conf->BLOG_MAX_COMMENT_DAYS && $art->commentsMode != 'disabled'){
				
				// Сменяме състоянието и на заключено за коментари
				$art->commentsMode = 'disabled';
				
				// Ъпдейтваме статията в базата данни
				static::save($art);
			}
			
		}
	}
	
	
	/**
	 * Нагласяме крона да изпълнява екшъна LockOldArticles по разписание
	 */
	static function on_AfterSetupMVC($mvc, &$res)
	{
		$res .= "<p><i>Нагласяне на Cron</i></p>";
		
		// Данни за работата на cron
		$rec = new stdClass();
		$rec->systemId = 'LockOldArticles';
		$rec->description = 'Заключване на стари статии';
		$rec->controller = $mvc->className;
		$rec->action = 'LockOldArticles';
		$rec->period = 2;
		$rec->offset = 0;
		$rec->timeLimit = 500;
		
		// Инстанцираме Крона
		$cron = cls::get('core_Cron');
		
		// Добавяме ново действие по разписание към него
		if ($cron->addOnce($rec)) {
			$res .= "<li><font color='green'>Задаване на крон за заключване на статии, некоментирани повече от 5 мин.</font></li>";
		} else {
			$res .= "<li>Отпреди Cron е бил нагласен да заключва статиите.</li>";
		}
	}
	
	
	/**
	 *  Имплементация на интерфейсен метод за достъп до записа на документа
	 */
	function getDocumentRow($id)
	    {
	        $rec = $this->fetch($id);
	        $row = new stdClass();
	        $row->title = $rec->title;
	        $row->authorId = $rec->createdBy;
	        $row->cat=$rec->cat;
	        $row->author = $this->getVerbal($rec, 'createdBy');
	        $row->state = $rec->state;
	        
	        return $row;
	    }
	    
}
