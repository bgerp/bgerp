<?php
/**
  * class feed_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджърa свързан с Хранилките
 *
 *
 * @category  vendors
 * @package   feed
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class feed_Setup
{


	/**
	 * Версия на пакета
	 */
	var $version = '0.1';


	/**
	 * Мениджър - входна точка в пакета
	 */
	var $startCtr = 'feed_Generator';


	/**
	 * Екшън - входна точка в пакета
	 */
	var $startAct = 'list';


	/**
	 * Описание на модула
	 */
	var $info = "Хранилка";

	
	/**
	 * Инсталиране на пакета
	*/
	function install()
	{
		$managers = array(
				'feed_Generator'
		);

		// Роля за power-user на този модул
		$role = 'feed';
		$html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';

		$instances = array();

		foreach ($managers as $manager) {
			$instances[$manager] = &cls::get($manager);
			$html .= $instances[$manager]->setupMVC();
		}
        
        $Menu  = cls::get('bgerp_Menu');
		$html .= $Menu->addItem(3, 'Сайт', 'Хранилки', 'feed_Generator', 'list', "cms, {$role}, admin");

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