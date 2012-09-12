<?php
class blog_Comment extends core_Detail {

	var $title = 'Коментари';
	var $loadList = ' plg_RowTools,plg_Created, plg_Modified, plg_State2';
	var $listFields=' commentAuthor, commentTitle, comment, gender, grade,createdOn,modifiedOn, state';
	var $masterKey = 'articleId';
	
	function description()
	{
		$this->FLD('articleId', 'key(mvc=blog_Article, select=title)', 'caption=Тема, input=hidden, silent');
		$this->FLD('commentTitle', 'varchar(50)', 'caption=Заглавие,width=100%');
		$this->FLD('commentAuthor', 'varchar(50)', 'caption=Автор,width=100%');
		$this->FLD('comment','richtext','caption=Коментар');
		$this->FLD('gender','enum(1=Мъж,2=Жена,3=Друг)','caption=Пол');
		$this->FLD('grade','enum(1=Лоша,2=Слаба,3=Средна,4=Добра,5=Мн. Добра,6=Отлична)','caption=Оценете статията,columns=2,maxRadio=6');
	}
	
	function on_BeforeRenderDetail($mvc, $res, &$data)
	{
		
		if(isset($data->rows))
		{
			//Създава се шаблон за новото таблично представяне на коментарите
			
			$html ='
					<table border="0" width="695px" class="blog-comment-new-table" cellpadding="0" cellspacing="0">
						<tr class="comment-table-row" height="25px" >
							<td class="comment-header" >
								<span class="comment-title">[#commentTitle#]</span>
								<span class="comment-author"> от:&nbsp;&nbsp;[#commentAuthor#]</span>
								<span class="createdon">на:&nbsp;&nbsp;&nbsp;[#createdOn#]</span>
								<!--ET_BEGIN grade--><span class="comment-grade">оценил с:&nbsp;&nbsp;[#grade#]</span><!--ET_END grade-->
							</td>
						</tr>
						<tr class="comment-table-row" >
							<td class="comment-body" >
								[#comment#]
							</td>
						</tr>
						<tr class="comment-table-row" >
							<td class="comment-tools" style="padding-top:8px;">
								[#tools#]
							</td>
						</tr>
					</table>
					';
			
			$rows=array();
			
			/* Обхождат се всички коментара и техните данни се заместват в новия шаблон
			   В празен масив $rows се записва шаблон, който съдържа новото таблично представяне на всеки коментар
			   на база досегашните данни в $data->rows */
			
			
			foreach ($data->rows as $row)
			{
				$sClass=new stdClass();
				$tpl=new ET($html);
				$tpl->replace($row->commentTitle, 'commentTitle');
				$tpl->replace($row->commentAuthor, 'commentAuthor');
				$tpl->replace($row->comment, 'comment');
				$tpl->replace($row->createdOn, 'createdOn');
				$tpl->replace($row->grade, 'grade');
				$tpl->replace($row->id, 'tools');
				$sClass->id=$row->id;
				$sClass->commentTitle=$tpl;
				$rows[]=$sClass;
				
			}
			
			// Оригиналните $data->rows  се заместват с новите $rows
			
		    $data->rows = $rows;
		    
		    /* Избират се да се показват в таблицата две колонки едната е на плъгина 
		       а другата съдържа комбинираната информация за всеки коментар помествена в посочения шаблон
		       Така вместо таблица с 5+ колони се показва таблица с две колони, визуалния изглед на таблицата
		       с детайлите е променен в css файла */
		    
		    $data->listFields='commentTitle';
		}
	}
}