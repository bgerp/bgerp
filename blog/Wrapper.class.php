<?php

/**
 * Блог - Опаковка
 *
 *
 * @category  bgerp
 * @package   blog
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blog_Wrapper extends plg_ProtoWrapper
{


	/**
	 * Описание на табовете
	 */
	function description()
	{
		$this->TAB('blog_Articles', 'Статии', 'admin,blog');
		$this->TAB('blog_Comments', 'Коментари', 'blog,admin');
		$this->TAB('blog_Categories', 'Категории', 'admin,blog');
	}
}