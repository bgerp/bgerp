<?php


/**
 * Клас 'colab_Setup'
 *
 * Исталиране/деинсталиране на colab
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ielin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class colab_Setup extends core_ProtoSetup
{
	

	/**
	 * Версия на пакета
	 */
	public $version = '0.1';
	
	
	/**
	 * Описание на модула
	 */
	public $info = "Пакет за работа с партньори";
	

	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
	
}

