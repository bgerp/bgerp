
<?php

/**
 * Драйвър за универсална услуга
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Универсална услуга
 */
class cat_GeneralServiceDriver extends cat_GeneralProductDriver
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecificationBaseServiceDriver';
	
	
	/**
	 * Дефолт мета данни за всички продукти
	 */
	protected $defaultMetaData = 'canSell,canBuy';
}