<?php

/**
 * Разделител
 */
defIfNot('CSV_DELIMITER', ',');


/**
 * Ограждане
 */
defIfNot('CSV_ENCLOSURE', '"');


/**
 * Десетичен знак
 */
defIfNot('CSV_DELIMITER_DECIMAL_SING', ',');


/**
 * Формат на датата
 */
defIfNot('CSV_FORMAT_DATE', 'дд.мм.гггг');




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
		'CSV_DELIMITER' => array ('enum(comma=\,,semicolon=;,colon=:,vertical=Вертикална черта)', 'caption=Разделител'),
	
		'CSV_ENCLOSURE' => array ('enum(quote=",apostrophe=\')', 'caption=Ограждане'),
	
		'CSV_DELIMITER_DECIMAL_SING' => array ('enum(dot=., comma=\,)', 'caption=Десетичен знак'),
			
		'CSV_FORMAT_DATE'   => array ('enum(dot=дд.мм.гггг,slash=мм/дд/гг)', 'caption=Формат на датата'),
	);
	
	
	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
	
}

