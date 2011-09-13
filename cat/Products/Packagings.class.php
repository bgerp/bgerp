<?php

class cat_Products_Packagings extends core_Detail
{
	var $masterKey = 'productId';
	
	var $title = 'Опаковки';
	
	var $listFields = 'id, packagingId, quantity, netWeight, tareWeight, eanCode';
	
	var $loadList = 'cat_Wrapper, plg_RowTools';
	
    /**
     *  Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
	
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
		$this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name)', 'input,silent,caption=Опаковка');
		$this->FLD('quantity', 'double', 'input,caption=Количество');
		$this->FLD('netWeight', 'double', 'input,caption=Нето');
		$this->FLD('tareWeight', 'double', 'input,caption=Тара');
		$this->FLD('eanCode', 'gs1_TypeEan13', 'input,caption=EAN код');
		
		$this->setDbUnique('productId,packagingId');
	}
	
	
	function on_AfterPrepareListToolbar($mvc, $data)
	{
		$data->toolbar->removeBtn('*');
		$data->toolbar->addBtn('Нова опаковка', array($mvc, 'edit', 'productId'=>$data->masterId,'ret_url'=>getCurrentUrl()));
	}
	
		
	function on_AfterPrepareListFields($mvc, $data)
	{
		$data->query->orderBy('#id');
	}
	
	
	function on_AfterPrepareEditToolbar($mvc, $data)
	{
		$data->form->toolbar->addBtn('Отказ', array($mvc->Master, 'single', $data->form->rec->productId), array('class'=>'btn-cancel'));
	}

	
	function on_AfterInputEditForm($mvc, $form) {
		$productName = cat_Products::fetchField($form->rec->productId, 'name');
		
		$form->title .= " - {$productName}"; 
	}
}