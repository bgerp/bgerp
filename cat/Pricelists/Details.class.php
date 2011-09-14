<?php
/**
 * 
 * Детайли на ценоразпис
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class cat_Pricelists_Details extends core_Detail
{
	var $title = 'Цена';
	
	var $masterKey = 'pricelistId';
	
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,packagingId,validFrom,price,discount';
	
    function description()
	{
		$this->FLD('pricelistId', 'key(mvc=cat_Pricelists,select=name)', 'caption=Ценоразпис');
		
		
		// Продукт
		$this->FLD('productId', 'key(mvc=cat_Products,select=name, allowEmpty)', 'caption=Продукт');
		
		// Вид опаковка. Ако е пропуснат, записа се отнася за основната мярка
		$this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name, allowEmpty)', 'caption=Опаковка');
		
		// Валидност от дата
		$this->FLD('validFrom', 'datetime', 'caption=В сила от');

		// Продажна цена
		$this->FLD('price', 'double', 'caption=Цена->Продажна');
		
		// отстъпка от крайната цена до себестойността
		$this->FLD('discount', 'percent', 'caption=Цена->Отстъпка');
	}
}