<?php


/**
 * Клас 'apachetika_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   apachetika
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class apachetika_Setup extends core_ProtoSetup
{
	
	
	/**
	 * Версия на пакета
	 */
	public $version = '0.1';
	
	
	/**
	 * Описание на модула
	 */
	public $info = "Инструмент за разпознаване и извличане на метаданни и текст от различни типове файлове";
	

	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
	
}

