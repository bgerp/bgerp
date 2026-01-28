<?php


/**
 * Модел за "Детайл на зоните"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_ZoneDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайл на зоните';
    
    
    /**
     * Кой може да листва?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да променя партидите?
     */
    public $canModifybatch = 'ceo,rack';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'zoneId';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'movementsHtml';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'productId, batch, status=Състояние,movementsHtml=@, packagingId, batch';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    public $hashField = 'id';
    
    
    /**
     * Шаблон за реда в листовия изглед
     */
    public $tableRowTpl = "[#ROW#][#ADD_ROWS#]\n";

    
    /**
     * Шаблон за реда в листовия изглед
     */
    public static $allocatedMovements = array();

    /**
     * Кой може да редактира детайла на документа
     */
    public $canEditdetailindocument = 'powerUser';


    /**
     * Кеш на продуктови опаковки
     */
    public $cachePacks = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=rack_Zones)', 'caption=Зона, input=hidden,silent,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,tdClass=productCell nowrap');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,removeAndRefreshForm=quantity|quantityInPack|displayPrice,tdClass=nowrap rack-quantity');
        $this->FLD('batch', 'varchar', 'caption=Партида,tdClass=rack-zone-batch,notNull');
        $this->FLD('documentQuantity', 'double(smartRound)', 'caption=Очаквано,mandatory');
        $this->FLD('movementQuantity', 'double(smartRound)', 'caption=Нагласено,mandatory');
        $this->FNC('status', 'varchar', 'tdClass=zone-product-status');

        $this->setDbIndex('zoneId,productId,packagingId,batch'); // най-честият филтър
        $this->setDbIndex('productId,packagingId,batch');        // вторичен
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (is_object($rec)) {
            $packRec = $mvc->cachePacks["{$rec->productId}|{$rec->packagingId}"];
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->movementQuantity = $rec->movementQuantity / $rec->quantityInPack;
            $rec->documentQuantity = $rec->documentQuantity / $rec->quantityInPack;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $isInline = Mode::get('inlineDetail');
        if(!Mode::is('printing')){
            $row->productId = $isInline ?  ht::createLinkRef(cat_Products::getTitleById($rec->_productRec, 'name'), array('cat_Products', 'single', $rec->productId)) : cat_Products::getShortHyperlink($rec->productId, true);        }

        $movementQuantity = core_Math::roundNumber($rec->movementQuantity);
        $documentQuantity = core_Math::roundNumber($rec->documentQuantity);
        $movementQuantityVerbal = $mvc->getFieldType('movementQuantity')->toVerbal($movementQuantity);
        $documentQuantityVerbal = $mvc->getFieldType('documentQuantity')->toVerbal($documentQuantity);
        $moveStatusColor = (round($rec->movementQuantity, 4) < round($rec->documentQuantity, 4)) ? '#ff7a7a' : (($rec->movementQuantity == $rec->documentQuantity) ? '#ccc' : '#8484ff');
        $row->status = "<span style='color:{$moveStatusColor} !important'>{$movementQuantityVerbal}</span> / <b>{$documentQuantityVerbal}</b>";

        core_Debug::startTimer("GET_MOVEMENT_BATCH_INFO_{$rec->zoneId}");
        if ($Definition = batch_Defs::getBatchDef($rec->_productRec)) {
            if(!empty($rec->batch)){
                $row->batch = $Definition->toVerbal($rec->batch);
                if(rack_ProductsByBatches::haveRightFor('list')){
                    $row->batch = ht::createLinkRef($row->batch, array('rack_ProductsByBatches', 'list', 'search' => $rec->batch));
                }
            } else {
                $row->batch = "<span class='quiet'>" . tr('Без партида') . "</span>";
            }

            if(!$isInline){
                if($mvc->haveRightFor('modifybatch', $rec)){
                    $row->batch .= ht::createLink('', array($mvc, 'modifybatch', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/arrow_refresh.png,title=Промяна на партидата');
                }
            }
        } else {
            $row->batch = null;
        }
        core_Debug::stopTimer("GET_MOVEMENT_BATCH_INFO_{$rec->zoneId}");
    }
    
    
    /**
     * След рендиране на детайлите се скриват ценовите данни от резултатите
     * ако потребителя няма права
     */
    protected static function on_AfterPrepareDetail($mvc, $res, &$data)
    {
        if(!countR($data->rows)) return;
        setIfNot($data->inlineDetail, false);
        setIfNot($data->masterData->rec->_isSingle, !$data->inlineDetail);
        $requestedProductId = Request::get('productId', 'int');
        if(Mode::is('printing')){
            $data->filter = 'notClosed';
        }
        
        // >>> ПОДМЯНА НА СТАТУСА: лявото число = реално изпълненото (active+closed) за текущия документ
        // Контейнерът (документът), вързан към текущата зона
        $containerId = rack_Zones::fetchField($data->masterData->rec->id, 'containerId');

        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];
            $row->_code = !empty($rec->_productRec->code) ? $rec->_productRec->code : "Art{$rec->productId}";

            $row->ROW_ATTR['class'] = 'row-added';
            core_Debug::startTimer("GET_MOVEMENTS_PREPARE_INLINE_MOVEMENTS");
            $movementsHtml = self::getInlineMovements($rec, $data->masterData->rec, $data->filter);
            core_Debug::stopTimer("GET_MOVEMENTS_PREPARE_INLINE_MOVEMENTS");
            if(!empty($movementsHtml)){
                $row->movementsHtml = $movementsHtml;
            }

            // Филтър по productId (ако е подаден)
            if((isset($requestedProductId) && $rec->productId != $requestedProductId)){
                unset($data->rows[$id]);
                continue;
            }

            core_Debug::startTimer("GET_MOVEMENTS_PREPARE_STATUS_FOR_DOC");

            // По подразбиране ползваме нагласеното/заявеното (ver1/ver2)
            $leftShown  = (float)$rec->movementQuantity;
            $rightShown = (float)$rec->documentQuantity;

            if ($containerId) {
                // Сумираме реално изпълненото в БАЗОВА мярка за ТОЗИ документ (states = active+closed)
                $doneBase = 0.0;

                $q = rack_Movements::getQuery();
                $q->in('state', array('active','closed'));
                $q->where("#productId = {$rec->productId}");

                if ($rec->batch !== null && $rec->batch !== '') {
                    $q->where(array("#batch = '[#1#]'", $rec->batch));
                } else {
                    $q->where("#batch IS NULL OR #batch = ''");
                }

                // Само движения, вързани към този документ
                $q->where(array("LOCATE('|[#1#]|', #documents)", (int)$containerId));
                $q->show('zones,quantityInPack');

                while ($m = $q->fetch()) {
                    $zones = type_Table::toArray($m->zones);
                    if (!is_array($zones)) continue;
                    foreach ($zones as $z) {
                        if ((int)$z->zone === (int)$data->masterData->rec->id) {
                            // z->quantity е в "брой опаковки" на движението; quantityInPack → базова мярка
                            $doneBase += (float)$z->quantity * (float)$m->quantityInPack;
                        }
                    }
                }

                // Превръщаме в МЯРКАТА НА РЕДА (опаковката на заявката)
                $qip = (float)$rec->quantityInPack;
                $qip = ($qip != 0.0) ? $qip : 1.0; // пазим се от делене на 0, но НЕ затапваме стойности <1
                $leftShown = $doneBase / $qip;
            }
            core_Debug::stopTimer("GET_MOVEMENTS_PREPARE_STATUS_FOR_DOC");

            // Вербализация и оцветяване
            $movementQuantityVerbal = $mvc->getFieldType('movementQuantity')->toVerbal(core_Math::roundNumber($leftShown));
            $documentQuantityVerbal = $mvc->getFieldType('documentQuantity')->toVerbal(core_Math::roundNumber($rightShown));

            $cmpLeft  = round((float)$leftShown, 4);
            $cmpRight = round((float)$rightShown, 4);
            $moveStatusColor = ($cmpLeft < $cmpRight) ? '#ff7a7a' : (($cmpLeft == $cmpRight) ? '#ccc' : '#8484ff');

            $row->status = "<span style='color:{$moveStatusColor} !important'>{$movementQuantityVerbal}</span> / <b>{$documentQuantityVerbal}</b>";
            // <<< край на подмяната на статуса

            // Ако няма движения и к-та са 0, реда се маркира за скриване
            if((empty($rec->movementQuantity) && empty($rec->documentQuantity) && empty($rec->_movements))){
                unset($data->rows[$id]);
                continue;
            }

            // Подсказка при недостатъчно общо генерирано
            $row->status = new core_ET($row->status);

            // СРАВНЕНИЕ В ОПАКОВКАТА НА ЗАЯВКАТА:
            // - documentQuantity вече е в опаковката на реда (нормализирана в on_BeforeRecToVerbal).
            // - _generatedBase е в базова мярка → делим на request qtyInPack, за да минем в опаковката на реда.
            $genInRequestPack = 0.0;
            if (!empty($rec->_generatedBase)) {
                $qip2 = (float)$rec->quantityInPack;
                $qip2 = ($qip2 != 0.0) ? $qip2 : 1.0; // позволяваме стойности < 1 (напр. 1 бр = 0.001 хил.бр.)
                $genInRequestPack = (float)$rec->_generatedBase / $qip2;
            }

            $docInRequestPack = (float)$rec->documentQuantity;

            // Прецизност: MAX(3, cat_UoM.round) за мярката на реда
            $roundDigits = max(3, (int) cat_UoM::fetchField($rec->packagingId, 'round'));

            $genCmp = round($genInRequestPack, $roundDigits);
            $docCmp = round($docInRequestPack, $roundDigits);

            if ($genCmp < $docCmp) {
				$Double   = core_Type::getByName("double(decimals={$roundDigits})");
				$uomShort = cat_UoM::getShortName($rec->packagingId);

				// Вербализация:
				// - ако количеството е цяло число -> без дробна част
				// - иначе -> с точността на мярката
				$eps = pow(10, -$roundDigits);        // граница за "почти цяло"
				$genDisplayNum = $genInRequestPack;

				if (abs($genDisplayNum - round($genDisplayNum)) < $eps) {
					// цяло число – без десетични знаци
					$DoubleInt  = core_Type::getByName("double(decimals=0)");
					$genDisplay = $DoubleInt->toVerbal($genDisplayNum);
				} else {
					// има реална дробна част – оставяме я
					$genDisplay = $Double->toVerbal($genDisplayNum);
				}

				$row->status = ht::createHint(
					$row->status,
					"Генерираните движения са за по-малко от необходимото (заявеното от документа) количество|*: {$genDisplay} {$uomShort}",
					'warning',
					false
				);
				$row->status->prepend("<span class='notEnoughQuantityForZone'>");
				$row->status->append("</span>");
			}
			
			// Бутон за връщане на нагласено количество от зоната (минусче)
			// (по дизайн се показва само ако изпълненото е повече от заявеното)
			if (!Mode::is('printing') && $cmpLeft > $cmpRight && $mvc->haveRightFor('returnquantitymovement')) {
				$minusBtn = ht::createLink(
					'',
					array($mvc, 'returnquantitymovement', $rec->id, 'ret_url' => true),
					false,
					'class=minusImg,ef_icon=img/16/minus-white.png,title=Връщане на нагласено количество'
				);

				// $row->status вече е core_ET, затова добавяме бутона в началото
				$row->status->prepend($minusBtn);
			}

            if($mvc->haveRightFor('editdetailindocument', $rec)){
                $changeBtn = ht::createLink('', array($mvc, 'editdetailindocument', $rec->id, 'ret_url' => true), 'Наистина ли искате да промените количеството в реда на документа|*?', 'class=changeQuantityBtn,ef_icon=img/16/arrow_refresh.png,title=Задаване на това количество в реда на документа');
                $row->status->append($changeBtn);
            }
        }

        arr::sortObjects($data->rows, '_code', 'asc', 'str');
    }
    
    
    /**
     * Записва движение в зоната
     *
     * @param int   $zoneId      - ид на зона
     * @param int   $productId   - ид на артикул
     * @param int   $packagingId - ид на опаковка
     * @param float $quantity    - количество в основна мярка
     * @param string $batch      - ид на опаковка
     *
     * @return void
     */
    public static function recordMovement($zoneId, $productId, $packagingId, $quantity, $batch)
    {
        $batch = (string) (isset($batch) ? $batch : '');
		$condBatch = ($batch === '') ? "(#batch IS NULL OR #batch = '')" : "#batch = '[#1#]'";

		if ($batch === '') {
			$newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$productId} AND #packagingId = {$packagingId} AND {$condBatch}");
		} else {
			$newRec = self::fetch(array("#zoneId = {$zoneId} AND #productId = {$productId} AND #packagingId = {$packagingId} AND {$condBatch}", $batch));
		}
        if (empty($newRec)) {
            $newRec = (object) array('zoneId' => $zoneId, 'productId' => $productId, 'packagingId' => $packagingId, 'movementQuantity' => 0, 'documentQuantity' => null, 'batch' => $batch);
        }
        $newRec->movementQuantity += $quantity;
        $newRec->movementQuantity = round($newRec->movementQuantity, 4);
       
        self::save($newRec);
    }
	
	
	/**
	 * Връща packagingId от заявката (documentQuantity) за даден zoneId+productId+batch.
	 * Ако няма – връща NULL.
	 */
	public static function getRequestedPackagingId($zoneId, $productId, $batch)
	{
		$batch = (string) (isset($batch) ? $batch : '');

		$q = self::getQuery();
		$q->where("#zoneId = {$zoneId} AND #productId = {$productId}");
		if ($batch === '') {
			$q->where("(#batch IS NULL OR #batch = '')");
		} else {
			$q->where(array("#batch = '[#1#]'", $batch));
		}
		$q->where("#documentQuantity IS NOT NULL AND #documentQuantity <> 0");
		$q->show('packagingId,documentQuantity');
		$q->orderBy('documentQuantity', 'DESC');
		$q->limit(1);

		if ($r = $q->fetch()) {
			return (int)$r->packagingId;
		}

		return null;
	}
    
    
    /**
     * Синхронизиране на зоните с документа
     *
     * @param int $zoneId
     * @param int $containerId
     */
    public static function syncWithDoc($zoneId, $containerId = null)
    {
        $notIn = array();
        if (isset($containerId)) {
            $document = doc_Containers::getDocument($containerId);
            $products = $document->getProductsSummary();
            
            if (countR($products)) {
                foreach ($products as $obj) {
                    $batch = empty($obj->batch) ? '' : (string)$obj->batch;

					if ($batch === '') {
						$newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$obj->productId} AND #packagingId = {$obj->packagingId} AND (#batch IS NULL OR #batch = '')");
					} else {
						$newRec = self::fetch(array("#zoneId = {$zoneId} AND #productId = {$obj->productId} AND #packagingId = {$obj->packagingId} AND #batch = '[#1#]'", $batch));
					}

					if (empty($newRec)) {
						$newRec = (object) array(
							'zoneId' => $zoneId,
							'productId' => $obj->productId,
							'packagingId' => $obj->packagingId,
							'batch' => $batch,
							'movementQuantity' => null,
							'documentQuantity' => 0
						);
					} else {
						$newRec->batch = $batch; // нормализация и при съществуващ
                    }
                    $newRec->documentQuantity = $obj->quantity;
                    if(!empty($newRec->documentQuantity)){
                        $newRec->documentQuantity = round($newRec->documentQuantity, 4);
                    }
                    
                    self::save($newRec);
                    $notIn[$newRec->id] = $newRec->id;
                }
            }
        }
        
        // Зануляват се к-та от документ освен на променените записи
        self::nullifyQuantityFromDocument($zoneId, $notIn);
    }
    
    
    /**
     * Зануляване на очакваното количество по документи
     *
     * @param int   $zoneId
     * @param array $notIn
     */
    private static function nullifyQuantityFromDocument(int $zoneId, array $notIn = array())
    {
        $query = self::getQuery();
        $query->where("#zoneId = {$zoneId}");
        $query->where('#documentQuantity IS NOT NULL');
        if (countR($notIn)) {
            $query->notIn('id', $notIn);
        }
        
        while ($rec = $query->fetch()) {
            $rec->documentQuantity = null;
            self::save($rec);
        }
    }
    
    
    /**
     * Изчислява какво количество от даден продукт е налично в зоните
     * 
     * @param int $productId
     * @param int $storeId
     * @return number $res
     */
    public static function calcProductQuantityOnZones($productId, $storeId = null, $batch = null)
    {
        $query = self::getQuery();
        $query->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $query->XPR('sum', 'double', 'sum(#movementQuantity)');
        $query->where("#productId = {$productId}");
        if(isset($storeId)){
            $query->where("#storeId = {$storeId}");
        }
        if(isset($batch)){
            $query->where(array("#batch = '[#1#]'", $batch));
        }
        
        $rec = $query->fetch();
        $res =  ($rec) ? $rec->sum : 0;
        
        return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        $storeId = rack_Zones::fetchField($rec->zoneId, 'storeId');
        
        // Рекалкулира какво е количеството по зони на артикула в склад-а
        rack_Products::recalcQuantityOnZones($rec->productId, $storeId);

        if(core_Packs::isInstalled('batch')){
            $bItemRec = rack_ProductsByBatches::fetch(array("#productId = {$rec->productId} AND #batch = '[#1#]' AND #storeId = {$storeId}", $rec->batch));
            if(is_object($bItemRec)){
                $bItemRec->quantityOnZones = rack_ZoneDetails::calcProductQuantityOnZones($rec->productId, $storeId, $rec->batch);
                rack_ProductsByBatches::save($bItemRec, 'quantityOnZones');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('documentQuantity', 'DESC');
    }
    
    
    /**
     * Рендиране на детайла накуп
     * 
     * @param stdClass $masterRec
     * @param core_Mvc $masterMvc
     * @param string $additional
     * @return core_ET
     */
    public static function renderInlineDetail($masterRec, $masterMvc, $additional = null)
    {
        $additional = !empty($additional) ? $additional : 'pendingAndMine';
        setIfNot($additional, 'pendingAndMine');

        $cu = core_Users::getCurrent();
        $tpl = core_Cache::get("rack_Zones_{$masterRec->id}", "{$cu}|{$additional}");

        if(!($tpl instanceof core_ET)) {
            $tpl = new core_ET("");

            Mode::push('inlineDetail', true);
            $me = cls::get(get_called_class());

            $dData = (object)array('masterId' => $masterRec->id, 'masterMvc' => $masterMvc, 'masterData' => (object)array('rec' => $masterRec), 'listTableHideHeaders' => true, 'inlineDetail' => true, 'filter' => $additional);

            core_Debug::startTimer("GET_MOVEMENTS_PREPARE_{$masterRec->id}");
            $dData = $me->prepareDetail($dData);
            core_Debug::stopTimer("GET_MOVEMENTS_PREPARE_{$masterRec->id}");
            if(!countR($dData->recs)) return $tpl;
            unset($dData->listFields['id']);

            core_Debug::startTimer("GET_MOVEMENTS_RENDER_{$masterRec->id}");
            $tpl = $me->renderDetail($dData);
            core_Debug::stopTimer("GET_MOVEMENTS_RENDER_{$masterRec->id}");
            $tpl->removePlaces();
            $tpl->removeBlocks();
            Mode::pop('inlineDetail');

            core_Cache::set("rack_Zones_{$masterRec->id}", "{$cu}|{$additional}", $tpl, 10);
            core_Debug::log("GET_MOVEMENTS_SET_CACHE {$masterRec->id}");
        } else {
            core_Debug::log("GET_MOVEMENTS_FROM_CACHE++ {$masterRec->id}");
        }

        return $tpl;
    }
    
    
    /**
     * Рендира таблицата със движения към детайла на зоната
     *
     * @param stdClass $rec
     * @return string filter
     */
    private static function getInlineMovements(&$rec, &$masterRec, $filter)
    {
        $Movements = clone cls::get('rack_Movements');
        $Movements->FLD('_rowTools', 'varchar', 'tdClass=small-field');

        $data = (object) array('recs' => array(), 'rows' => array(), 'listTableMvc' => $Movements, 'inlineMovement' => true);
        $data->listFields = arr::make('movement=Движение,leftColBtns,rightColBtns,workerId=Работник', true);
        if($masterRec->_isSingle === true){
            $data->listFields['modifiedOn'] = 'Модифициране||Modified->На||On';
            $data->listFields['modifiedBy'] = 'Модифициране||Modified->От||By';
        }

        if(Mode::is('printing')){
            unset($data->listFields['leftColBtns']);
            unset($data->listFields['rightColBtns']);
        }

        $Movements->setField('workerId', "tdClass=inline-workerId");
        core_Debug::startTimer("GET_MOVEMENTS_PREPARE_GET_CURRENT_RECS");
        $movementArr = rack_Zones::getCurrentMovementRecs($rec->zoneId, $filter);
        core_Debug::stopTimer("GET_MOVEMENTS_PREPARE_GET_CURRENT_RECS");
        $allocated = &rack_ZoneDetails::$allocatedMovements[$rec->zoneId];
        $allocated = is_array($allocated) ? $allocated : array();

        list($productId, $packagingId, $batch) = array($rec->productId, $rec->packagingId, $rec->batch);

        // При ver3 показваме всички движения за продукта/партидата в зоната,
        // независимо в каква опаковка са (MG3 разцепва по опаковки).
        if (rack_Setup::get('PICKUP_STRATEGY') == 'ver3') {
            $data->recs = array_filter($movementArr, function($o) use ($productId, $batch, $allocated) {
                return (int)$o->productId === (int)$productId
                    && (string)$o->batch === (string)$batch
                    && !array_key_exists($o->id, $allocated);
            });
        } else {
            // Старо поведение за ver1/ver2 – привързано към опаковката на реда
            $data->recs = array_filter($movementArr, function($o) use ($productId, $packagingId, $batch, $allocated) {
                return (int)$o->productId === (int)$productId
                    && (int)$o->packagingId === (int)$packagingId
                    && (string)$o->batch === (string)$batch
                    && !array_key_exists($o->id, $allocated);
            });
        }

        if(countR($data->recs)){
            $masterRec->_noMovements = true;
        }

        $rec->_movements = $data->recs;
		if (countR($rec->_movements)) {
			$allocated += $rec->_movements;
		}

		// Общото "генерирано" количество в базова мярка
        core_Debug::startTimer("GET_MOVEMENTS_PREPARE_GENERATE_BASE");
		$rec->_generatedBase = static::getGeneratedBaseForDetail($masterRec, $rec);
        core_Debug::stopTimer("GET_MOVEMENTS_PREPARE_GENERATE_BASE");

		$requestedProductId = Request::get('productId', 'int');

		foreach ($data->recs as $mRec) {

			// Филтър по productId в терминала (ако има такъв)
			if (isset($requestedProductId) && $mRec->productId != $requestedProductId) {
				continue;
			}

			$fields = $Movements->selectFields();
			$fields['-list']   = true;
			$fields['-inline'] = true;
			if ($masterRec->_isSingle === true) {
				$fields['-inline-single'] = true;
			}
            unset($fields['productId']);
            unset($fields['storeId']);
            unset($fields['batch']);
            unset($fields['packagingId']);

			$mRec->_currentZoneId = $masterRec->id;
            core_Debug::startTimer("GET_MOVEMENTS_PREPARE_RECTOVERBAL");
			$data->rows[$mRec->id] = rack_Movements::recToVerbal($mRec, $fields);
            core_Debug::stopTimer("GET_MOVEMENTS_PREPARE_RECTOVERBAL");
		}

        // Сигнализираме, ако се взема цялото налично количество от палетмястото (т.е. че няма нужда да се брои)
        core_Debug::startTimer("GET_MOVEMENTS_PREPARE_PALLET_WARNING");
        foreach ($data->rows as $mId => &$rRow) {
            $mRec = $data->recs[$mId];
            if (!empty($mRec->palletId) && $mRec->quantity > 0) {
                $availableQty = rack_Pallets::fetchField($mRec->palletId, 'quantity');
                if (!empty($availableQty) && abs($mRec->quantity - $availableQty) < 0.0001) {
                    if (!empty($rRow->movement)) {
                        $rRow->movement = preg_replace(
                            '/\(([^)]+)\)(?=.*»)/u',
                            '( <span style="background:#c0c0c0; border-radius:6px; padding:1px 6px; font-weight:bold; color:#000;" title="Цялото налично количество на позицията!">$1</span> )',
                            $rRow->movement,
                            1 // само първото срещане
                        );
                    }
                }
            }
        }
        core_Debug::stopTimer("GET_MOVEMENTS_PREPARE_PALLET_WARNING");

        // Рендиране на таблицата
        $tpl = new core_ET('');
        if (countR($data->rows) || $masterRec->_isSingle === true) {
            $tableClass = ($masterRec->_isSingle === true && countR($data->rows)) ? 'listTable' : 'simpleTable';
            $table = cls::get('core_TableView', array('mvc' => $data->listTableMvc, 'tableClass' => $tableClass, 'thHide' => true));
            $Movements->invoke('BeforeRenderListTable', array($tpl, &$data));

            core_Debug::startTimer("GET_MOVEMENTS_RENDER_TABLE");
            $tpl->append($table->get($data->rows, $data->listFields));
            core_Debug::stopTimer("GET_MOVEMENTS_RENDER_TABLE");

            $tpl->append("style='width:100%;'", 'TABLE_ATTR');
        }
        
        $tpl->removePendings('COMMON_ROW_ATTR');
        
        return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'modifybatch' && isset($rec)){
            $zoneRec = rack_Zones::fetch($rec->zoneId);
            if(empty($zoneRec->containerId)){
                $requiredRoles = 'no_one';
            } else {
                $Document = doc_Containers::getDocument($zoneRec->containerId);
                if(!$Document->haveRightFor('edit')){
                    $requiredRoles = 'no_one';
                } else {
                    $containerId = $Document->fetchField('containerId');
                    $batchRec = batch_BatchesInDocuments::fetch(array("#containerId = {$containerId} AND #productId = {$rec->productId} AND #batch = '[#1#]' AND #storeId = {$zoneRec->storeId}", $rec->batch));
                    if(!is_object($batchRec)){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }

        if ($action == 'returnquantitymovement') {
            // Правата за обратно движение се свеждат до правата
            // за добавяне на движение в rack_Movements.
            // Проверка дали реално има излишно количество се прави
            // вътре в act_ReturnQuantityMovement().
            $requiredRoles = rack_Movements::getRequiredRoles('add', null, $userId);
        }

        // Кой може от зоната да редактира реда в детайла на документа
        if($action == 'editdetailindocument' && isset($rec)){
            $zoneRec = rack_Zones::fetch($rec->zoneId);
            if(!$zoneRec->containerId){
                $requiredRoles = 'no_one';
            } elseif(empty($rec->movementQuantity)){
                $requiredRoles = 'no_one';
            } elseif($rec->movementQuantity >= $rec->documentQuantity){
                $requiredRoles = 'no_one';
            } else {
                $Document = doc_Containers::getDocument($zoneRec->containerId);
                $editDocumentRoles = $Document->getRequiredRoles('edit');
                if(!haveRole($editDocumentRoles, $userId)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Екшън за промяна на редовете от документа
     *
     * @return Redirect
     * @throws core_exception_Expect
     */
    public function act_modifybatch()
    {
        $this->requireRightFor('modifybatch');
        expect($id = Request::get('id', 'int'));
        expect($rec = static::fetch($id));
        $rId = Request::get('rId', 'int');
        $this->requireRightFor('modifybatch', $rec);
        $zoneRec = rack_Zones::fetch($rec->zoneId);
        $Document = doc_Containers::getDocument($zoneRec->containerId);
        $retUrl = getRetUrl();

        // Кои са записите от документа отговарящи на посочените артикул+партида
        $order = $dRecs = array();
        $bQuery = batch_BatchesInDocuments::getQuery();
        $bQuery->where("#containerId = {$zoneRec->containerId}");
        $bQuery->where(array("#productId = {$rec->productId} AND #storeId = {$zoneRec->storeId} AND #batch = '[#1#]'", $rec->batch));
        $bQuery->orderBy('id', 'ASC');
        while ($bRec = $bQuery->fetch()) {
            $dRecs[$bRec->detailRecId] = array('detailClassId' => $bRec->detailClassId, 'detailRecId' => $bRec->detailRecId, 'quantity' => $bRec->quantity, 'quantityInPack' => $bRec->quantityInPack, 'packagingId' => $bRec->packagingId);
            $order[] = $bRec->detailRecId;
        }

        // Кои са предходните и следващата
        $rId = isset($rId) ? $rId : key($dRecs);
        $index = array_search($rId, $order);
        $nextNum = $index + 1;
        $prevNum = $index - 1;
        $dRec = $dRecs[$rId];
        $recInfo = cls::get($dRec['detailClassId'])->getRowInfo($dRec['detailRecId']);

        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Промяната на партидите в|* ' . $Document->getFormTitleLink();
        $Def = batch_Defs::getBatchDef($rec->productId);
        Mode::push('text', 'plain');
        $batch = $Def->toVerbal($rec->batch);
        Mode::pop('text');
        $batchCaption = str_replace(',', ' ', $batch);
        $key = md5($rec->batch);

        // Добавяне на партидата от изходния ред
        $map = array($key => $rec->batch);
        $measureName = cat_UoM::getShortName($dRec['packagingId']);
        $pCaption = cat_Products::getTitleById($rec->productId);
        $pCaption = "{$pCaption} / {$measureName}";

        $form->FLD($key, "double(min=0)", "caption={$pCaption}->{$batchCaption}");
        $form->setDefault($key, $dRec['quantity'] / $dRec['quantityInPack']);

        $round = cat_UoM::fetchField($dRec['packagingId'], 'round');
        $Double = core_Type::getByName("double(decimals={$round})");

        $quantityPack = $recInfo->quantity / $recInfo->quantityInPack;
        $form->info = tr("Общо на реда|*: <b>") . $Double->toVerbal($quantityPack) . "</b> " . str::getPlural($quantityPack, $measureName, true);

        // Показване на съществуващите налични партиди в склада
        $exBatchArr = batch_Items::getBatchQuantitiesInStore($rec->productId, $zoneRec->storeId, null, null, null, true);
        unset($exBatchArr[$rec->batch]);
        foreach ($exBatchArr as $exBatch => $exQuantity) {
            if($exQuantity <= 0) continue;
            $key = md5($exBatch);
            $map[$key] = $exBatch;

            Mode::push('text', 'plain');
            $batchCaption = $Def->toVerbal($exBatch);
            $batchCaption = str_replace(',', ' ', $batchCaption);
            Mode::pop('text');

            $form->FLD($key, "double(min=0)", "caption=Други партиди в склада->{$batchCaption}");
            Mode::push('text', 'plain');
            $info = "|* / " . $Double->toVerbal($exQuantity / $recInfo->quantityInPack) . " " . str::getPlural($exQuantity, $measureName, true);
            $form->setField($key, "unit={$info}");
            Mode::pop('text');
        }
        $form->input();

        if($form->isSubmitted()){
            $syncArr = array();
            $newArr = (array)$form->rec;
            $msg = "Редът от документа е редактиран успешно|*!";
            $noChange = true;
            $deleteId = null;

            // За всяка инпутната партида
            foreach ($newArr as $k => $v){
                $batch = $map[$k];

                // Ако има съществуващ запис
                $exRec = batch_BatchesInDocuments::fetch(array("#detailClassId = {$dRec['detailClassId']} AND #detailRecId = {$dRec['detailRecId']} AND #batch = '[#1#]'", $batch));
                if($exRec){
                    if($batch == $rec->batch){
                        if(empty($v)){

                            // Ако на изходната партида е посочено празно количество - ще се изтрива
                            $noChange = false;
                            $deleteId = $exRec->id;
                        } else {

                            // Ако изходната партида е променена - ще се обновява
                            $newQuantity = $v * $exRec->quantityInPack;
                            if(round($newQuantity, $round) != round($exRec->quantity, $round)){
                                $syncArr[$batch] = $newQuantity;
                            }
                        }
                    } else {

                        // Ако е посочена друга партида, к-то се добавя към вече съществуваното от нея
                        if(!empty($v)){
                            $syncArr[$batch] = $exRec->quantity + $v * $exRec->quantityInPack;
                        }
                    }
                } elseif(!empty($v)){
                    // Ако е изцяло нова партида ще се добавя
                    $syncArr[$batch] = $v * $dRec['quantityInPack'];
                }
            }

            $sum = array_sum($syncArr);
            if(round($sum, $round) > round($recInfo->quantity, $round)){
                $fieldsError = array();
                $mapReverse = array_flip($map);
                array_walk($syncArr, function ($a, $k) use (&$fieldsError, $mapReverse){$fieldsError[] = $mapReverse[$k];});
                $form->setError(implode(',', $fieldsError), 'Общото количество е над допустимото за реда|*!');
            }

            if(!$form->gotErrors()){
                // Ако има партиди за добавяне/обновяване
                if(countR($syncArr)){
                    $noChange = false;
                    batch_BatchesInDocuments::saveBatches($dRec['detailClassId'], $dRec['detailRecId'], $syncArr);
                }
                if(isset($deleteId)){
                    batch_BatchesInDocuments::delete($deleteId);
                }

                if($form->cmd == 'save_n_prev'){
                    $redirectNum =  $order[$prevNum];
                } elseif($form->cmd == 'save_n_next'){
                    $redirectNum =  $order[$nextNum];
                }

                if($noChange){
                    $msg = "Редът не е променен, защото няма промяна|*!";
                } else {
                    rack_Zones::forceSync($zoneRec->containerId, $zoneRec);
                }

                // Ако формата е събмитната от бутоните за следващ/предходен редирект към следващия/предходния
                if(isset($redirectNum)){
                    return new redirect(array($this, 'modifybatch', 'id' => $id, 'rId' => $redirectNum, 'ret_url' => $retUrl), $msg);
                }

                followRetUrl(null, $msg);
            }
        }

        $batchCount = countR($dRecs);

        // Бутони за напред/назад
        if($batchCount > 1){
            if (isset($order[$nextNum])) {
                $form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=noicon fright,order=30, title = Следващ');
            } else {
                $form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=btn-disabled noicon fright,disabled,order=30, title = Следващ');
            }
            $prevAndNextIndicator = ($index + 1) . "/{$batchCount}";
            $form->toolbar->addFnBtn($prevAndNextIndicator, '', 'class=noicon fright,order=30');
            if (isset($order[$prevNum])) {
                $form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=noicon fright,order=30, title = Предишен');
            } else {
                $form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=btn-disabled noicon fright,disabled,order=30, title = Предишен');
            }
        }

        $form->toolbar->addSbBtn('Промяна', 'save', 'id=btnSave,ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', $retUrl, 'id=back,ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        // Рендиране на формата
        return $tpl;
    }


    /**
     * Екшън генериращ движение за връщане от зоната
     */
    public function act_ReturnQuantityMovement()
	{
		// Проверяваме общите права за обратно движение
		$this->requireRightFor('returnquantitymovement');

		expect($id = Request::get('id', 'int'));
		expect($rec = static::fetch($id));

		// Зона на реда
		$zoneRec = rack_Zones::fetchRec($rec->zoneId);

		// Реално "генерирано/изпълнено" количество за тази зона+документ (в базова мярка)
		$generatedBase = static::getGeneratedBaseForDetail($zoneRec, $rec);

		// Заявеното количество по документа (в базова мярка)
		$documentBase = (float) $rec->documentQuantity;

		// Излишък = изпълнено - заявено
		$overQuantity = round($generatedBase - $documentBase, 7);

		// Ако няма реален излишък – не правим движение
		if ($overQuantity <= 0) {
			followRetUrl(null, '|Няма излишно количество за връщане от тази зона');
		}

		// Създава се обратно движение за излишъка
		$newRec = static::makeReturnQuantityMovement(
			$zoneRec,              // може да е и $rec->zoneId, функцията сама ще направи fetchRec()
			$rec->productId,
			$rec->batch,
			$rec->packagingId,
			$overQuantity          // в базова мярка
		);
		rack_Movements::save($newRec);

		followRetUrl(null, '|Създадено е обратно движение за връщане на останалото количество в зоната');
	}


    /**
     * Генериране на обратно движение за връщане на посоченото к-во от зоната
     *
     * @param mixed $zoneRec    - запис на зона
     * @param int $productId    - ид на артикул
     * @param string $batch     - партида
     * @param bull $packagingId - ид на опаковката
     * @param double $quantity  - к-во за връщане
     * @param int|null $userId  - ид на работник
     * @return object $newRec   - запис на движение
     */
    private static function makeReturnQuantityMovement($zoneRec, $productId, $batch, $packagingId, $quantity, $userId = null)
    {
        $zoneRec = rack_Zones::fetchRec($zoneRec);
        $workerId = isset($userId) ? $userId : core_Users::getCurrent();
        $packRec = cat_products_Packagings::getPack($productId, $packagingId);
        $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;

        $newRec = (object) array('productId' => $productId,
                                 'packagingId' => $packagingId,
                                 'storeId' => $zoneRec->storeId,
                                 'quantityInPack' => $quantityInPack,
                                 'state' => isset($workerId) ? 'waiting' : 'pending',
                                 'brState' => isset($workerId) ? 'pending' : 'null',
                                 'batch' => $batch,
                                 'workerId' => $workerId,
                                 'quantity' => 0,
                                 'positionTo' => rack_PositionType::FLOOR,
        );

        $zoneArr = array('zone' => array($zoneRec->id), 'quantity' => array(-1 * $quantity / $quantityInPack));
        $TableType = core_Type::getByName('table(columns=zone|quantity,captions=Зона|Количество)');
        $newRec->zones = $TableType->fromVerbal($zoneArr);

        return $newRec;
    }


    /**
     * Автоматична редакция в документа към зоната
     *
     * @return void
     * @throws core_exception_Expect
     */
    function act_editdetailindocument()
    {
        $this->requireRightFor('editdetailindocument');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('editdetailindocument', $rec);
        $zoneRec = rack_Zones::fetch($rec->zoneId);

        $Document = doc_Containers::getDocument($zoneRec->containerId);
        $Detail = cls::get($Document->detailToPlaceInZones);

        if(!empty($rec->batch)){
            $bQuery = batch_BatchesInDocuments::getQuery();
            $bQuery->where("#containerId = {$zoneRec->containerId} AND #detailClassId = {$Detail->getClassId()}");
            $bQuery->where(array("#productId = {$rec->productId} AND #packagingId = {$rec->packagingId} AND #batch = '[#1#]'", $rec->batch));

            if($bQuery->count() > 1){
                followRetUrl(null, 'Не може да промените документа, защото комбинацията от арткул+опаковка+партида е на повече от един ред в документа|*!', 'warning');
            }

            $singleBatchRec = $bQuery->fetch();
            $diff = $singleBatchRec->quantity - $rec->movementQuantity;
            $singleBatchRec->quantity = $rec->movementQuantity;
            batch_BatchesInDocuments::save($singleBatchRec, 'quantity');
            $dRec = $Detail->fetch($singleBatchRec->detailRecId);

            if ($Detail instanceof store_InternalDocumentDetail) {
                $dRec->packQuantity -= $diff / $singleBatchRec->quantityInPack;
            } else {
                $dRec->quantity -= $diff;
            }
        } else {
            $dQuery = $Detail->getQuery();
            $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$Detail->productFld}");
            $dQuery->where("#{$Detail->masterKey} = {$Document->that} AND #canStore = 'yes'");
            $Detail->invoke('AfterGetZoneSummaryQuery', array($rec, &$dQuery));
            $dQuery->where("#{$Detail->packagingFld} = {$rec->packagingId} AND #{$Detail->productFld} = {$rec->productId}");

            if($dQuery->count() > 1) {
                followRetUrl(null, 'Не може да промените документа, защото комбинацията от арткул+опаковка (без партида) е на повече от един ред в документа|*!', 'warning');
            }

            $dRec = $dQuery->fetch();
            $bQuery = batch_BatchesInDocuments::getQuery();
            $bQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$dRec->id}");
            $bQuery->XPR('sum', 'double', 'SUM(#quantity)');
            $sum = $bQuery->fetch()->sum;
            $finalQuantity = $sum + $rec->movementQuantity;
            if ($Detail instanceof store_InternalDocumentDetail) {
                $dRec->packQuantity = $finalQuantity / $dRec->quantityInPack;
            } else {
                $dRec->quantity = $finalQuantity;
            }
        }

        $dRec->quantity = round($dRec->quantity, 5);
        $Detail->save($dRec);
        if ($Detail instanceof core_Detail) {
            $Detail->Master->logWrite('Потвърдено к-во от палетния склад', $Document->that);
        } else {
            $Detail->logWrite('Потвърдено к-во от палетния склад', $Document->that);
        }

        // Изтриване на чакащите и запазените движения за този ред
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("LOCATE('|{$zoneRec->id}|', #zoneList)");
        $mQuery->where("#productId = {$rec->productId} AND #packagingId = {$rec->packagingId} AND #batch = '{$rec->batch}' AND #state IN ('pending', 'waiting')");
        $mQuery->show('id');
        $deleteIds = arr::extractValuesFromArray($mQuery->fetchAll(), 'id');
        if(countR($deleteIds)){
            core_Statuses::newStatus('L:' . countR($deleteIds), 'warning');
            $deleteIdStr = implode(',', $deleteIds);
            rack_Movements::delete("#id IN ({$deleteIdStr})");
        }

        followRetUrl(null, "Успешно е променено количеството в|* #{$Document->getHandle()} ");
    }
	
	
	/**
	 * Връща общото "генерирано" количество в БАЗОВА мярка
	 * за даден детайлен ред (зона + документ + продукт + партида).
	 *
	 * ВКЛЮЧВА всички състояния: pending, waiting, active, closed.
	 * - pending / waiting: броят се винаги (висят в тази зона за текущия документ)
	 * - active / closed: броят се само ако са вързани към документа на зоната
	 *   чрез полето `documents`.
	 *
	 * Така:
	 *  - не зависим от филтъра в терминала (Свободни/Моите/…)
	 *  - не броим затворени движения за стари документи в същата зона.
	 *
	 * @param stdClass $zoneRec  Запис от rack_Zones (мастър)
	 * @param stdClass $detRec   Запис от rack_ZoneDetails (детайл)
	 * @return float             Количество в базова мярка
	 */
	protected static function getGeneratedBaseForDetail($zoneRec, $detRec)
	{
		$zoneId      = (int) $zoneRec->id;
		$containerId = isset($zoneRec->containerId) ? (int) $zoneRec->containerId : 0;

		$res = 0.0;

		$q = rack_Movements::getQuery();

		// Продукт
		$q->where("#productId = {$detRec->productId}");

		// Партида: празна към празна, непразна към непразна
		if ($detRec->batch !== null && $detRec->batch !== '') {
			$q->where(array("#batch = '[#1#]'", $detRec->batch));
		} else {
			$q->where("#batch IS NULL OR #batch = ''");
		}

		// Ограничаваме по зони (и без това после ще проверяваме табличното поле)
		$q->where("LOCATE('|{$zoneId}|', #zoneList)");

		// Трябват ни зоните, коефициентът към базова мярка, състоянието и документите
		$q->show('zones,quantityInPack,state,documents');

		while ($m = $q->fetch()) {

			$state = (string) $m->state;
			$allow = false;

			if (in_array($state, array('pending', 'waiting'), true)) {
				// Всички pending / waiting в тази зона са "генерирани" за текущия документ
				$allow = true;
			} else {
				// active / closed – броим ги само ако са вързани към документа на зоната
				if ($containerId) {
					$docsArr = !empty($m->documents) ? keylist::toArray($m->documents) : array();
					if (isset($docsArr[$containerId])) {
						$allow = true;
					}
				} else {
					// Зона без документ – няма с какво да сравняваме, но не е критично
					$allow = true;
				}
			}

			if (!$allow) continue;

			// Табличното поле със зоните
			$zones = type_Table::toArray($m->zones);
			if (!is_array($zones) || !count($zones)) continue;

			foreach ($zones as $z) {
				if ((int)$z->zone === $zoneId) {
					// z->quantity = брой опаковки, quantityInPack => към базова мярка
					$res += (float)$z->quantity * (float)$m->quantityInPack;
				}
			}
		}

		return $res;
	}


    /**
     * Преди подготовка на записите
     */
    public function on_BeforePrepareListRows($mvc, &$res, $data)
    {
        if(!countR($data->recs)) return;

        // Еднократно кеширане на записите на засегнатите артикули
        $productIds = arr::extractValuesFromArray($data->recs, 'productId');
        $pQuery = cat_Products::getQuery();
        $pQuery->in('id', $productIds);
        $pQuery->show('name,code,isPublic,nameEn,state,canStore,measureId');
        $allProducts = $pQuery->fetchAll();
        foreach ($data->recs as $rec){
            $rec->_productRec = $allProducts[$rec->productId];
        }

        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->in('productId', $productIds);
        while($pRec = $packQuery->fetch()) {
            $mvc->cachePacks["{$pRec->productId}|{$pRec->packagingId}"] = $pRec;
        }
    }


    /**
     * Преди подготовка на записите
     */
    public function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if(!countR($data->rows)) return;
        foreach ($data->rows as $id => $row){
            $rec = $data->recs[$id];

            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack, $mvc->cachePacks);
        }
    }
}