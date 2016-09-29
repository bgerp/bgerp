<?php



/**
 * Клас 'bgerp_ProtoParam' - Клас за наследяване от класове за параметри на обекти
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class bgerp_ProtoParam extends embed_Manager
{
	
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cond_ParamTypeIntf';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'name, suffix,  sysId';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'typeExt,driverClass=Тип,state';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'typeExt';
	
	
	/**
	 * Нов темплейт за показване
	 */
	public $singleLayoutFile = 'bgerp/tpl/SingleLayoutParams.shtml';
	
	
	/**
	 * Добавя задължителни полета към модела
	 * 
	 * @param bgerp_ProtoParam $mvc
	 * @return void
	 */
	public static function setFields(&$mvc)
	{
		$mvc->FLD('name', 'varchar(64,ci)', 'caption=Име, mandatory');
		$mvc->FLD('suffix', 'varchar(16,ci)', 'caption=Суфикс');
		$mvc->FLD('sysId', 'varchar(32)', 'input=none');
		$mvc->FNC('typeExt', 'varchar', 'caption=Име');
		$mvc->FLD('default', 'varchar(64)', 'caption=Конкретизиране->Дефолт');
		$mvc->FLD('isFeature', 'enum(no=Не,yes=Да)', 'caption=Счетоводен признак за групиране->Използване,notNull,value=no,maxRadio=2,value=no,hint=Използване като признак за групиране в счетоводните справки?');
		$mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
		$mvc->FLD('group', 'varchar(64,ci)', 'caption=Група,after=suffix,placeholder=В която да се показва параметъра в списъците');
		
		$mvc->setDbUnique('name, suffix, group');
		$mvc->setDbUnique("sysId");
	}
	
	
	/**
	 * Изчисляване на typeExt
	 */
	protected static function on_CalcTypeExt($mvc, $rec)
	{
		$rec->typeExt = tr($rec->name);
		 
		if (!empty($rec->group)) {
			$group = tr($rec->group);
			$rec->typeExt = "{$group} » {$rec->typeExt}";
		}
		 
		if (!empty($rec->suffix)) {
			$rec->typeExt .= ' (' . tr($rec->suffix) . ')';
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setField('driverClass', 'caption=Тип');
		 
		if(isset($data->form->rec->sysId)){
			$data->form->setReadOnly('name');
			$data->form->setReadOnly('suffix');
		}
		
		$query = $mvc->getQuery();
		$query->where("#group != '' AND #group IS NOT NULL");
		$params = array_map(create_function('$o', 'return $o->group;'), $query->fetchAll());
		if(count($params)){
			$params = arr::make($params, TRUE);
		}
		
		$data->form->setSuggestions('group', array('' => '') + $params);
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		 
		if($form->isSubmitted()){
			if(!empty($rec->group)){
				$rec->group = str::mbUcfirst(mb_strtolower($rec->group));
			}
		}
	}
	
	
	/**
	 * Връща ид-то на параметъра по зададен sysId
	 * 
	 * @param string $sysId
	 * @return int $id - ид на параметъра
	 */
	public static function fetchIdBySysId($sysId)
	{
		return static::fetchField(array("#sysId = '[#1#]'", $sysId), 'id');
	}
	
	
	/**
	 * След подготовка на масива за избор на опции
	 */
	protected static function on_AfterMakeArray4Select($mvc, &$options, $fields = NULL, &$where = "", $index = 'id'  )
	{
		$newOptions = $options;
		
		// Ако има опции
		if(is_array($options)){
			$newOptions = array();
			foreach ($options as $id => $value){
				$group = $mvc->fetchField($id, 'group');
				
				// Ако имат група, и няма такава група в масива, те се групират
				if(!empty($group)){
					if(!array_key_exists($group, $newOptions)){
						$group = $mvc->getFieldType('group')->toVerbal(tr($group));
						$newOptions[$group] = (object)array('title' => $group, 'group' => TRUE);
					}
				}
				
				// Махане на гръпата от името
				$exploded = explode(" » ", $value);
				$value = (count($exploded) == 2) ? $exploded[1] : $value;
					
				$newOptions[$id] = $value;
			}
		}
		
		$options = $newOptions;
	}
	
	
	/**
	 * Подготвя опциите за селектиране на параметър като към името се добавя неговия suffix
	 */
	public function makeArray4Select_($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
	{
		$query = static::getQuery();
		if(strlen($where)){
			$query->where($where);
		}
		$query->orderBy('group', 'ASC');
		
		$options = array();
		while($rec = $query->fetch()){
			$options[$rec->{$index}] = $rec->typeExt;
		}
		
		return $options;
	}
	
	
	/**
	 * Връща типа на параметъра
	 *
	 * @param mixed $id - ид или запис на параметър
	 * @return FALSE|cond_type_Proto - инстанцирания тип или FALSE ако не може да се определи
	 */
	public static function getTypeInstance($id)
	{
		$rec = static::fetchRec($id);
		if($Driver = static::getDriver($rec)){
			return $Type = $Driver->getType($rec);
		}
		 
		return FALSE;
	}
	
	
	/**
	 * Изпълнява се преди импортирването на данните
	 */
	protected static function on_BeforeImportRec($mvc, &$rec)
	{
		core_Classes::add($rec->driverClass);
		$rec->driverClass = cls::get($rec->driverClass)->getClassId();
		
		// Импортиране на параметри при нужда
		if(isset($rec->csv_params)){
			$params = arr::make($rec->csv_params);
			foreach ($params as $k => $v){
				if(!isset($rec->{$k})){
					$rec->{$k} = $v;
				}
			}
		}
	}
	
	
	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->listFilter->showFields = 'search';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->view = 'horizontal';
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
		if(!empty($rec->suffix)){
			$row->suffix = $mvc->getFieldType('suffix')->toVerbal(tr($rec->suffix));
		}
	}
	
	
	/**
	 * Форсира параметър
	 *
	 * @param string $sysId       - систем ид на параметър
	 * @param string $name        - име на параметъра
	 * @param string $type        - тип на параметъра
	 * @param NULL|text $options  - опции на параметъра само за типовете enum и set
	 * @param NULL|string $suffix - наставка
	 * @return number             - ид на параметъра
	 */
	public static function force($sysId, $name, $type, $options = array(), $suffix = NULL)
	{
		// Ако има параметър с това систем ид,връща се
		$id = self::fetchIdBySysId($sysId);
		if(!empty($id)) return $id;
		 
		// Проверка дали типа е допустим
		expect(in_array(strtolower($type), array('double', 'text', 'varchar', 'time', 'date', 'component', 'percent', 'int', 'delivery', 'paymentmethod', 'image', 'enum', 'set', 'file')));
		 
		// Подготовка на записа на параметъра
		expect($Type = cls::get("cond_type_{$type}"));
		$nRec = new stdClass();
		$nRec->name = $name;
		$nRec->driverClass = $Type->getClassId();
		$nRec->sysId = $sysId;
		if(!empty($suffix)){
			$nRec->suffix = $suffix;
		}
		 
		// Само за типовете enum и set, се искат опции
		if($type == 'enum' || $type == 'set'){
			$nRec->options = cond_type_Proto::options2text($options);
		}
		 
		// Създаване на параметъра
		return self::save($nRec);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 */
	protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec->id)) {
			if($rec->sysId || $rec->lastUsedOn) {
				$requiredRoles = 'no_one';
			}
		}
		
		if(($action == 'changestate') && isset($rec->id)) {
			if(isset($rec->sysId)){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * Параметри функция за вербализиране
	 *
	 * @param int $id      - ид на параметър
	 * @param mixed $value - стойност за вебализиране
	 * @return mixed       - вербализирана стойност или FALSE ако не може
	 */
	public static function toVerbal($id, $value)
	{
		$Type = self::getTypeInstance($id);
		if($Type) return $Type->toVerbal(trim($value));
		 
		return FALSE;
	}
}