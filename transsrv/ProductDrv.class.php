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
	protected $defaultMetaData = 'canSell,canBuy,canConvert';
	
	
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
        $form->FLD('fromCountry', 'key(mvc=drdata_Countries,select=commonNameBg,allowEmpty)', 'caption=Локация за натоварване->Държава');
        $form->FLD('fromPCode', 'varchar(16)', 'caption=Локация за натоварване->П. код');
        $form->FLD('fromPlace', 'varchar(32)', 'caption=Локация за натоварване->Нас. място');

        // Локация за разтоварване
        $form->FLD('toCountry', 'key(mvc=drdata_Countries,select=commonNameBg,allowEmpty)', 'caption=Локация за разтоварване->Държава');
        $form->FLD('toPCode', 'varchar(16)', 'caption=Локация за разтоварване->П. код');
        $form->FLD('toPlace', 'varchar(32)', 'caption=Локация за разтоварване->Нас. място');

        // Описание на товара
        $form->FLD('transUnit', 'varchar', 'caption=Информация за товара->Трансп. ед.,suggestions=Европалета|Палета|Кашона|Скари|Сандъка|Чувала|Каси|Биг Бага|20\' контейнера|40\' контейнера|20\' контейнера upgraded|40\' High cube контейнера|20\' reefer хладилни|40\' reefer хладилни|Reefer 40\' High Cube хлд|Open Top 20\'|Open Top 40\'|Flat Rack 20\'|Flat Rack 40\'|FlatRack Collapsible 20\'|FlatRack Collapsible 40\'|Platform 20\'|Platform 40\'|Хенгер|Прицеп|Мега трейлър|Гондола');
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
        // Срокове
        $form->FLD('loadingTime', 'datetime(format=smartTime,defaultTime=09:00:00)', 'caption=Срокове->За товарене');
        $form->FLD('deliveryTime', 'datetime(format=smartTime,defaultTime=17:00:00)', 'caption=Срокове->За доставка');
        
        // Обща информация
        $form->FLD('conditions', 'richtext(bucket=Notes,rows=3)', 'caption=Обща информация->Условия');
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
}