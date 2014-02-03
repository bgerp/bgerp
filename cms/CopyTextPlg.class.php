<?php



/**
 * Клас 'cms_CopyTextPlg' - Плъгин за добавяне на линк към текущата страница при копиране на текст
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_CopyTextPlg extends core_Plugin
{
	static function on_Output(&$invoker)
	{
		$invoker->append("document.oncopy = addLink;", "JQRUN");
	}
}
