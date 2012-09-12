<?php
class blog_Article extends core_Master {
	
	var $title = 'Тестов Блог';
	const IMG_BUCKET = 'productsImages';
	var $loadList = 'plg_RowTools,plg_Created, plg_Modified,plg_State2,plg_Printing';
	var $listFields=' title,cat,body,author,createdOn,modifiedOn,state,comments,average'; //,created_on,created_by,modified_on
	var $details = 'blog_Comment';
	var $singleLayoutFile = 'blog/tpl/SingleArticle.shtml';
	//var $layoutFile='blog/tpl/ListArticle.shtml';
	
	function description()
	{
		$this->FLD('title','varchar(190)','caption=Тема на статията,notNull');
		$this->FLD('author','varchar(40)','caption=Автор,notNull');
		$this->FLD('body','richtext','caption=Текст');
		$this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Изтрит)', 'caption=Статус');
		$this->FLD('fileHnd', 'fileman_FileType(bucket=' . self::IMG_BUCKET . ')', 'caption=Качете Файл');
		$this->FLD('cat', 'keylist(mvc=blog_Categories,select=title)', 'caption=Категории');
		$this->setDbUnique('title');
	}
	
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		// Създава се линк за сортиране на статиите по избраната категория
		
		$row->cat = ht::createLink($row->cat, array('blog_Article', 'list', 'cat' => $rec->id));
		
		
		// Проверява дали екшъна е list ако да, текста на статията бива съкратен за представяне в изгледа
		// ако екшъна не е list не се правят промени по дължината на текста
		
		if( Request::get('ret_url', 'varchar')=='')
		{
			$singleUrl = toUrl(array(
					$mvc,
					'single',
					'id' => $rec->id,
					'ret_url' => TRUE
			));
			
			$short_body=strip_tags(substr($row->body->content, 0,420));
			
			// Добавя бутон "още" който отваря единичния изглед на статията
			
			$short_body.="....&nbsp;&nbsp;".ht::createLink("[още]", $singleUrl)->content;
			$row->body->content=$short_body;
		
		
		
		// Преброява всички коментари към всяка статия и ги представя в таблицата
		
		$comment_number=cls::get('blog_Comment')->count("article_id=".$rec->id);
		
		// Намира средната оценка на статията като средно-артиметично на броя на всички поставени оценки
		
		if($comment_number!=0) {
			$query=blog_Comment::getQuery();
			$query->XPR('sumGrades', 'int', 'min(#grade)');
			$query->where("article_id = '".$rec->id."'");
			$average_grade=ceil($query->fetch()->sumGrades / $comment_number );
		}
		else {
			$average_grade="няма";
		}
		
		switch($average_grade) {
			case 1:
				$average_grade="Лоша";
				break;
			case 2:
				$average_grade="Слаба";
				break;
			case 3:
				$average_grade="Средна";
				break;
			case 4:
				$average_grade="Добра";
				break;
			case 5:
				$average_grade="Мн.Добра";
				break;
			case 6:
				$average_grade="Отлична";
				break;
		}
		//bp($average_grade);
		$row->average=$average_grade;
		$row->comments=$comment_number;
		
		/* Полетата на $row се заместват с нов шаблон в който е обединена всичката информация от полетата на 
		 $row, така се изменя облика на таблицата на list изгледа */
		
		$html ='
				<table border="0" class="blog-article-list-table" cellpadding="0" cellspacing="0" width="500">
					<tr>
						<td class="list-table-header" height="56px">
							<span class="article-list-title">[#title#]</span><br>
							<span class="article-list-author">от:&nbsp;&nbsp;[#author#]</span>
							<span class="article-list-createdon">на:&nbsp;&nbsp;[#createdOn#]</span>
						</td>
					</tr>
					<tr>
						<td class="list-table-body" >
							[#body#]
						</td>
					</tr>
					<tr>
						<td class="list-table-footer" style="padding-top:10px">
							<span class="article-list-cat">категории:&nbsp;&nbsp;[#cat#]</span>
							<span class="article-list-commentnumber">[#comments#] &nbsp;&nbsp;коментара</span>
							<span class="article-list-average">Средна оценка: [#average#]</span>
							<span class="article-list-tools">[#tools#]</span>
						</td>
					</tr>
				</table>
				';
		$sClass=new stdClass();
		$tpl=new ET($html);
		$tpl->replace($row->title, 'title');
		$tpl->replace($row->cat, 'cat');
		$tpl->replace($row->author, 'author');
		$tpl->replace($row->body, 'body');
		$tpl->replace($row->createdOn, 'createdOn');
		$tpl->replace($row->comments, 'comments');
		$tpl->replace($row->id, 'tools');
		$tpl->replace($row->average, 'average');
		$sClass->title=$tpl;
		$row=$sClass;
		}
	}
	
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		
		// Проверява дали е избрана определена категория, ако да то статиите се филтрират да показват само тези
		// които отговарят на посочената категория
		
		$cat = Request::get('cat', 'int');
		if(isset($cat))
		{
			$data->query->where("#cat LIKE '%|{$cat}|%'");
		}
	}
	
	static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		// Ненужните колони от таблицата се скриват
		
		
		$data->listFields='title=Статии';
	}
	
	function on_AfterPrepareListTitle($mvc, $data)
	{
		
		// Проверява имали избрана категория
		
		$cat = Request::get('cat', 'int');
		
		if(isset($cat))
		{
			// Ако е избрана се взима заглавието на категорията, което отговаря на посоченото id на категорията
			
			$category=cls::get('blog_Categories')->fetch($cat);
			
			// В заглавието на list  изгледа се поставя името на избраната категория

			$data->title='Статии от категория:&nbsp;&nbsp;&nbsp;&nbsp;'.$category->title;
		}
	}
	
}