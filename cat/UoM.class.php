<?php



/**
 * Клас 'cat_UoM' - мерни единици
 *
 * Unit of Measures
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_UoM extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_State2, plg_AlignDecimals, plg_Sorting, plg_Translate';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'cat,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'cat,ceo';

    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'cat,ceo';

    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'cat,ceo';
  

    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'cat,ceo';
 

    /**
     * Заглавие
     */
    var $title = 'Мерни единици';
    
    
    /**
     * Полета за лист изгледа
     */
    var $listFields = "id,name,shortName=Съкращение->Българско,sysId=Съкращение->Международно,state,round=Точност,showContents";
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(36)', 'caption=Мярка, export,translate,mandatory');
        $this->FLD('shortName', 'varchar(12)', 'caption=Съкращение, export,translate,mandatory');
        $this->FLD('type', 'enum(uom=Мярка,packaging=Опаковка)', 'notNull,value=uom,caption=Тип,silent,input=hidden');
        $this->FLD('baseUnitId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Базова мярка, export');
        $this->FLD('baseUnitRatio', 'double', 'caption=Коефициент, export');
        $this->FLD('sysId', 'varchar', 'caption=System Id,input=hidden');
        $this->FLD('sinonims', 'varchar(255)', 'caption=Синоними');
        $this->FLD('showContents', 'enum(yes=Показване,no=Скриване)', 'caption=Показване в документи->К-во в опаковка');
        $this->FLD('round', 'int', 'caption=Точност след десетичната запетая->Цифри');
        
        $this->setDbUnique('name');
        $this->setDbUnique('shortName');
    }
    
    
    /**
     * Връща опции с опаковките
     */
    public static function getPackagingOptions()
    {
    	$options = cls::get(get_called_class())->makeArray4Select('name', "#type = 'packaging' AND state NOT IN ('closed')");
    	
    	return $options;
    }
    
    
    /**
     * Връща опции с мерките
     */
    public static function getUomOptions()
    {
    	$options = cls::get(get_called_class())->makeArray4Select('name', "#type = 'uom' AND state NOT IN ('closed')");
    	 
    	return $options;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$type = core_Request::get('type', 'enum(uom,packaging)');
    	if($type == 'packaging'){
    		$mvc->currentTab = 'Мерки->Опаковки';
    		$mvc->title = 'Опаковки';
    		$data->listFields['name'] = 'Опаковка';
    	} else {
    		$mvc->currentTab = 'Мерки->Мерки';
    	}
    	
    	$data->query->where(array("#type = '[#1#]'", $type));
    }
    
    
    /**
     * Ф-я закръгляща количество спрямо основната мярка на даден артикул, Ако е пдоадена опаковка
     * спрямо нея
     * 
     * @param double $quantity - к-то което ще закръгляме
     * @param int $productId - ид на артикула
     * @return double - закръгленото количество
     */
    public static function round($quantity, $productId)
    {
    	// Коя е основната мярка на артикула
    	$uomId = cat_Products::getProductInfo($productId)->productRec->measureId;
    	
    	// Имали зададено закръгляне
    	$round = static::fetchField($uomId, 'round');
    	
    	// Ако няма
    	if(!isset($round)){
    		$uomRec = static::fetch($uomId);
    		
    		// Имали основна мярка върху която да стъпим
    		if($uomRec->baseUnitId){
    			
    			/*
    			 * Ако има базова мярка, тогава да е спрямо точността на базовата мярка. bp($round);
    			 * Например ако базовата мярка е килограм и имаме нова мярка - грам, която 
    			 * е 1/1000 от базовата, то точността по подразбиране е 3/3 = 1, където числителя 
    			 * е точността на мярката килограм, а в знаменателя - log(1000).
    			 */
    			$baseRound = static::fetchField($uomRec->baseUnitId, 'round');
    			$round = $baseRound / log10(pow($uomRec->baseUnitRatio, -1));
    			$round = abs($round);
    		} else {
    			
    			// Ако няма базова мярка и няма зададено закръгляне значи е 0
    			$round = 0;
    		}
    	}
    	
    	$res = round($quantity, $round);
    	
    	return $res;
    }
    
    
    /**
     * Конвертира стойност от една мярка към основната и
     * @param double amount - стойност
     * @param int $unitId - ид на мярката
     */
    public static function convertToBaseUnit($amount, $unitId)
    {
        $rec = static::fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount * $ratio;
        
        return $result;
    }
    
    
    /**
     * Конвертира стойност от основната мярка на дадена мярка
     * @param double amount - стойност
     * @param int $unitId - ид на мярката
     */
    public static function convertFromBaseUnit($amount, $unitId)
    {
        $rec = static::fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount / $ratio;
        
        return $result;
    }
    
    
    /**
     * Функция връщащи масив от всички мерки които са сродни
     * на посочената мярка (примерно за грам това са : килограм, тон и др)
     * @param int $measureId - id на мярка
     * @return array $options - всички мярки от същата категория
     * като подадената
     */
    public static function getSameTypeMeasures($measureId, $short = FALSE)
    {
    	expect($rec = static::fetch($measureId), "Няма такава мярка");	
    	
    	$query = static::getQuery();
    	($rec->baseUnitId) ? $baseId = $rec->baseUnitId : $baseId = $rec->id;
    	$query->where("#baseUnitId = {$baseId}");
    	$query->orWhere("#id = {$baseId}");
    	
    	$options = array("" => "");
    	while($op = $query->fetch()){
    		$cap = ($short) ? $op->shortName : $op->name;
    		$options[$op->id] = $cap;	
    	}
    	
    	return $options;
    }
    
    
    /**
     * Функция която конвертира стойност от една мярка в друга
     * сродна мярка
     * @param double $value - Стойноста за конвертиране
     * @param int $from - Id на мярката от която ще обръщаме
     * @param int $to - Id на мярката към която конвертираме
     * @return double - Конвертираната стойност
     */
    public static function convertValue($value, $from, $to)
    {
    	expect($fromRec = static::fetch($from), 'Проблем при изчислението на първата валута');
    	expect($toRec = static::fetch($to), 'Проблем при изчислението на втората валута');
    	
    	($fromRec->baseUnitId) ? $baseFromId = $fromRec->baseUnitId : $baseFromId = $fromRec->id;
    	($toRec->baseUnitId) ? $baseToId = $toRec->baseUnitId : $baseToId = $toRec->id;
    	
    	// Очакваме двете мерки да имат една обща основна мярка
    	expect($baseFromId == $baseToId, "Не може да се конвертира от едната мярка в другата");
    	$rate = $fromRec->baseUnitRatio / $toRec->baseUnitRatio;
    	
    	// Форматираме резултата да се показва правилно числото
    	$rate = number_format($rate, 9, '.', '');
    	
    	return $value * $rate;
    }
    
    
    /**
     * Връща краткото име на мярката
     * 
     * @param int $id - ид на мярка
     * @return string - краткото име на мярката
     */
    public static function getShortName($id)
    {
    	if(!$id) return '???';
    	
    	expect($rec = static::fetch($id));
    	
    	return static::recToVerbal($rec, 'shortName')->shortName;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	// Ако се импортира от csv файл, заместваме основната
    	// единица с ид-то и от системата
    	if(isset($rec->csv_baseUnitId) && strlen($rec->csv_baseUnitId) != 0){
    		$rec->baseUnitId = static::fetchField("#name = '{$rec->csv_baseUnitId}'", 'id');
    	}
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/UoM.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "shortName", 
	    	2 => "csv_baseUnitId", 
	    	3 => "baseUnitRatio",
	    	4 => "state",
	    	5 => "sysId",
	    	6 => "sinonims",
    		7 => "round",
    		8 => "type");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща мерна еденициа по систем ид
     * @param varchar $sysId - sistem Id
     * @return stdClass $rec - записа отговарящ на сис ид-то
     */
    public static function fetchBySysId($sysId)
    {
    	return static::fetch("#sysId = '{$sysId}'");
    }
    
    
    /**
     * Връща запис отговарящ на име на мерна еденица
     * (включва българско, английско или фонетично записване)
     * @param string $string - дума по която се търси
     * @return stdClass $rec - записа отговарящ на сис Ид-то
     */
    public static function fetchBySinonim($unit)
    {
    	$unitLat = strtolower(str::utf2ascii($unit));
    	
    	$query = static::getQuery();
    	$query->likeKeylist('sinonims', "|{$unitLat}|");
    	$query->orWhere(array("LOWER(#sysId) = LOWER('[#1#]')", $unitLat));
        $query->orWhere(array("LOWER(#name) = LOWER('[#1#]')", $unit));
        $query->orWhere(array("LOWER(#shortName) = LOWER('[#1#]')", $unit));

    	return $query->fetch();
    }
    
    
    /**
     * Помощна ф-я правеща умно закръгляне на сума в най-оптималната близка
     * мерна еденица от същия тип
     * @param double $val - сума за закръгляне
     * @param string $sysId - системно ид на мярка
     * @param boolean $verbal - дали да са вербални числата
     * @param boolean $asObject - да се върне обект със стойност, мярка или като стринг
     * @return string - закръглената сума с краткото име на мярката
     */
    public static function smartConvert($val, $sysId, $verbal = TRUE, $asObject = FALSE)
    {
    	$Double = cls::get('type_Double');
    	$Double->params['smartRound'] = 'smartRound';
    	
    	// Намира се коя мярка отговаря на това сис ид
    	$typeUom = cat_UoM::fetchBySysId($sysId);
    	
    	// Извличат се мерките от същия тип и се премахва празния елемент в масива
        $sameMeasures = cat_UoM::getSameTypeMeasures($typeUom->id);
        unset($sameMeasures[""]);
       
        if(count($sameMeasures) == 1){
        	
        	// Ако мярката няма сродни мерки, сумата се конвертира в нея и се връща
        	$val = cat_UoM::convertFromBaseUnit($val, $typeUom->id);
        	$val = ($verbal) ? $Double->toVerbal($val) : $val;
        	
        	return ($asObject) ? (object)(array('value' => $val, 'measure' => $typeUom->id)) : $val . " " . $typeUom->shortName;
        }
        
        // При повече от една мярка, изчисляваме, колко е конвертираната сума на всяка една
        $all = array();
        foreach ($sameMeasures as $mId => $name){
        	$all[$mId] = cat_UoM::convertFromBaseUnit($val, $mId);
        }
       
        // Сумите се пдореждат в възходящ ред
        asort($all);
        
        // Първата сума по голяма от 1 се връща
        foreach ($all as $mId => $amount){
        	
	        if($amount >= 1){
	        	$all[$mId] = ($verbal) ? $Double->toVerbal($all[$mId]) : $all[$mId];
	        	
	        	return ($asObject) ? (object)(array('value' => $all[$mId], 'measure' => $mId)) : $all[$mId] . " " . static::getShortName($mId); 
        	}
        }
        
        // Ако няма такава се връща последната (тази най-близо до 1)
        end($all);
        $uomId = key($all);
        
        $all[$mId] = ($verbal) ? $Double->toVerbal($all[$mId]) : $all[$mId];
        
        return ($asObject) ? (object)(array('value' => $all[$uomId], 'measure' => $mId)) : $all[$uomId] . " " . static::getShortName($mId);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$type = Request::get('type', 'enum(uom,packaging)');
    	$title = ($type == 'uom') ? 'мярка' : 'опаковка';
    	
    	$data->toolbar->removeBtn('btnAdd');
    	$data->toolbar->addBtn('Нов запис', array($mvc, 'add', 'type' => $type), "ef_icon=img/16/star_2.png,title=Добавяне на нова {$title}");
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$rec = $data->form->rec;
    	
    	if($rec->type == 'packaging'){
    		$data->form->setField('name', 'caption=Опаковка');
    	}
    	
    	// Ако записа е създаден от системния потребител, може да се 
    	if($rec->createdBy == core_Users::SYSTEM_USER){
    		foreach (array('name', 'shortName', 'baseUnitId', 'baseUnitRatio', 'sysId', 'sinonims', 'round') as $fld){
    			$data->form->setField($fld, 'input=none');
    		}
    	}
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	// Рет урл-то не сочи към мастъра само ако е натиснато 'Запис и Нов'
    	if (isset($data->form) && ($data->form->cmd === 'save' || is_null($data->form->cmd))) {
    
    		// Променяма да сочи към single-a
    		$data->retUrl = toUrl(array('cat_UoM', 'list', 'type' => $data->form->rec->type));
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'edit' && $rec->state == 'closed'){
    		$requiredRoles = 'no_one';
    	}
    }
}
