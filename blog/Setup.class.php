<?php
/**
 * Константи за брой дни след които статията се заключва за коментиране
 */
defIfNot('BLOG_MAX_COMMENT_DAYS', '5');


/**
  * class blog_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с Блога
 *
 *
 * @category  bgerp
 * @package   blog
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blog_Setup
{


	/**
	 * Версия на пакета
	 */
	var $version = '0.1';


	/**
	 * Мениджър - входна точка в пакета
	 */
	var $startCtr = 'blog_Articles';


	/**
	 * Екшън - входна точка в пакета
	 */
	var $startAct = 'default';


	/**
	 * Описание на модула
	 */
	var $info = "Блог";

	/**
	 * Описание на конфигурационните константи
	 */
	
	
	/**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
	
			// Константи за инициализиране на таблицата с контактите
			'BLOG_MAX_COMMENT_DAYS' => array ('int'),
	
	);
	
	
/**
	 * Инсталиране на пакета
	*/
	function install()
	{
		$managers = array(
				'blog_Articles',
				'blog_Categories',
				'blog_Comments'
		);

		// Роля за power-user на този модул
		$role = 'blog';
		$html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';

		$instances = array();

		foreach ($managers as $manager) {
			$instances[$manager] = &cls::get($manager);
			$html .= $instances[$manager]->setupMVC();
		}


		$Menu = cls::get('bgerp_Menu');
		$html .=$Menu->addItem(3, 'Обслужване', 'Блог', 'blog_Articles', 'default', "{$role}, admin");

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