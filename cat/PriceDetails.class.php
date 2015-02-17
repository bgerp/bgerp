<?php



/**
 * Помощен детайл подготвящ и обединяващ заедно детайлите на артикулите свързани
 * с ценовата информация на артикулите
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_PriceDetails extends core_Manager
{
    
    /**
     * Кои мениджъри ще се зареждат
     */
    public $loadList = 'PriceList=price_ListRules,VatGroups=cat_products_VatGroups,PriceGroup=price_GroupOfProducts';
    
    
    /**
     * Кой има достъп до списъчния изглед
     */
    public $canList = 'no_one';
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Подготвя ценовата информация за артикула
     */
	public function preparePrices($data)
    {
    	$data->TabCaption = 'Цени';
    	$data->Tab = 'top';
    	$data->Order = 5;
    	 
    	$groupsData = clone $data;
    	$listsData = clone $data;
    	$vatData = clone $data;
    	
    	$this->PriceGroup->preparePriceGroup($groupsData);
    	$this->PriceList->preparePriceList($listsData);
    	$this->VatGroups->prepareVatGroups($vatData);
    	
    	$data->groupsData = $groupsData;
    	$data->listsData = $listsData;
    	$data->vatData = $vatData;
    }
    
    
    /**
     * Рендира ценовата информация за артикула
     */
    public function renderPrices($data)
    {
    	$tpl = getTplFromFile('cat/tpl/PriceDetails.shtml');
    	$tpl->append($this->PriceGroup->renderPriceGroup($data->groupsData), 'PriceGroup');
    	$tpl->append($this->PriceList->renderPriceList($data->listsData), 'PriceList');
    	$tpl->append($this->VatGroups->renderVatGroups($data->vatData), 'VatGroups');
    	
    	return $tpl;
    }
}