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
	
	var $loadList = 'plg_RowTools';
	
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,productId, packagingId,validFrom,price,discount, prices';
	
    function description()
	{
		$this->FLD('pricelistId', 'key(mvc=cat_Pricelists,select=name)', 'caption=Ценоразпис');
		
		// Продукт
		$this->FLD('productId', 'key(mvc=cat_Products,select=name, allowEmpty)', 'silent,mandatory,caption=Продукт');
		
		// Вид опаковка. Ако е пропуснат, записа се отнася за основната мярка
		$this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name, allowEmpty)', 'silent,caption=Опаковка');
		
		// Валидност от дата
		$this->FLD('validFrom', 'datetime', 'caption=В сила от');

		// Продажна цена
		$this->FLD('price', 'double', 'caption=Цена->Продажна');
		
		// отстъпка от крайната цена до себестойността
		$this->FLD('discount', 'percent', 'caption=Цена->Отстъпка');
		
		$this->setDbUnique('pricelistId, productId, packagingId, validFrom');
	}
	
	function on_AfterPrepareEditForm($mvc, $data)
	{
		if (empty($data->form->rec->validFrom)) {
			$data->form->rec->validFrom = dt::now();
		}
	}
	
	function on_AfterInputEditForm($mvc, $form)
	{
		if (empty($form->rec->validFrom)) {
			$form->rec->validFrom = dt::now();
		}
	}
	
	function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		// Сортиране на записите по num
		$data->query->orderBy('validFrom');
	}
	
	function on_AfterPrepareListRecs($mvc, $data)
	{
		$products = array();
		$rows     = $data->rows;
		$recs     = $data->recs;
		
		// Групиране на записите по продукти
		foreach ($recs as $i=>$rec) {
			$products[$rec->productId][$rec->packagingId][] = $rows[$i];
		}

        $table = cls::get('core_TableView', compact('mvc'));
        $listFields = arr::make('id, validFrom, price, discount', TRUE);
        foreach ($listFields as &$f) {
        	$f = $mvc->getField($f)->caption;
        }
		
		$data->rows = array();
		foreach ($products as $productId=>$packagings) {
			foreach ($packagings as $packagingId=>$prices) {
	        	$hdrTpl = new core_ET('<h3>[#productId#], [#packagingId#] [#add#]</h3>');
	        	$hdrTpl->replace($prices[0]->productId, 'productId');
	        	$hdrTpl->replace($prices[0]->packagingId, 'packagingId');
		        $hdrTpl->replace(
		        	ht::createBtn('+', 
		        		array(
		        			$mvc, 
		        			'add', 
		        			'productId'=>$productId, 
		        			'packagingId'=>$packagingId,
		        			'ret_url' => getCurrentUrl()
		        		)
		        	),
		        	'add'
		        );
	        	$pricesTpl = $table->get($prices, $listFields);
		        $pricesTpl->prepend($hdrTpl);
		        $data->rows[] = (object)array(
					'productId' => $prices[0]->productId,
					'packagingId' => $prices[0]->packagingId,
					'prices' => $pricesTpl
				);
			}
		}
		
		$data->listFields = array(
//			'productId'=>$mvc->getField('productId')->caption, 
//			'packagingId'=>$mvc->getField('packagingId')->caption, 
			'prices'=>'Цени'
		);
	}
}