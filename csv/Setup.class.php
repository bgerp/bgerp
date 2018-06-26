<?php



/**
 * Разделител
 */
defIfNot('CSV_DELIMITER', '&comma;');


/**
 * Как да е форматирана датата
 */
defIfNot('CSV_DATE_MASK', 'd.m.Y');


/**
 * Как да е форматирана датата и часа
 */
defIfNot('CSV_DATE_TIME_MASK', 'd.m.y H:i');


/**
 * Какъв да е десетичният разделител на числата при експорт в csv
 */
defIfNot('CSV_DEC_POINT', '&#44;');


/**
 * Клас 'csv_Setup'
 *
 * Исталиране/деинсталиране на класове за работа със csv
 *
 *
 * @category  bgerp
 * @package   csv
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
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
		'CSV_DELIMITER'      => array ('varchar(12)', 'caption=Формат за експорт на CSV->Разделител,suggestions=&Tab;|&comma;|&vert;|;|:'),
		'CSV_DEC_POINT'      => array ('enum(.=Точка,&#44;=Запетая)', 'caption=Формат за експорт на CSV->Дробен знак, customizeBy=powerUser'),
		'CSV_DATE_MASK'      => array ('enum(d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)', 'caption=Формат за експорт на CSV->Дата, customizeBy=powerUser'),
		'CSV_DATE_TIME_MASK' => array ('enum(d.m.y H:i=|*22.11.1999 00:00, d.m.y H:i:s=|*22.11.1999 00:00:00)', 'caption=Формат за експорт на CSV->Дата и час, customizeBy=powerUser'),
	);
	
	
	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
}

