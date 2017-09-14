<?php


/**
 * Помощен абстрактен клас, който позволява на определени
 * скриптови класове да модифицират, според шаблоните. За да 
 * може към шаблоните за документи (@see doc_TplManager) да се 
 * прикачат файлове, които да модифицират данните на документа
 * взависимост от текущия шаблон трябва в директорията на файла
 * от който е зареден шаблона на документа, да има клас със същото
 * име но с разширение `.class.php`, тогава този клас ще се инстанцира
 *  и ще може да модифицира вътрените данни на документа.
 * 
 * След подготовката на данните на мастъра се подават данните на скрипта,
 *  както и след подготовката на данните на детайла
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class doc_TplScript {
	
	
	/**
	 * Функция - флаг, че обектите от този клас са Singleton
	 */
	function _Singleton() {}
	
	
	/**
	 * Метод който подава данните на мастъра за обработка на скрипта
	 * 
	 * @param core_Mvc $mvc - мастър на документа
	 * @param stdClass $data - данни
	 * @return void
	 */
	public function modifyMasterData(core_Mvc $mvc, &$data)
	{
		
	}
	
	
	/**
	 * Метод който подава данните на детайла на мастъра, за обработка на скрипта
	 * 
	 * @param core_Mvc $detail - Детайл на документа
	 * @param stdClass $data - данни
	 * @return void
	 */
	public function modifyDetailData(core_Mvc $detail, &$data)
	{
	
	}
	
	
	/**
	 * Модифицира шаблона на детайла
	 * 
	 * @param core_Mvc $detail
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public function modifyDetailTpl(core_Mvc $detail, &$tpl, &$data)
	{
		
	}
	
	
	/**
	 * Преди рендиране на шаблона на детайла
	 *
	 * @param core_Mvc $detail
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public function beforeRenderListTable(core_Mvc $detail, &$tpl, &$data)
	{
	
	}
}