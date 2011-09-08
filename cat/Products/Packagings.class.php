<?php

class cat_Products_Packagings extends core_Detail
{
	var $masterKey = 'productId';
	
	var $title = 'Опаковки';
	
	var $listFields = 'id, packagingId, quantity, netWeight, tareWeight, eanCode';
	
	var $loadList = 'cat_Wrapper';
	
    /**
     *  Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
	
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
		$this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name)', 'input=hidden,silent,caption=Опаковка');
		$this->FLD('quantity', 'double', 'input,caption=Количество');
		$this->FLD('netWeight', 'double', 'input,caption=Нето');
		$this->FLD('tareWeight', 'double', 'input,caption=Тара');
		$this->FLD('eanCode', 'gs1_TypeEan13', 'input,caption=EAN код');
		
		$this->setDbUnique('productId,packagingId');
	}
	
	function on_AfterPrepareListToolbar($mvc, $data)
	{
		$data->toolbar->removeBtn('*');
	}
	
	function on_AfterPrepareListFields($mvc, $data)
	{
		$data->query->orderBy('#id');
	}
	
	function on_AfterPrepareListRecs($mvc, $data)
	{
		$allPkgs      = cat_Categories::fetchField($data->masterData->rec->categoryId, 'packagings');
		$allPkgs      = type_Keylist::toArray($allPkgs);
		$existingPkgs = array();
		
		if (count($data->recs)) {
			foreach ($data->recs as $r) {
				$existingPkgs[] = $r->packagingId;
			}
		}
		
		foreach (array_diff($allPkgs, $existingPkgs) as $packagingId) {
			$rec = (object)array(
				'productId' => $data->masterId,
				'packagingId' => $packagingId
			);
			
			$data->recs[] = $rec;
			$data->rows[] = $this->recToVerbal($rec, $data->listFields);
		}
	}
	
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        // Вземаме съдържанието на полето, като шаблон
        $row->{$field} = new ET($row->{$field});
        $tpl =& $row->{$field};
        
        $tpl->append("<div class='rowtools'>");
        
        if ($mvc->haveRightFor('edit', $rec)) {
            
            $editImg = "<img src=" . sbf('img/16/edit-icon.png') . ">";
            
            if ($rec->id > 0) {
	            $editUrl = array(
	                $mvc,
	                'edit',
	                'id' => $rec->id,
	                'ret_url' => TRUE
	            );
            } else {
	            $editUrl = array(
	                $mvc,
	                'add',
	                'productId' => $rec->productId,
	                'packagingId' => $rec->packagingId,
	                'ret_url' => TRUE
	            );
            }
            
            $editLnk = ht::createLink($editImg, $editUrl);
            
            $tpl->append($editLnk);
        }
        
        if ($rec->id > 0 && $mvc->haveRightFor('delete', $rec)) {
            
            $deleteImg = "<img src=" . sbf('img/16/delete-icon.png') . ">";
            
            $deleteUrl = array(
                $mvc,
                'delete',
                'id' => $rec->id,
                'ret_url' => TRUE
            );
            
            $deleteLnk = ht::createLink($deleteImg, $deleteUrl,
            tr('Наистина ли желаете записът да бъде изтрит?'));
            
            $tpl->append($deleteLnk);
        }
        
        $tpl->append("</div>");
	}

	function on_AfterInputEditForm($mvc, $form) {
		$productName = cat_Products::fetchField($form->rec->productId, 'name');
		$packName    = cat_Packagings::fetchField($form->rec->packagingId, 'name');
		
		$form->title = "{$packName} - {$productName}"; 
	}
}