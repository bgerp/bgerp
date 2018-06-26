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

DEFINE('PROSODY_DOMAIN','');
DEFINE('PROSODY_ADMIN_URL', '');
DEFINE('PROSODY_ADMIN_USER', '');
DEFINE('PROSODY_ADMIN_PASS', '');

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
	);
	
	
	/**
	 * Описание на конфигурационните константи
	 */
	public $configDescription = array(
	    'PROSODY_DOMAIN' => array("varchar", 'caption=Настройки->Домейн,placeholder=jabber.sever.bg'),
	    'PROSODY_ADMIN_URL' => array("url", 'caption=Настройки->Административно URL,placeholder=http://jabber.server.bg:5280/admin_rest'),
	    'PROSODY_ADMIN_USER' => array("email", 'caption=Настройки->Потребител,placeholder=admin@jabber.server.bg'),
	    'PROSODY_ADMIN_PASS' => array("password", 'caption=Настройки->Парола,placeholder=Passw0rd!'),
	     
	);
	
	
	/**
	 * Описание на модула
	 */
	public $info = "REST API за Prosody XMPP Чат";
	
	
    public $defClasses = 'prosody_RemoteDriver';


	/**
	 * Инсталиране на пакета
	 */
	function install()
	{
		$html = parent::install();
		 
		return $html;
	}	
}

