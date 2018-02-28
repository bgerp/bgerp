<?php




/**
 * Клас 'prosody_Setup'
 *
 * Исталиране/деинсталиране на prosody RESTful API
 *
 *
 * @category  bgerp
 * @package   prosody
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

DEFINE('PROSODY_DOMAIN','jabber.server.bg');
DEFINE('PROSODY_ADMIN_URL', "http://jabber.server.bg:5280/admin_rest");
DEFINE('PROSODY_ADMIN_USER', "admin@jabber.server.bg");
DEFINE('PROSODY_ADMIN_PASS', 'Passw0rd!');

class prosody_Setup extends core_ProtoSetup
{
	
	
	/**
	 * Версия на пакета
	 */
	public $version = '0.1';
	
	/**
	 * Списък с мениджърите, които съдържа пакета
	 */
	public $managers = array(
			//'prosody_RestApi',
	
	);
	
	
	/**
	 * Роли за достъп до модула
	*/
	public $roles = 'chat';
	
	
	/**
	 * Описание на конфигурационните константи
	 */
	public $configDescription = array(
	    'PROSODY_DOMAIN' => array("varchar", 'caption=Настройки->Домейн'),
	    'PROSODY_ADMIN_URL' => array("varchar", 'caption=Настройки->Административно URL'),
	    'PROSODY_ADMIN_USER' => array("varchar", 'caption=Настройки->Потребител'),
	    'PROSODY_ADMIN_PASS' => array("password", 'caption=Настройки->Парола'),
	     
	);
	
	
	/**
	 * Описание на модула
	 */
	public $info = "REST API за Prosody";
	
	
	/**
	 * Инсталиране на пакета
	 */
	function install()
	{
		$html = parent::install();
		 
		return $html;
	}	
}

