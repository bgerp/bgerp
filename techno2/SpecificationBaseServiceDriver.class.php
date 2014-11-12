
<?php

/**
 * Драйвър за универсална услуга
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Драйвър за универсална услуга
 */
class techno2_SpecificationBaseServiceDriver extends techno2_SpecificationBaseDriver
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno_SpecificationBaseServiceDriver';
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
	/**
	 * Дефолт мета данни за всички продукти
	 */
	public static $defaultMetaData = 'canSell,canBuy';
	
	
	/**
	 * Кои опаковки поддържа продукта
	 */
	public function getDefaultMetas()
	{
		return arr::make(self::$defaultMetaData, TRUE);
	}
}