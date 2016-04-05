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
			
			$form = cls::get('core_Form');
			$form->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Прототип,input,silent,removeAndRefreshForm=name2,placeholder=Популярни продукти,mandatory");
			$form->FLD('innerClass', "class(interface=cat_ProductDriverIntf, allowEmpty, select=title)", "caption=Вид,silent,refreshForm,after=id,input=hidden");
			$form->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=hidden');
			$form->FLD('price', 'double', 'caption=Цена,input=hidden');
			
			$form->fields['price']->unit = "|*" . $masterRec->currencyId . ", ";
			$form->fields['price']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
			
			// В кои категории може да има прототипни артикули
			$options = self::getProtoOptions($mvc->filterProtoByMeta);
			if(isset($cloneId)){
				$options[$cloneRec->productId] = cat_Products::getTitleById($cloneRec->productId, FALSE);
				$form->setReadOnly('proto', $cloneRec->productId);
				$form->setDefault('proto', $cloneRec->productId);
			}
			
			$form->setOptions('proto', $options);
			$form->input(NULL, 'silent');
			
			if(isset($form->rec->proto)){
				$form->setField('quantity', 'input');
				$form->setField('price', 'input,mandatory');
				$proto = $form->rec->proto;
				cat_Products::setAutoCloneFormFields($form, $proto);
				
				$form->rec = cat_Products::fetch($proto);
				foreach (array('code', 'threadId', 'folderId', 'id', 'createdOn', 'createdBy', 'modifiedOn', 'modifiedBy', 'containerId', 'isPublic') as $fld){
					unset($form->rec->{$fld});
				}
				
				$form->rec->proto = $proto;
				if(isset($cloneRec)){
					$form->setField('quantity', 'input=none');
					$form->setField('price', 'input=none');
				}
				
				$Driver = cat_Products::getDriver($proto);
				$Driver->invoke('AfterPrepareEditForm', array($Products, (object)array('form' => $form)));
				
				$keys = array_keys($form->selectFields());
				unset($keys[0]);
				$keys = implode('|', $keys);
				$form->setField('proto', "removeAndRefreshForm={$keys}");
				
				$rec = $form->rec;
				$rec->folderId = $masterRec->folderId;
				$rec->threadId = $masterRec->threadId;
				$rec->isPublic = 'no';
				
				$form->input();
				$Products->invoke('AfterInputEditForm', array($form));
			} else {
				$form->input();
			}
			
			if($form->isSubmitted()){
				
				$rec->quantity = ($rec->quantity) ? $rec->quantity : cat_UoM::fetchField($rec->measureId, 'defQuantity');
				if(empty($rec->quantity)){
					$form->setError('quantity', 'Няма количество');
				}
				
				if(!$form->gotErrors()){
					$vat = acc_Periods::fetchByDate($masterRec->valior)->vatRate;
					$price = deals_Helper::getPurePrice($rec->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
					
					$productId = $Products->save($rec);
					
					$dRec = (object)array('productId'      => $productId,
										  $mvc->masterKey  => $masterId,
										  'packagingId'    => $rec->measureId,
										  'quantityInPack' => 1,
										  'showMode'       => 'detailed',
										  'quantity'       => $rec->quantity,
										  'price'          => $price,
					);
					
					if(isset($cloneRec)){
						foreach (array('packagingId', 'quantityInPack', 'quantity', 'price', 'discount', 'tolerance') as $fld){
							$dRec->{$fld} = $cloneRec->{$fld};
						}
					}
					
					$mvc->save($dRec);
					
					return Redirect(array($mvc->Master, 'single', $masterId), FALSE, 'Успешно е създаден нов артикул');
				}
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