<?php
/**
 * 
 * Детайл на модела @link catpr_Pricelists
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Ценоразпис-детайли
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Pricelists_Details extends core_Detail
{
	var $title = 'Ценоразпис';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State2,
                     catpr_Wrapper, plg_AlignDecimals';
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     *
     * @var string
     */
    var $masterKey = 'pricelistId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'priceGroupId, productId, price, tools=Пулт';
    
    
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
	
    
    function description()
	{
		$this->FLD('pricelistId', 'key(mvc=catpr_Pricelists,select=id)', 'mandatory,input,caption=Ценоразпис');
		$this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name)', 'mandatory,input,caption=Група');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'mandatory,input,caption=Продукт');
		$this->FLD('price', 'double(minDecimals=2)', 'mandatory,input,caption=Цена');
		$this->FLD('state', 'enum(draft,active,rejected)');
	}
}