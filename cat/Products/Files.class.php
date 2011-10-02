<?php

class cat_Products_Files extends core_Detail
{
	var $masterKey = 'productId';
	
	var $title = 'Файлове';
	
	var $listFields = 'id, file';
	
	var $loadList = 'cat_Wrapper, plg_RowTools';
	
    /**
     *  Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
	
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $this->FLD('file', 'fileman_FileType(bucket=productsFiles)', 'caption=Файл, notSorting');
        $this->FLD('description', 'text', 'caption=Описание,input');
	}
	
    /**
     * Създаваме кофа
     *
     * @param core_MVC $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('productsFiles', 'Файлове към продукта', '', '100MB', 'user', 'every_one');
    }
    
	function on_AfterPrepareListToolbar($mvc, $data)
	{
		$data->toolbar->removeBtn('*');
		
		$data->toolbar->addBtn('Нов Файл', 
			array(
				$mvc, 
				'add', 
				'productId'=>$data->masterId,
				'ret_url'=>TRUE
			),
			array(
				'class' => 'btn-add'
			)
		);
	}
	
	
	function on_AfterInputEditForm($mvc, $form) {
		$productName = cat_Products::fetchField($form->rec->productId, 'name');
		
		$form->title = "Файл към|* {$productName}";
	}
	
}