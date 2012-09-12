<?php
class blog_Categories extends core_Master {
	
	
	var $title = 'Категория';
	var $loadList = 'plg_RowTools';
	var $listFields='id, title';
	
	function description()
	{
		$this->FLD('title','varchar(40)','caption=Категория,notNull');
		$this->setDbUnique('title');
	}
	function on_AfterRecToVerbal($mvc, $row, $rec)
	{
		$row->title = ht::createLink($row->title, array('blog_Article', 'list', 'cat' => $rec->id));
	}
}