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
				$requiredRoles = $mvc->getRequiredRoles('add', $rec);
				if($requiredRoles == 'no_one') return;
				$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
				
				$options = cat_Categories::getProtoOptions(NULL, $mvc->filterProtoByMeta, 1);
				
				if(isset($rec->cloneId)){
					$cloneRec = $mvc->fetch($rec->cloneId);
					$isPublic = cat_Products::fetchField($cloneRec->productId, 'isPublic');
					
					if($isPublic == 'no'){
						$options[$cloneRec->productId] = $cloneRec->productId;
					} else {
						$requiredRoles = 'no_one';
					}
				}
				
				if($requiredRoles != 'no_one'){
					if(count($options)){
						
						if(!cat_Products::haveRightFor('add', (object)array('threadId' => $masterRec->threadId))){
							$requiredRoles = 'no_one';
						} else {
							$requiredRoles = $mvc->getRequiredRoles('add', (object)array($mvc->masterKey => $masterRec->id));
						}
					} else {
						$requiredRoles = 'no_one';
					}
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
			
			// Взимаме формата на детайла
			$form = $mvc->getForm();
			$form->setField($mvc->masterKey, 'input=hidden');
			$fieldPack = $form->getField('packagingId');
			unset($fieldPack->removeAndRefreshForm);
			
			// Поле за прототип
			$form->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Прототип,input,silent,removeAndRefreshForm=packPrice|discount|packagingId|tolerance,placeholder=Популярни продукти,mandatory,before=packagingId");
			
			// Наличните прототипи + клонирания
			$protos = cat_Categories::getProtoOptions(NULL, $mvc->filterProtoByMeta);
			
			if(isset($cloneRec)){
				$protos[$cloneRec->productId] = cat_Products::getTitleById($cloneRec->productId, FALSE);
			}
			$form->setOptions('proto', $protos);
			
			// Инпутваме silent полетата
			$form->input(NULL, 'silent');
			
			// Махаме системните полета от формата
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
					if(isset($cloneRec->{$n}) && !in_array($n, array('packQuantity', 'quantity', 'price', 'packPrice', 'discount'))){
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
			
			if($mvc instanceof sales_QuotationsDetails){
				$form->setDefault('optional', 'no');
			}
			
			// Ако е инпутнат прототип
			if(isset($form->rec->proto)){
				
				// Взимаме от драйвера нужните полета
				$proto = $form->rec->proto;
				cat_Products::setAutoCloneFormFields($form, $proto);
				$form->setDefault('productId', $form->rec->proto);
				
				// Зареждаме данни от прототипа (или артикула който клонираме)
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
				
				// Допустимите мерки са сред производните на тази на прототипа
				$sameMeasures = cat_UoM::getSameTypeMeasures($protoRec->measureId);
				$form->setOptions('measureId', $sameMeasures);
				$form->rec->folderId = $masterRec->folderId;
				$form->rec->threadId = $masterRec->threadId;
				
				// Извикваме в класа и драйвера нужните ивенти
				$Driver = cat_Products::getDriver($proto);
				$Driver->invoke('AfterPrepareEditForm', array($Products, (object)array('form' => $form)));
			
				$form->input();
				$Products->invoke('AfterInputEditForm', array($form));
				$mvc->invoke('AfterInputEditForm', array($form));
				
				$productKeys = array_keys($productFields);
				$productKeys = implode('|', $productKeys);
				$form->setField('proto', "removeAndRefreshForm={$productKeys}");
				$form->setField('packagingId', 'input=hidden');
				
				// Намираме полетата от артикула
				$productFields = array_diff_key($form->fields, $detailFields);
			} else {
				// Ако не клонираме прототипа е скрит
				$fields = $form->selectFields();
				foreach ($fields as $name => $fl1){
					if($name != 'proto'){
						$form->setField($name, 'input=none');
					}
				}
			}
			
			// След събмит
			if($form->isSubmitted()){
				$rec = $form->rec;
				
				if(isset($cloneRec)){
					$rec->proto = cat_Products::fetchField($rec->productId, 'proto');
				}
				
				$arrRec = (array)$rec;
				
				// Намираме полетата на артикула
				$pRec = (object)(array('proto' => $rec->proto) + array_intersect_key($arrRec, $productFields));
				$pRec->folderId = $masterRec->folderId;
				$pRec->threadId = $masterRec->threadId;
				$pRec->isPublic = 'no';
				
				$protoRec = cat_Products::fetch($rec->productId);
				$pRec->meta = $protoRec->meta;
				
				// Създаваме артикула
				$productId = $Products->save($pRec);
				$dRec = (object)array_diff_key($arrRec, $productFields);
				$dRec->productId = $productId;
				$dRec->packagingId = $pRec->measureId;
				$dRec->quantityInPack = 1;
				
				$mvc->save($dRec);
				
				// Редирект към сделката/офертата
				return Redirect(array($mvc->Master, 'single', $dRec->{$mvc->masterKey}), FALSE, 'Успешно е създаден нов артикул');
			}
			
			// Добавяме бутони на формата
			$folderTitle = doc_Folders::recToVerbal(doc_Folders::fetch($masterRec->folderId))->title;
			$form->title = "Създаване на нов нестандартен артикул в|* {$folderTitle}";
				
			if(isset($form->rec->proto)){
				$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис');
			} else {
				$form->toolbar->addBtn('Запис', array(), 'ef_icon = img/16/disk.png, title = Запис');
			}
			
			$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
				
			// Рендиране на опаковката
			$tpl = $mvc->renderWrapping($form->renderHtml());

			// Връщаме FALSE за да се прекъсне ивента
			return FALSE;
		}
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
		
			if($mvc->haveRightFor('createProduct', (object)array($mvc->masterKey => $rec->{$mvc->masterKey}, 'cloneId' => $rec->id))){
				$url = array($mvc, 'CreateProduct', $mvc->masterKey => $rec->{$mvc->masterKey}, 'cloneId' => $rec->id, 'ret_url' => TRUE);
				
				if($mvc->hasPlugin('plg_RowTools2')){
					core_RowToolbar::createIfNotExists($row->_rowTools);
					$row->_rowTools->addLink('Клониране', $url, "id=btnNewProduct,title=Създаване на нов нестандартен артикул", 'ef_icon = img/16/clone.png,order=12');
				}
			}
	}
}