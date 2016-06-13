<?php


/**
 * Разделител
 */
defIfNot('CSV_DELIMITER', '&comma;');


/**
 * Клас 'csv_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   csv
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class csv_Setup extends core_ProtoSetup
{

	
	/**
	 * Версия на пакета
	 */
	public $version = '0.1';
	
	
	/**
	 * Описание на модула
	 */
	public $info = "Пакет за работа с CSV файлове";


	/**
	 * Описание на конфигурационните константи
	 */
	public $configDescription = array(
		'CSV_DELIMITER' => array ('varchar(12)', 'caption=Разделител,suggestions=&Tab;|&comma;|&vert;|;|:'),
	);
	
	
	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
	
}

