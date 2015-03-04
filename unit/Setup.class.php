<?php


/**
 * Клас 'unit_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   unit
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class unit_Setup extends core_ProtoSetup
{
	
	
	/**
	 * Версия на пакета
	 */
	public $version = '0.1';
	
	
	/**
	 * Описание на модула
	 */
	public $info = "Пакет за автоматично тестване на класове";

	
	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
	
}

