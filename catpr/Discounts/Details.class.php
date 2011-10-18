<?php
/**
 * 
 * Детайл на модела @link catpr_Discounts
 * 
 * Всеки запис от модела съдържа конкретен процент отстъпка за конкретна ценова група 
 * (@see catpr_Pricegroups) към дата.
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Отстъпки-детайли
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Discounts_Details extends core_Detail
{
	var $title = 'Отстъпки';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     catpr_Wrapper, plg_Sorting, plg_SaveAndNew,
                     plg_LastUsedKeys';
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     *
     * @var string
     */
    var $masterKey = 'discountId';
    
    /**
     * Списък от полета, които са външни ключове към други модели
     *  
     * @see plg_LastUsedKeys
     *
     * @var string
     */
    var $lastUsedKeys = 'priceGroupId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'priceGroupId, discount, tools=Пулт';
    
    var $zebraRows = TRUE;
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,catpr,broker';
    
    var $canList = 'admin,catpr,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,catpr';
    
    var $tabName = 'catpr_Discounts';
	
    
    function description()
	{
		$this->FLD('discountId', 'key(mvc=catpr_Discounts,select=name,allowEmpty)', 'mandatory,input=hidden,caption=Пакет,remember');
		$this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name,allowEmpty)', 'mandatory,input,caption=Група,remember');
		
		// процент на отстъпка от публичните цени
		$this->FLD('discount', 'percent', 'mandatory,input,caption=Отстъпка');
		
		$this->setDbUnique('discountId, priceGroupId');
	}
}