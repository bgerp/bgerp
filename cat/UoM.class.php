<?php


/**
 * Клас 'cat_UoM' - мерни единици и опаковки
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_UoM extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cat_Wrapper, plg_State2, plg_AlignDecimals, plg_Sorting, plg_Translate, core_UserTranslatePlg';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'packEdit, ceo, sales, purchase';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'cat, ceo';
    
    
    /**
     * Кой може сменя състоянието
     */
    public $canChangestate = 'cat, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'cat, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';
    
    
    /**
     * Заглавие
     */
    public $title = 'Мерни единици';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'мерна единица';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'sysId';
    
    
    /**
     * Полета за лист изгледа
     */
    public $listFields = 'id,name,shortName=Съкращение,baseUnitId,sysId=System Id,round,roundSignificant,showContents,defQuantity,state,createdOn,createdBy';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Шаблон за заглавието
     */
    public $recTitleTpl = '[#shortName#]';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(36)', 'caption=Мярка, export, translate=user|tr|transliterate, mandatory');
        $this->FLD('shortName', 'varchar(12)', 'caption=Съкращение, export, translate=user|tr|transliterate, mandatory');
        $this->FLD('type', 'enum(uom=Мярка,packaging=Опаковка)', 'notNull,value=uom,caption=Тип,silent,input=hidden');
        $this->FLD('baseUnitId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Базова мярка, export,removeAndRefreshForm=baseUnitRatio,silent');
        $this->FLD('baseUnitRatio', 'double(Min=0)', 'caption=Коефициент, export, input=hidden');
        $this->FLD('sysId', 'varchar', 'caption=System Id,input=hidden');
        $this->FLD('isBasic', 'enum(no=Друга,yes=Първична)', 'caption=Тип,notNull,value=no');
        $this->FLD('sinonims', 'varchar(255)', 'caption=Синоними');
        $this->FLD('showContents', 'enum(yes=Показване,no=Скриване)', 'caption=Показване в документи->К-во в опаковка,smartCenter');
        $this->FLD('defQuantity', 'double(smartRound)', 'caption=Показване в документи->Дефолтно к-во');
        $this->FLD('round', 'int', 'caption=Точност->Дробни цифри (брой)');
        $this->FLD('roundSignificant', 'int', 'caption=Точност->Значещи цифри (минимум)');
        
        $this->setDbUnique('name');
        $this->setDbUnique('shortName');
        $this->setDbIndex('baseUnitId');
        $this->setDbIndex('sysId');
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
        if (empty($rec->showContents)) {
            $row->showContents = $mvc->getFieldType('showContents')->toVerbal('no');
        }
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
        
        if ($type == 'packaging') {
            $mvc->currentTab = 'Мерки->Опаковки';
            $mvc->title = 'Опаковки';
            $data->listFields['name'] = 'Опаковка';
            arr::placeInAssocArray($data->listFields, array('isBasic' => 'Тип'), 'sysId');
        } else {
            $mvc->currentTab = 'Мерки->Мерки';
        }
        
        $data->query->where(array("#type = '[#1#]'", $type));
    }
    
    
    /**
     * ф-я закръгляща к-то в подадената опаковка, ако к-то е много-малко или много
     * голямо и има по точна мярка се закръгля към нея
     *
     * @param int   $uomId
     * @param float $quantity
     *
     * @return float $res
     */
    public static function round(&$uomId, $quantity)
    {
        // Коя е основната мярка на артикула
        expect($rec = self::fetch($uomId));
        $round = $rec->round;
        
        // Ако няма
        if (!isset($round)) {
            $uomRec = static::fetch($uomId);
            
            // Имали основна мярка върху която да стъпим
            if ($uomRec->baseUnitId) {
                
                /*
    			 * Ако има базова мярка, тогава да е спрямо точността на базовата мярка.
    			 * Например ако базовата мярка е килограм и имаме нова мярка - грам, която
    			 * е 1/1000 от базовата, то точността по подразбиране е 3/3 = 1, където числителя
    			 * е точността на мярката килограм, а в знаменателя - log(1000).
    			 */
                $baseRound = static::fetchField($uomRec->baseUnitId, 'round');
                
                $bRatio = log10(pow($uomRec->baseUnitRatio, -1));
                
                if (!is_infinite($bRatio) && $bRatio) {
                    $round = $baseRound / $bRatio;
                    $round = abs($round);
                }
                
                if (!isset($round)) {
                    $round = 0;
                }
            } else {
                
                // Ако няма базова мярка и няма зададено закръгляне значи е 0
                $round = 0;
            }
        }
        
        
        
        if ($quantity < 1 && ($downMeasureId = cat_UoM::getMeasureByRatio($uomId, 0.001))) {
            $quantity *= 1000;
            $uomId = $downMeasureId;
        } elseif ($quantity > 1000 && ($downMeasureId = cat_UoM::getMeasureByRatio($uomId, 1000))) {
            $quantity /= 1000;
            $uomId = $downMeasureId;
        }
        
        $rec = self::fetch($uomId);
        $round = $rec->round;
        $res = round($quantity, $round);
        
        return $res;
    }
    
    
    /**
     * Конвертира стойност от една мярка към основната и
     *
     * @param float amount - стойност
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
     *
     * @param float amount - стойност
     * @param int $unitId - ид на мярката
     */
    public static function convertFromBaseUnit($amount, $unitId)
    {
        $rec = static::fetch($unitId, 'baseUnitId,baseUnitRatio');
        
        if (is_null($rec->baseUnitId)) {
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
     *
     * @param int  $measureId
     * @param bool $short
     *
     * @return array $options
     */
    public static function getSameTypeMeasures($measureId, $short = false)
    {
        expect($rec = static::fetch($measureId, 'baseUnitId,id'), 'Няма такава мярка');
        
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $baseId = ($rec->baseUnitId) ? $rec->baseUnitId : $rec->id;
        $query->where("#baseUnitId = {$baseId} OR #id = {$baseId}");
        $query->show('shortName,name');
        
        $options = array();
        while ($op = $query->fetch()) {
            $cap = ($short) ? $op->shortName : $op->name;
            $options[$op->id] = $cap;
        }
        
        if (countR($options)) {
            $options = array('' => '') + $options;
        }
        
        return $options;
    }
    
    
    /**
     * Връща, (ако има) мярка, която е в отношение ratio спрямо текущата
     */
    public static function getMeasureByRatio($measureId, $ratio = 0.001)
    {
        static $res = array();
        $key = $measureId. '|' . $ratio;
        if (!isset($res[$key])) {
            $res[$key] = false;
            $mArr = array_keys(self::getSameTypeMeasures($measureId));
            foreach ($mArr as $id) {
                if ($id == $measureId || empty($id)) {
                    continue;
                }
                if (self::convertValue(1, $id, $measureId) . '' == $ratio . '') {
                    $res[$key] = $id;
                    break;
                }
            }
        }
        
        return $res[$key];
    }
    
    
    /**
     * Функция която конвертира стойност от една мярка в друга сродна мярка
     *
     * @param float $value - Стойноста за конвертиране
     * @param int   $from  - Id на мярката от която ще обръщаме
     * @param int   $to    - Id на мярката към която конвертираме
     *
     * @return FALSE|float - Конвертираната стойност или FALSE ако мерките са от различен тип
     */
    public static function convertValue($value, $from, $to)
    {
        if (is_string($from) && !is_numeric($from)) {
            $fromRec = self::fetchBySinonim($from);
        } else {
            $fromRec = static::fetch($from);
        }
        
        if (is_string($to) && !is_numeric($to)) {
            $toRec = self::fetchBySinonim($to);
        } else {
            $toRec = static::fetch($to);
        }
        
        expect($fromRec, "Неразпозната мярка: {$from}", $fromRec);
        expect($toRec, "Неразпозната мярка: {$to}", $toRec);

        // Ако двете мерки са една и съща
        if($fromRec->id == $toRec->id){

            return $value;
        }

        ($fromRec->baseUnitId) ? $baseFromId = $fromRec->baseUnitId : $baseFromId = $fromRec->id;
        ($toRec->baseUnitId) ? $baseToId = $toRec->baseUnitId : $baseToId = $toRec->id;
        
        if ($baseFromId != $baseToId) {
            
            return false;
        }
        
        $rate = $fromRec->baseUnitRatio / $toRec->baseUnitRatio;
        
        // Форматираме резултата да се показва правилно числото
        $rate = number_format($rate, 9, '.', '');
        
        return $value * $rate;
    }
    
    
    /**
     * Връща краткото име на мярката
     *
     * @param int $id - ид на мярка
     *
     * @return string - краткото име на мярката
     */
    public static function getShortName($id)
    {
        static $uoms = array();
        
        if (!$id) {
            
            return '???';
        }
        
        $cLg = core_Lg::getCurrent();
        
        if (empty($uoms[$cLg][$id])) {
            $uoms[$cLg][$id] = static::getVerbal($id, 'shortName');
        } 
        
        return $uoms[$cLg][$id];
    }
    
    
    /**
     * Връща умното наименование на мярката спрямо количеството. Ако е мярка се връща
     * краткото наименование, ако е опаковка се връща името на опаковката съгласувано с количеството
     * 
     * @param mixed $id
     * @param int|null $count
     * @param int|null $minLen
     * @return string
     */
    public static function getSmartName($id, $count = null, $minLen = null)
    {
        $rec = static::fetchRec($id);
        if($rec->type == 'uom'){
            
            $name = static::getShortName($rec->id);
            if(empty($minLen) || mb_strlen($name) >= $minLen){

                return $name;
            }
        }
        
        $name = static::getVerbal($rec, 'name');
        
        return (isset($count)) ? str::getPlural($count, $name, true) : $name;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        // Ако се импортира от csv файл, заместваме основната единица с ид-то и от системата
        if (isset($rec->csv_baseUnitId) && strlen($rec->csv_baseUnitId) != 0) {
            $rec->baseUnitId = static::fetchField("#name = '{$rec->csv_baseUnitId}'", 'id');
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'cat/csv/UoM.csv';
        $fields = array(
            0 => 'name',
            1 => 'shortName',
            2 => 'csv_baseUnitId',
            3 => 'baseUnitRatio',
            4 => 'state',
            5 => 'sysId',
            6 => 'sinonims',
            7 => 'round',
            8 => 'type',
            9 => 'defQuantity',
            10 => 'roundSignificant'
        );
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща мерна единица по систем ид
     *
     * @param string $sysId - sistem Id
     *
     * @return stdClass $rec - записа отговарящ на сис ид-то
     */
    public static function fetchBySysId($sysId)
    {
        if (!array_key_exists($sysId, self::$cache)) {
            self::$cache[$sysId] = static::fetch("#sysId = '{$sysId}'");
        }
        
        $rec = self::$cache[$sysId];
        
        return $rec;
    }
    
    
    /**
     * Връща запис отговарящ на име на мерна единица
     * (включва българско, английско или фонетично записване)
     *
     * @param string $unit - дума по която се търси
     *
     * @return stdClass $rec - записа отговарящ на сис Ид-то
     */
    public static function fetchBySinonim($unit)
    {
        $unit = trim(mb_strtolower($unit));
        $unitAscii = str::utf2ascii($unit);
        
        $arr = array();
        $arr[] = "LOWER(#sysId) = LOWER('[#1#]')";
        $arr[] = "LOWER(#name) = LOWER('[#1#]')";
        $arr[] = "LOWER(#shortName) = LOWER('[#1#]')";
        $arr[] = "LOWER(CONCAT('|', #name, '|', #shortName)) LIKE '%|[#1#]|%'";
        $arr[] = "LOWER(CONCAT('|', #sysId, #sinonims)) LIKE '%|[#2#]|%'";
        $rec = self::fetch(array(implode(' OR ', $arr), $unit, $unitAscii));
        
        return $rec;
    }
    
    
    /**
     * Помощна ф-я правеща умно закръгляне на сума в най-оптималната близка
     * мерна единица от същия тип
     *
     * @param float  $val      - сума за закръгляне
     * @param string $sysId    - системно ид на мярка
     * @param bool   $verbal   - дали да са вербални числата
     * @param bool   $asObject - да се върне обект със стойност, мярка или като стринг
     *
     * @return string - закръглената сума с краткото име на мярката
     */
    public static function smartConvert($val, $sysId, $verbal = true, $asObject = false)
    {
        $Double = cls::get('type_Double');
        $Double->params['smartRound'] = 'smartRound';
        
        // Намира се коя мярка отговаря на това сис ид
        $typeUom = cat_UoM::fetchBySysId($sysId);
        
        // Ако стойността е 0 не се прави конверсия
        if($val == 0){
            
            return ($asObject) ? (object) (array('value' => 0, 'measure' => $typeUom->id)) : $val . ' ' . tr($typeUom->shortName);
        }
        
        // Извличат се мерките от същия тип и се премахва празния елемент в масива
        $sameMeasures = cat_UoM::getSameTypeMeasures($typeUom->id);
        unset($sameMeasures['']);
        
        if ($sysId == 'l') {
            $sameMeasures = array();
            $sameMeasures[$typeUom->id] = $typeUom->name;
        }
        
        if (countR($sameMeasures) == 1) {
            
            // Ако мярката няма сродни мерки, сумата се конвертира в нея и се връща
            $val = cat_UoM::convertFromBaseUnit($val, $typeUom->id);
            $val = ($verbal) ? $Double->toVerbal($val) : $val;
            
            return ($asObject) ? (object) (array('value' => $val, 'measure' => $typeUom->id)) : $val . ' ' . tr($typeUom->shortName);
        }
        
        // При повече от една мярка, изчисляваме, колко е конвертираната сума на всяка една
        $all = array();
        foreach ($sameMeasures as $mId => $name) {
            $all[$mId] = cat_UoM::convertFromBaseUnit($val, $mId);
        }
        
        // Сумите се пдореждат в възходящ ред
        asort($all);
        
        // Първата сума по голяма от 1 се връща
        foreach ($all as $mId => $amount) {
            if ($amount >= 1) {
                $all[$mId] = ($verbal) ? $Double->toVerbal($all[$mId]) : $all[$mId];
                
                return ($asObject) ? (object) (array('value' => $all[$mId], 'measure' => $mId)) : $all[$mId] . ' ' . static::getShortName($mId);
            }
        }
        
        // Ако няма такава се връща последната (тази най-близо до 1)
        end($all);
        $uomId = key($all);
        
        $all[$mId] = ($verbal) ? $Double->toVerbal($all[$mId]) : $all[$mId];
        
        return ($asObject) ? (object) (array('value' => $all[$uomId], 'measure' => $mId)) : $all[$uomId] . ' ' . static::getShortName($mId);
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
        
        if (!haveRole('debug')) {
            unset($data->listFields['sysId']);
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $data->title = ($rec->type == 'uom') ? 'Мерки' : 'Опаковки';
        
        if ($rec->type == 'packaging') {
            $mvc->currentTab = 'Мерки->Опаковки';
            $form->setField('name', 'caption=Опаковка');
        } else {
            $form->setField('isBasic', 'input=none');
        }
        
        $form->setDefault('showContents', 'no');
        
        // Ако записа е създаден от системния потребител, може да се
        if ($rec->createdBy == core_Users::SYSTEM_USER) {
            foreach (array('name', 'shortName', 'baseUnitId', 'baseUnitRatio', 'sysId', 'sinonims') as $fld) {
                $form->setField($fld, 'input=none');
            }
        }
        
        if(isset($form->rec->baseUnitId)){
            $form->setField('baseUnitRatio', 'input,mandatory');
        }
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
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
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'edit' && $rec->state == 'closed') {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    protected static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'default') {
            $type = Request::get('type', 'enum(uom,packaging)');
            
            // Ако не е посочен тип, избираме това да са мерките
            if (!isset($type)) {
                $curUrl = getCurrentUrl();
                $curUrl['type'] = 'uom';
                redirect($curUrl);
            }
        }
    }
    
    
    /**
     * Дали мярката е тегловна (грам, килограм, тон и т.н.)
     *
     * @param int $uomId
     *
     * @return bool
     */
    public static function isWeightMeasure($uomId)
    {
        $kgUoms = cat_UoM::getSameTypeMeasures(cat_UoM::fetchBySysId('kg')->id);
        
        return array_key_exists($uomId, $kgUoms);
    }
}
