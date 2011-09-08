<?php

class cat_Products_Packagings extends core_Detail
{
	var $masterKey = 'productId';
	
	var $title = 'Опаковки';
	
	var $listFields = 'packagingId, value';
	
	var $loadList = 'cat_Wrapper';
	
    /**
     *  Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
	
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden');
		$this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name)', 'input,caption=Опаковка');
		$this->FLD('value', 'varchar(255)', 'input,caption=Стойност');
		
		$this->setDbUnique('productId,packagingId');
	}
	
	function on_AfterPrepareListToolbar($mvc, $data)
	{
		$data->toolbar->removeBtn('*');
		$data->toolbar->addBtn('Промяна', array($this, 'edit', 'productId'=>$data->masterId));
	}
	
	function on_AfterPrepareListFields($mvc, $data)
	{
		$data->query->orderBy('#id');
	}
	
	function on_AfterPrepareEditForm($mvc, $data)
	{
		$productId = Request::get('productId', "key(mvc={$mvc->Master->className})");
		$data->form = $mvc->getPackagingsForm($productId);
	}
	
	function on_AfterPrepareEditToolbar($mvc, $data)
	{
		$productId = Request::get('productId', "key(mvc={$mvc->Master->className})");
		$data->form->toolbar->addBtn('Отказ', array('cat_Products', 'single', $productId), array('class'=>'btn-cancel'));
	}
	
	static function &getPackagingsForm($productId, &$form = NULL)
	{
		$productRec = cat_Products::fetch($productId);
		$form = cat_Categories::getPackagingsForm($productRec->categoryId, $form);
		
		if (!$form->getField('productId', FALSE)) {
			$form->FLD('productId', 'key(mvc=cat_Products)', 'silent,input=hidden,value='.$productId);
		}

		if (!$form->title) {
			$form->title = $productRec->name;
		}
		
		$query = static::getQuery();
		$query->where("#productId = {$productId}");
		
		while ($rec = $query->fetch()) {
			$form->setDefault("packvalue_{$rec->packagingId}", $rec->value);
			$form->FLD("packid_{$rec->packagingId}", "key(mvc=cat_Products_Packagings)", "input=hidden,value={$rec->id}");
		}
		
		return $form;
	}
	
	static function processPackagingsForm($form)
	{
		$productId = $form->rec->productId;
		
		foreach ((array)$form->rec as $n=>$v) {
			list($n, $key) = explode('_', $n, 2);
			if ($n == 'packvalue') {
				$packagingId = $key;
				$id          = $form->rec->{"packid_{$packagingId}"};
				$value       = $v;

				$rec = (object)compact('id', 'productId', 'packagingId', 'value');
				static::save($rec);
			}
			
		}
	}
	
	function on_AfterInputEditForm($mvc, $form)
	{
		if ($form->isSubmitted()) {
			$mvc->processPackagingsForm($form);
			redirect(array('cat_Products', 'single', $form->rec->productId));
		}
	}
}