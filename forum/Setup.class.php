<?php

/**
 *  Константа за тема по-подразбиране на блога
 */
defIfNot('FORUM_DEFAULT_THEME', 'forum/themes/default');

defIfNot('FORUM_THEMES_PER_PAGE', '10');

defIfNot('GREETING_MESSAGE', 'Добре дошли в нашия форум');

defIfNot('FORUM_POSTS_PER_PAGE', '10');

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
class forum_Setup extends core_ProtoSetup
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
     * Описание на конфигурационните константи за този модул
     */
    var $configDescription = array(
            
            'FORUM_DEFAULT_THEME' => array ('varchar', 'mandatory, caption=Тема по подразбиране в блога->Път до темата'),
         
    		'FORUM_THEMES_PER_PAGE' => array ('int', 'mandatory, caption=Броят на темите в една страница->Число'),
    
    		'GREETING_MESSAGE' => array ('text', 'mandatory, caption=Съобщение за поздрав->Текст'),
         
    		'FORUM_POSTS_PER_PAGE' => array ('int', 'mandatory, caption=Броят на постовете на една страница->Число'),
        );
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
				'forum_Boards',
				'forum_Postings',
				'forum_Categories',
		);
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'forum';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.5, 'Сайт', 'Форум', 'forum_Boards', 'list', "cms,forum, admin, ceo"),
        );
	
	
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