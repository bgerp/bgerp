<?php



/**
 * Добавя екшън към бизнес документ за автоматично добавяне на нов артикул
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_plg_CreateProductFromDocument extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->filterProtoByMeta, 'canSell');
		expect(in_array($mvc->filterProtoByMeta, array('canSell', 'canBuy', 'canStore', 'canConvert', 'fixedAsset', 'canManifacture')));
		expect($mvc instanceof deals_DealDetail || $mvc instanceof sales_QuotationsDetails);
	}
	
	
	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, $data)
	{
		if($mvc->haveRightFor('createProduct', (object)array($mvc->masterKey => $data->masterId))){
			$data->toolbar->addBtn('Създаване', array($mvc, 'CreateProduct', $mvc->masterKey => $data->masterId, 'ret_url' => TRUE), "id=btnNewProduct,title=Създаване на нов нестандартен артикул", 'ef_icon = img/16/shopping.png,order=12');
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'createproduct'){
			if(isset($rec)){
				$options = self::getProtoOptions($mvc->filterProtoByMeta, 1);
				if(isset($rec->cloneId)){
					$cloneRec = $mvc->fetch($rec->cloneId);
					if(!$cloneRec || $cloneRec->{$mvc->masterKey} != $rec->{$mvc->masterKey}){
						$requiredRoles = 'no_one';
					} else {
						$options[$cloneRec->productId] = $cloneRec->productId;
					}
				}
				
				if(count($options)){
					$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
					if(!cat_Products::haveRightFor('add', (object)array('threadId' => $masterRec->threadId))){
						$requiredRoles = 'no_one';
					} else {
						$requiredRoles = $mvc->getRequiredRoles('add', (object)array($mvc->masterKey => $masterRec->id));
					}
				} else {
					$requiredRoles = 'no_one';
				}
			} else {
				$requiredRoles = $mvc->getRequiredRoles('add');
			}
		}
	}
	
	
	/**
	 * Преди всеки екшън на мениджъра-домакин
	 */
	public static function on_BeforeAction($mvc, &$tpl, $action)
	{
		if($action == 'createproduct'){
			$mvc->requireRightFor('createproduct');
			expect($masterId = Request::get($mvc->masterKey, 'int'));
			expect($masterRec = $mvc->Master->fetch($masterId));
			$cloneId = Request::get('cloneId', 'int');
			if($cloneId){
				$cloneRec = $mvc->fetch($cloneId);
			}
			
			$mvc->requireRightFor('createproduct', (object)array($mvc->masterKey => $masterId, 'cloneId' => $cloneRec->id));
			$Products = cls::get('cat_Products');
			
			$detailFields = $productFields = array();
			
			$form = $mvc->getForm();
			$form->setField($mvc->masterKey, 'input=hidden');
			$form->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Прототип,input,silent,removeAndRefreshForm=packPrice|discount|packagingId|tolerance,placeholder=Популярни продукти,mandatory,after=saleId");
			
			$protos = cat_Categories::getProtoOptions();
			if(isset($cloneRec)){
				$protos[$cloneRec->productId] = cat_Products::getTitleById($cloneRec->productId, FALSE);
			}
			$form->setOptions('proto', $protos);
			
			$form->input(NULL, 'silent');
			
			foreach (array('id', 'createdOn', 'createdBy') as $f){
				unset($form->fields[$f]);
			}
			
			$form->setField('productId', 'input=hidden');
			if(isset($cloneRec)){
				$form->setField('proto', 'input=hidden');
				$form->setDefault('proto', $cloneRec->productId);
				$detailFields['proto'] = 'proto';
				foreach ($form->fields as $n => $f1){
					$detailFields[$n] = $n;
					if(isset($cloneRec->{$n})){
						$form->setDefault($n, $cloneRec->{$n});
					}
				}
			} else {
				foreach ($form->fields as $n => $f1){
					$detailFields[$n] = $n;
				}
			}
			
			$data1 = (object)array('form' => $form, 'masterRec' => $masterRec);
			$mvc->invoke('AfterPrepareEditForm', array($data1, $data1));
			
			if(isset($form->rec->proto)){
				$proto = $form->rec->proto;
				cat_Products::setAutoCloneFormFields($form, $proto);
				$form->setDefault('productId', $form->rec->proto);
				
				$protoRec = cat_Products::fetch($proto);
				$productFields = array_diff_key($form->fields, $detailFields);
				$protoName = cat_Products::getTitleById($protoRec->id);
				foreach ($productFields as $n1 => $fld){
					if(isset($protoRec->{$n1})){
						$form->setDefault($n1, $protoRec->{$n1});
					}
					
					$caption = $fld->caption;
					if(strpos($fld->caption, '->') === FALSE){
						$caption = (isset($cloneRec)) ? "Клониране на" : "Персонализиране на";
						$caption .= "|* <b>{$protoName}</b>->{$fld->caption}";
					}
					
					$form->setField($n1, "caption={$caption}");
				}
				
				$productKeys = array_keys($productFields);
				$productKeys = implode('|', $productKeys);
				$form->setField('proto', "removeAndRefreshForm={$productKeys}");
				
				$sameMeasures = cat_UoM::getSameTypeMeasures($protoRec->measureId);
				$form->setOptions('measureId', $sameMeasures);
				
				$Driver = cat_Products::getDriver($proto);
				$Driver->invoke('AfterPrepareEditForm', array($Products, (object)array('form' => $form)));
			
				$form->input();
				$mvc->invoke('AfterInputEditForm', array($form));
				$Products->invoke('AfterInputEditForm', array($form));
				
				$productKeys = array_keys($productFields);
				$productKeys = implode('|', $productKeys);
				$form->setField('proto', "removeAndRefreshForm={$productKeys}");
				
				if(isset($cloneRec)){
					$form->rec->proto = $protoRec->proto;
				} else {
					$quantityField = $form->getField('measureId');
					if($quantityField->input != 'hidden'){
						unset($form->fields['packQuantity']->unit);
					}
				}
				
				$productFields = array_diff_key($form->fields, $detailFields);
				
				if(isset($cloneRec)){
					if(!array_key_exists($cloneRec->packagingId, $sameMeasures)){
						$sameMeasures[$cloneRec->packagingId] = cat_UoM::getVerbal($cloneRec->packagingId, 'name');
					}
					$form->setOptions('packagingId', $sameMeasures);
				}
			} else {
				$fields = $form->selectFields();
				foreach ($fields as $name => $fl1){
					if($name != 'proto'){
						$form->setField($name, 'input=none');
					}
				}
			}
			
			if($form->isSubmitted()){
				$rec = $form->rec;
				$arrRec = (array)$rec;
				
				$pRec = (object)array_intersect_key($arrRec, $productFields);
				$pRec->proto = $rec->proto;
				$pRec->folderId = $masterRec->folderId;
				$pRec->threadId = $masterRec->threadId;
				$pRec->isPublic = 'no';
				
				$protoRec = cat_Products::fetch($rec->productId);
				$pRec->meta = $protoRec->meta;
				
				$productId = $Products->save($pRec);
				$dRec = (object)array_diff_key($arrRec, $productFields);
				$dRec->productId = $productId;
				if(empty($cloneRec)){
					$dRec->packagingId = $pRec->measureId;
					$dRec->quantityInPack = 1;
				}
				
				$mvc->save($dRec);
					
				if($dRec->packagingId != $pRec->measureId){
					$packRec = (object)array('productId' => $productId, 'packagingId' => $rec->packagingId, 'quantity' => $rec->quantityInPack);
					cat_products_Packagings::save($packRec);
				}
				
				return Redirect(array($mvc->Master, 'single', $dRec->{$mvc->masterKey}), FALSE, 'Успешно е създаден нов артикул');
			}
			
			$folderTitle = doc_Folders::recToVerbal(doc_Folders::fetch($masterRec->folderId))->title;
			$form->title = "Създаване на нов нестандартен артикул в|* {$folderTitle}";
				
			$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис');
			$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
				
			// Рендиране на опаковката
			$tpl = $mvc->renderWrapping($form->renderHtml());
				
			return FALSE;
		}
	}
	
	
	/**
	 * Кои са прототипните артикули
	 * 
	 * @param string $meta - мета свойство
	 * @param int $limit - ограничение
	 * @return array $options - опции
	 */
	private static function getProtoOptions($meta, $limit = NULL)
	{
		$options = cat_Categories::getProtoOptions();
		
		$count = 0;
		foreach ($options as $id => $opt){
			$metaValue = cat_Products::fetchField($id, $meta);
			if($metaValue != 'yes'){
				unset($options[$id]);
			} else {
				$count++;
				if(isset($limit) && $count == $limit) return $options;
			}
		}
		
		return $options;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		if(cat_Products::fetchField($rec->productId, 'proto')){
			if($mvc->haveRightFor('createProduct', (object)array($mvc->masterKey => $rec->{$mvc->masterKey}, 'cloneId' => $rec->id))){
				$url = array($mvc, 'CreateProduct', $mvc->masterKey => $rec->{$mvc->masterKey}, 'cloneId' => $rec->id, 'ret_url' => TRUE);
				
				if($mvc->hasPlugin('plg_RowTools2')){
					core_RowToolbar::createIfNotExists($row->_rowTools);
					$row->_rowTools->addLink('Клониране', $url, "id=btnNewProduct,title=Създаване на нов нестандартен артикул", 'ef_icon = img/16/shopping.png,order=12');
				}
			}
		}
	}
}