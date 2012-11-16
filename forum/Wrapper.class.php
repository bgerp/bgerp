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
		
		/*$topicUrl = array();
		
		//  Ако сме в екшъня Topic извличамв ид-то на темата и го записваме в сесията
		if(Request::get('Act') == 'Topic') {
			$topic = Request::get('id');
			Mode::setPermanent('lastTopic', $topic);
		}
		
		// Ако има последна тема в сесията създаваме адреса на таба
		if($topic = Mode::get('lastTopic')) {
			$topicUrl = array('forum_Postings', 'Topic', $topic);
		}
		
		$this->TAB($topicUrl, 'Tема', 'forum,admin');*/
		$this->TAB('forum_Postings', 'Постинги', 'forum,admin');
	}
}