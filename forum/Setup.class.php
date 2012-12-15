<?php

/**
 *  Константа за тема по-подразбиране на блога
 */
defIfNot('FORUM_DEFAULT_THEME', 'forum/themes/default');

defIfNot('FORUM_THEMES_PER_PAGE', '10');

defIfNot('GREETING_MESSAGE', 'Добре дошли в нашия форум');

defIfNot('FORUM_POSTS_PER_PAGE', '3');

/**
  * class forum_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с Форума
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Setup
{


	/**
	 * Версия на пакета
	 */
	var $version = '0.1';


	/**
	 * Мениджър - входна точка в пакета
	 */
	var $startCtr = 'forum_Boards';


	/**
	 * Екшън - входна точка в пакета
	 */
	var $startAct = 'default';


	/**
	 * Описание на модула
	 */
	var $info = "Форум";

	
	/**
	 * Инсталиране на пакета
	*/
	function install()
	{
		$managers = array(
				'forum_Boards',
				'forum_Postings',
				'forum_Categories',
		);

		// Роля за power-user на този модул
		$role = 'forum';
		$html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';

		$instances = array();

		foreach ($managers as $manager) {
			$instances[$manager] = &cls::get($manager);
			$html .= $instances[$manager]->setupMVC();
		}
        
        $Menu  = cls::get('bgerp_Menu');
		$html .= $Menu->addItem(3.5, 'Сайт', 'Форум', 'forum_Boards', 'list', "cms, {$role}, admin");

		return $html;
	}
	
	
	/**
	 * Де-инсталиране на пакета
	 */
	function deinstall()
	{
		// Изтриване на пакета от менюто
		$res .= bgerp_Menu::remove($this);

		return $res;
	}
}