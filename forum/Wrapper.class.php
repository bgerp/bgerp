<?php

/**
 * Форум - Опаковка
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Wrapper extends plg_ProtoWrapper
{


	/**
	 * Описание на табовете
	 */
	function description()
	{
		$this->TAB('forum_Categories', 'Категории', 'forum,admin');
		$this->TAB('forum_Boards', 'Дъски', 'admin,forum');
		
		$topicUrl = array();
		
		//@TODO  да оправя правилното взимане на ид на тема и да се записва в сесията
		if(Request::get('Act') == 'Topic') {
			$topic = Request::get('id');
			
			$topicUrl = array('forum_Postings', 'Topic', $topic);
		}
		$this->TAB($topicUrl, 'Tема', 'forum,admin');
		$this->TAB('forum_Postings', 'Постинги', 'forum,admin');
	}
}