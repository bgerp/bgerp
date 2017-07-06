<?php


/**
 * Драйвър за продукт на ЕП
 *
 *
 * @category  extrapack
 * @package   epbags
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Транспортна услуга
 */
class transsrv_ProductDrv extends cat_ProductDriver
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	//public $canSelectDriver = 'no_one';
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'cat_ProductDriverIntf';
	
	
	/**
	 * Дефолт мета данни за всички продукти
	 */
	protected $defaultMetaData = 'canSell,canBuy';
	
	
	/**
	 * Стандартна мярка за ЕП продуктите
	 */
	public $uom = 'pcs';
	
	
	/**
	 * Допълва дадената форма с параметрите на фигурата
	 * Връща масив от имената на параметрите
	 */
	function addFields(core_Fieldset &$form)
	{
        // Локация за натоварване
        $form->FLD('fromCountry', 'key(mvc=drdata_Countries,select=commonNameBg,allowEmpty)', 'caption=Натоварване->Държава');
        $form->FLD('fromPCode', 'varchar(16)', 'caption=Натоварване->П. код');
        $form->FLD('fromPlace', 'varchar(32)', 'caption=Натоварване->Нас. място');
        $form->FLD('fromAddress', 'varchar', 'caption=Натоварване->Адрес');
        $form->FLD('fromCompany', 'varchar', 'caption=Натоварване->Фирма');
        $form->FLD('fromPerson', 'varchar', 'caption=Натоварване->Лице');
        $form->FLD('loadingTime', 'datetime(defaultTime=09:00:00)', 'caption=Натоварване->Най-късно на');

        // Локация за разтоварване
        $form->FLD('toCountry', 'key(mvc=drdata_Countries,select=commonNameBg,allowEmpty)', 'caption=Разтоварване->Държава');
        $form->FLD('toPCode', 'varchar(16)', 'caption=Разтоварване->П. код');
        $form->FLD('toPlace', 'varchar(32)', 'caption=Разтоварване->Нас. място');
        $form->FLD('toAddress', 'varchar', 'caption=Разтоварване->Адрес');
        $form->FLD('toCompany', 'varchar', 'caption=Разтоварване->Фирма');
        $form->FLD('toPerson', 'varchar', 'caption=Разтоварване->Лице');
        $form->FLD('deliveryTime', 'datetime(defaultTime=17:00:00)', 'caption=Разтоварване->Краен срок');

        // Описание на товара
        $form->FLD('transUnit', 'varchar', 'caption=Информация за товара->Трансп. ед.,suggestions=Европалета|Палета|Кашона|Скари|Сандъка|Чувала|Каси|Биг Бага|20\' контейнер|40\' контейнер|20\' контейнер upgraded|40\' High cube контейнер|20\' reefer хладилен|40\' reefer хладилен|Reefer 40\' High Cube хлд|Open Top 20\'|Open Top 40\'|Flat Rack 20\'|Flat Rack 40\'|FlatRack Collapsible 20\'|FlatRack Collapsible 40\'|Platform 20\'|Platform 40\'|Хенгер|Прицеп|Мега трейлър|Гондола');
        $form->FLD('unitQty', 'int(Min=0)', 'caption=Информация за товара->Количество');
        $form->FLD('maxWeight', 'cat_type_Uom(unit=t,min=1,max=5000000)', 'caption=Информация за товара->Общо тегло');
        $form->FLD('maxVolume', 'cat_type_Uom(unit=cub.m,min=0.1,max=5000)', 'caption=Информация за товара->Общ обем');
        $form->FLD('maxHeight', 'cat_type_Uom(unit=m,min=0.1,max=10)', 'caption=Информация за товара->Макс. височина');
        $form->FLD('dangerous', 'enum( no = Безопасен товар,
                                       hl = Извънгабаритен товар,
                                        1 = Kлac 1 - Взривни вещества и изделия,
                                        2 = Клас 2 - Газове,
                                        3 = Клас 3 - Запалими течности,
                                        4 = Клас 4 - Други запалими вещества,
                                        5 = Клас 5 - Окисляващи вещества и органични пероксиди,
                                        6 = Клас 6 - Отровни и заразни вещества,
                                        7 = Клас 7 - Радиоактивни материали,
                                        8 = Kлac 8 - Корозионни вещества,
                                        9 = Клас 9 - Други опасни вещества)', 'caption=Информация за товара->Опасност');
        $form->FLD('load', 'varchar', 'caption=Информация за товара->Описание');

         
        // Обща информация
        $form->FLD('conditions', 'richtext(bucket=Notes,rows=3)', 'caption=Обща информация->Условия');
        $form->FLD('ourReff', 'varchar', 'caption=Обща информация->Наш реф.№');
        $form->FLD('auction', 'varchar', 'caption=Обща информация->Търг');
        $form->FLD('auctionId', 'varchar', 'caption=Обща информация->Търг,input=hidden');
	}
	

    /**
     * Връща дефолтното име на артикула
     * 
     * @param stdClass $rec
	 * @return NULL|string
     */
    public function getProductTitle($rec)
    {
 

        $myCompany = crm_Companies::fetchOurCompany();
    	
        if(!$rec->fromCountry) {
            $rec->fromCountry = $myCompany->country;
        }

    	if(!$rec->toCountry) {
            $rec->toCountry = $myCompany->country;
        }

        $from2let = drdata_Countries::fetch($rec->fromCountry)->letterCode2;
        $to2let = drdata_Countries::fetch($rec->toCountry)->letterCode2;
        
        $title = $from2let . '»' . $to2let;
 
        if($rec->unitQty && $rec->transUnit) {
            $title .= ', ' . $rec->unitQty . ' ' . type_Varchar::escape($rec->transUnit);
        } elseif($rec->transUnit) {
            $title .= ', ' . type_Varchar::escape($rec->transUnit);
        }

        return $title . ' / ' . tr('Транспорт') . '';
    }



    /**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterInputEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$form)
	{

        $form->rec->name = $Driver->getProductTitle($form->rec);

        if($form->isSubmitted()) {
            $fields = $form->selectFields("#input != 'none'");
     
            foreach($fields as $name => $fld) {
                if($form->rec->{$name} === '' && cls::getClassName($fld->type) == 'type_Varchar') {
                
                    $form->rec->{$name} = NULL;
                }
            }
        }
    }



	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$data)
	{
        if($data->form->getField('meta', FALSE)){
			$data->form->setField('meta', 'input=hidden');
		}
		
        if($data->form->getField('measureId', FALSE)){
			$data->form->setField('measureId', 'input=hidden');
		}

        if($data->form->getField('info', FALSE)){
			$data->form->setField('info', 'input=hidden');
		}

        if($data->form->getField('packQuantity', FALSE)){
			$data->form->setField('packQuantity', 'input=hidden');
		}

        if($data->form->getField('name', FALSE)){
			$data->form->setField('name', 'input=hidden');
		}
        if($data->form->getField('notes', FALSE)){
			$data->form->setField('notes', 'input=hidden');
		}

	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal(cat_ProductDriver $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
	{
	}
	
	
 	
	
	/**
	 * Връща стойността на параметъра с това име, или
	 * всички параметри с техните стойностти
	 *
	 * @param int $classId    - ид на клас
	 * @param string $id      - ид на записа
	 * @param string $name    - име на параметъра, или NULL ако искаме всички
	 * @param boolean $verbal - дали да са вербални стойностите
	 * @return mixed  $params - стойност или FALSE ако няма
	 */
	public function getParams($classId, $id, $name = NULL, $verbal = FALSE)
	{
		$rec = cls::get($classId)->fetchField($id, 'driverRec');
	
		$params = array();
		$toleranceId = cat_Params::force('tolerance', 'Толеранс', 'cond_type_Percent', NULL, '%');
		$params[$toleranceId] = 0;
		
		if(!is_numeric($name)) {
			$nameId = cat_Params::fetch(array("#sysId = '[#1#]'", $name))->id;
		} else {
			$nameId = $name;
		}
			
		if($nameId && isset($params[$nameId])) return $params[$nameId];
		
		if($name && isset($rec->_params[$name])) return $rec->_params[$name];
		
		if(isset($name) && !isset($params[$nameId])) return NULL;
		
		return $params;
	}
	
	
 	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareProductDescription(&$data)
	{
	}
	
	
	/**
	 * Допълнителните условия за дадения продукт,
	 * които автоматично се добавят към условията на договора
	 *
	 * @param mixed $rec       - ид или запис на артикул
	 * @param double $quantity - к-во
	 * @return array           - Допълнителните условия за дадения продукт
	 */
	public function getConditions($rec, $quantity)
	{
		if($condition = transsrv_Setup::get('SALE_DEFAULT_CONDITION')){
			return array($condition);
		}
		
		return array();
	}
	
	
	/**
	 * Връща хеша на артикула (стойност която показва дали е уникален)
	 *
	 * @param embed_Manager $Embedder - Ембедър
	 * @param mixed $rec              - Ид или запис на артикул
	 * @return NULL|varchar           - Допълнителните условия за дадения продукт
	 */
	public function getHash(embed_Manager $Embedder, $rec)
	{
		$objectToHash = new stdClass();
		$fields = $Embedder->getDriverFields($this);
		foreach ($fields as $name => $caption){
			$objectToHash->{$name} = $rec->{$name};
		}
		
		$hash = md5(serialize($objectToHash));
	
		return $hash;
	}
	
	
	/**
	 * Връща задължителната основна мярка
	 *
	 * @return int|NULL - ид на мярката, или NULL ако може да е всяка
	 */
	public function getDefaultUomId()
	{
		return cat_UoM::fetchBySinonim($this->uom)->id;
	}
	
	
	/**
	 * Рендиране на описанието на драйвера
	 *
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public function renderProductDescription($data)
	{
		// Шаблон
		$tpl = getTplFromFile('transsrv/tpl/TransportProduct.shtml');
		
		// ще се заместват само полетата от драйвера
		$fields = cat_Products::getDriverFields($this);
		$row = new stdClass();
		foreach ($fields as $name => $caption){
			$row->{$name} = $data->row->{$name};
		}
		
		if(!Mode::isReadOnly()){
			$systemId = remote_Authorizations::getSystemId(transsrv_Setup::get('BID_DOMAIN'));
			if(haveRole('officer')){
				if(!empty($data->rec->auction) && haveRole('officer')){
					if($systemId){
						$url = array("transbid_Auctions/single/{$data->rec->auctionId}");
						$url = remote_Authorizations::getRemoteUrl($systemId, $url);
						$row->auction = ht::createLink($row->auction, $url);
					} else {
						$row->auction = ht::createHint($row->auction, 'За да видите търга, ви е нужно оторизация за trans.bid', 'warning');
					}
				}
			}
		}
		
		$tpl->placeObject($row);
		
		return $tpl;
	}
}