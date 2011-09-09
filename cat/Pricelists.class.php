<?php
/**
 * 
 * Ценоразписи за продуктите от каталога
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class cat_Pricelists extends core_Manager
{
	var $title = 'Ценоразписи';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew,
                     cat_Wrapper, plg_Sorting, plg_Printing';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,objectId,packagingId,validFrom,price,discount';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,cat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,cat,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,cat,broker';
    
    var $canList = 'admin,cat,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,cat';
	
    
    function description()
	{
		$this->FLD('objectId', 'int', 'caption=За');
		
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