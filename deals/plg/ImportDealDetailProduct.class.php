<?php



/**
 * Плъгин за импорт на артикули към бизнес документи. Закача се към техен детайл който има интерфейс 'deals_DealImportCsvIntf'
 * 
 * Целта е да се уточни:
 * 1. Как се въвеждат csv данните с ъплоуд на файл или с copy & paste
 * 2. Какви са разделителят, ограждането и първия ред на данните
 * 3. Кои колони от csv-тo на кои полета от мениджъра отговарят.
 *
 * След определянето на тези данни драйвъра се грижи за правилното импортиране
 *
 * Мениджъра в който ще се импортира и кои полета от него ще бъдат попълнени
 * се определя от драйвъра.
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_ImportDealDetailProduct extends core_Plugin
{
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->declareInterface('deals_DealImportProductIntf');
	}
	
	
	
	/**
	 * Преди всеки екшън на мениджъра-домакин
	 */
	public static function on_BeforeAction($mvc, &$tpl, $action)
	{
		if($action == 'import'){
			$mvc->requireRightFor('import');
			expect($masterId = Request::get($mvc->masterKey, 'int'));
			$mvc->requireRightFor('import', (object)array($mvc->masterKey => $masterId));
			
			$mvc->requireRightFor('import');
			$form = cls::get('core_Form');
			
			$cu = core_Users::getCurrent();
			$cacheRec = core_Cache::get($mvc->className, "importProducts{$cu}");
			
			// Подготвяме формата
			$form->FLD($mvc->masterKey, "key(mvc={$mvc->Master->className})", 'input=hidden,silent');
			$form->input(NULL, 'silent');
			$form->title = 'Импортиране на артикули към|*' . " <b>" . $mvc->Master->getRecTitle($form->rec->{$mvc->masterKey}) . "</b>";
			$form->FLD('folderId', "int", 'input=hidden');
			$form->setDefault('folderId', $mvc->Master->fetchField($form->rec->{$mvc->masterKey}, 'folderId'));
			
			self::prepareForm($form);
			
			if($cacheRec){
				foreach ($cacheRec as $name => $value){
					$form->rec->{$name} = $value;
				}
			}
			
			$form->input();
			
			// Ако формата е импутната
			if($form->isSubmitted()){
				$rec = &$form->rec;
				
				// Трябва да има посочен източник
				if((empty($rec->csvData) && empty($rec->csvFile))){
					$form->setError('csvData,csvFile', 'Трябва да е попълнено поне едно от полетата');
				}
				
				// Трябва да има посочен източник
				if((!empty($rec->csvData) && !empty($rec->csvFile))){
					$form->setError('csvData,csvFile', 'Трябва да е попълнено само едно от полетата');
				}
				
				if(!$form->gotErrors()){
					$data = ($rec->csvFile) ? bgerp_plg_Import::getFileContent($rec->csvFile) : $rec->csvData;
					
                    $delimiter = $rec->delimiter == '\t' ? "\t" : $rec->delimiter;
					
					// Обработваме данните
					$rows = csv_Lib::getCsvRows($data, $delimiter, $rec->enclosure, $rec->firstRow);
					$fields = array('code' => $rec->codecol, 'quantity' => $rec->quantitycol, 'price' => $rec->pricecol, 'pack' => $rec->packcol);
					
					if(core_Packs::isInstalled('batch')){
						$fields['batch'] = $rec->batchcol;
					}
					
					if(!count($rows)){
						$form->setError('csvData,csvFile', 'Не са открити данни за импорт');
					}
					
					// Ако можем да импортираме импортираме
					if($mvc->haveRightFor('import')){
						
						// Обработваме и проверяваме данните
						if($msg = self::checkRows($rows, $fields, $rec->folderId, $mvc)){
							$form->setError('csvData', $msg);
						}
						
						if(!$form->gotErrors()){
						
							// Импортиране на данните от масива в зададените полета
							$msg = self::importRows($mvc, $rec->{$mvc->masterKey}, $rows, $fields);
							
							self::cacheImportParams($mvc, $rec);
							$mvc->Master->logWrite('Импортиране на артикули', $rec->{$mvc->masterKey});
							
							// Редирект кум мастъра на документа към който ще импортираме
							redirect(array($mvc->Master, 'single', $rec->{$mvc->masterKey}), FALSE, '|' . $msg);
						}
					}
				}
			}
			
			if(core_Users::haveRole('partner')){
				$mvc->currentTab = 'Нишка';
				plg_ProtoWrapper::changeWrapper($mvc, 'cms_ExternalWrapper');
			}
			
			// Рендиране на опаковката
			$tpl = $mvc->renderWrapping($form->renderHtml());
	
			return FALSE;
		}
	}
	
	
	/**
	 * Проверява и обработва записите за грешки
	 */
	private static function checkRows(&$rows, $fields, $folderId, $mvc)
	{
		$err = array();
		$msg = FALSE;
		
		$isPartner = core_Users::haveRole('partner');
		$batchInstalled = core_Packs::isInstalled('batch');
		
		foreach ($rows as $i => &$row){
			$hasError = FALSE;
			
			// Подготвяме данните за реда
			$obj = (object)array('code'     => $row[$fields['code']],
								 'quantity' => $row[$fields['quantity']],
								 'pack'     => ($row[$fields['pack']]) ? $row[$fields['pack']] : NULL,
					             'price'    => $row[$fields['price']] ? $row[$fields['price']] : NULL,
								 'batch'     => ($row[$fields['batch']]) ? $row[$fields['batch']] : NULL,
			);
		
			// Подсигуряваме се, че подадените данни са във вътрешен вид
			$obj->code = cls::get('type_Varchar')->fromVerbal($obj->code);
			$obj->quantity = cls::get('type_Double')->fromVerbal($obj->quantity);
			
            if(!strlen($obj->code)){
				$err[$i][] = $obj->code . ' |Липсващ код|*';
                continue;
			}
			
			$pRec = cat_Products::getByCode($obj->code);
		    
            if(!$pRec){
				$err[$i][] = $obj->code . ' |Няма продукт с такъв код|*';
                continue;
			}

            $packs = cat_Products::getPacks($pRec->productId);
			
            if(isset($obj->pack)){

                $obj->exPack = $obj->pack;

				$packId = cat_UoM::fetchBySinonim($obj->pack)->id;

				if(!$packId){
                    foreach($packs as $pId => $pName) {
                        if(strpos($obj->pack, $pName) !== FALSE) {
                             $packId = $pId;
                             break;
                        }
                    }
                }
                if($packId) {
                    $obj->pack = $packId;
                }
			} else {
				$obj->pack = key($packs);
			}

			if($obj->price){
				if($isPartner === FALSE){
					$obj->price = cls::get('type_Varchar')->fromVerbal($obj->price);
					if(!$obj->price){
						$err[$i][] = $obj->code . "|Грешна цена|*";
					}
				}
			}
			
			if(!isset($obj->price)){
				$Cover = doc_Folders::getCover($folderId);
				$Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get('price_ListToCustomers');
				$policyInfo = $Policy->getPriceInfo($Cover->getInstance()->getClassId(), $Cover->that, $pRec->productId, NULL, 1);
				
				if(empty($policyInfo->price)){
					$err[$i][] = $obj->code . " |Артикулът няма цена|*";
				}
			}
			
			if(!$obj->quantity){
				$err[$i][] = $obj->code . ' |Липсващо количество|*';
			}
			
			if($pRec && isset($obj->pack)){
				if(isset($pRec->packagingId) && $pRec->packagingId != $obj->pack){
					$err[$i][] = $obj->code . '|Подадения баркод е за друга опаковка|*';
				}
				  
				if(!array_key_exists($obj->pack, $packs)){
					$err[$i][] = $obj->code . ' |Артикулът не поддържа подадената мярка/опаковка|* (' . implode(',', $packs) . ')';
				}
			}
			
			// Проверка за точност на к-то
			if(isset($obj->quantity)){
				if($pRec){
					$packagingId = isset($pRec->packagingId) ? $pRec->packagingId : cat_Products::fetchField($pRec->productId, 'measureId');
					if(!deals_Helper::checkQuantity($packagingId, $obj->quantity, $warning)){
						$err[$i][] = $warning;
					}
				}
			}
			
			// Ако е инсталиран пакета за партидност и има партида
			if($batchInstalled && isset($obj->batch) && isset($pRec->productId)){
				if($batchDef = batch_Defs::getBatchDef($pRec->productId)){
					$batchType = $batchDef->getBatchClassType();
					$obj->batch = $batchType->fromVerbal($obj->batch);
					$r = $batchType->isValid($obj->batch);
					
					if(!$obj->batch || !empty($r['error'])){
						$error = !empty($r['error']) ? $r['error'] : $batchType->error;
						$err[$i][] = $obj->batch . " |{$error}|*";
						continue;
					}
					
					$obj->batch = $batchDef->denormalize($obj->batch);
					if(!$batchDef->isValid($obj->batch, $obj->quantity, $msg)){
						$msg = str_replace(',', ' ', $msg);
						$err[$i][] = $obj->batch . " {$msg}";
						continue;
					}
				} else {
					$err[$i][] = $obj->batch . ' |Продукта не поддържа партидност|*';
					continue;
				}
			}
			
			if($isPartner === TRUE){
				unset($obj->price);
			}
			
			$row = clone $obj;
		}
		
		if(count($err)){
			$msg = "|Има проблем със следните редове|*:";
			$msg .= "<ul>";
			foreach($err as $j => $r){
				$errMsg = implode(', ', $r);
				$msg .= "|*<li>|Ред|* '{$j}' - {$errMsg}" . "</li>";
			}
			$msg .= "</ul>";
		}
		
		return $msg;
	}
	

	/**
	 * Импортиране на записите ред по ред от мениджъра
	 */
	private static function importRows($mvc, $masterId, $rows, $fields)
	{
		$added = $failed = 0;
		
		foreach ($rows as $row){
				
			// Опитваме се да импортираме записа
			try{
				if($mvc->import($masterId, $row)){
					$added++;
				}
			} catch(core_exception_Expect $e){
				$failed++;
			}
		}
	
		$msg = ($added == 1) ? "|Импортиран е|* 1 |артикул|*" : "|Импортирани са|* {$added} |артикула|*";
		if($failed != 0){
			$msg .= ". |Не са импортирани|* {$failed} |артикула";
		}
	
		return $msg;
	}
	
	
	/**
	 * Кешира данните от последното импортиране на потребителя за документа
	 */
	private static function cacheImportParams($mvc, $rec)
	{
		$cu = core_Users::getCurrent();
		$key = "importProducts{$cu}";
		
		core_Cache::remove($mvc->className, $key);
		$nRec = (object)array('delimiter'   => $rec->delimiter, 
						      'enclosure'   => $rec->enclosure, 
						      'firstRow'    => $rec->firstRow, 
						      'codecol'     => $rec->codecol, 
						      'quantitycol' => $rec->quantitycol, 
						      'pricecol'    => $rec->pricecol);
		
		
		core_Cache::set($mvc->className, $key, $nRec, 1440);
	}
	
	
	/**
	 * Подготовка на формата за импорт на артикули
	 * @param unknown $form
	 */
	private static function prepareForm(&$form)
	{
		// Полета за орпеделяне на данните
		$form->info = tr('Въведете данни или качете csv файл');
		$form->FLD("csvData", 'text(1000000)', 'width=100%,caption=Данни');
		$form->FLD("csvFile", 'fileman_FileType(bucket=bnav_importCsv)', 'width=100%,caption=CSV файл');
		
		// Настройки на данните
		$form->FLD("delimiter", 'varchar(1,size=5)', 'width=100%,caption=Настройки->Разделител,maxRadio=5');
		$form->FLD("enclosure", 'varchar(1,size=3)', 'width=100%,caption=Настройки->Ограждане');
		$form->FLD("firstRow", 'enum(data=Данни,columnNames=Имена на колони)', 'width=100%,caption=Настройки->Първи ред');
		$form->setOptions("delimiter", array(',' => ',', ';' => ';', ':' => ':', '|' => '|', '\t' => 'Таб'));
		$form->setSuggestions("enclosure", array('"' => '"', '\'' => '\''));
		$form->setDefault("delimiter", ',');
		$form->setDefault("enclosure", '"');
		
		// Съответстващи колонки на полета
		$form->FLD('codecol', 'int', 'caption=Съответствие в данните->Код,unit=колона,mandatory');
		$form->FLD('quantitycol', 'int', 'caption=Съответствие в данните->Количество,unit=колона,mandatory');
		$form->FLD('packcol', 'int', 'caption=Съответствие в данните->Мярка/Опаковка,unit=колона');
		
		$fields = array('codecol', 'quantitycol', 'packcol');
		if(!core_Users::haveRole('partner')){
			$form->FLD('pricecol', 'int', 'caption=Съответствие в данните->Цена,unit=колона');
			$fields[] = 'pricecol';
		}
		
		if(core_Packs::isInstalled('batch')){
			$form->FLD('batchcol', 'int', 'caption=Съответствие в данните->Партида,unit=колона');
			$fields[] = 'batchcol';
		}
		
		foreach ($fields as $i => $fld){
			$form->setSuggestions($fld, array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7));
			$form->setDefault($fld, $i + 1);
		}
		
		$form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title = Импорт');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
	}
	
	
	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, $data)
	{
		$masterRec = $data->masterData->rec;
		
		if($mvc->haveRightFor('import', (object)array("{$mvc->masterKey}" => $masterRec->id))){
			$data->toolbar->addBtn('Импортиране', array($mvc, 'import', "{$mvc->masterKey}" => $masterRec->id, 'ret_url' => TRUE),
			"id=btnAdd-import,{$error},title=Импортиране на артикули", 'ef_icon = img/16/import.png,order=15');
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($requiredRoles == 'no_one') return;
		
		if($action == 'import' && isset($rec->{$mvc->masterKey})){
			if($mvc instanceof sales_SalesDetails){
				$roles = sales_Setup::get('ADD_BY_IMPORT_BTN');
				if(!haveRole($roles, $userId)){
    				$requiredRoles = 'no_one';
    			}
			} else {
				if(!$mvc->haveRightFor('add', $rec)){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
}