<?php


/**
 * Клас 'bgerp_ProtoParam' - Клас за наследяване от класове за параметри на обекти
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
    public $listFields = 'id,typeExt,order,driverClass=Тип,state,roles';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'typeExt';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'bgerp/tpl/SingleLayoutParams.shtml';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'roles';
    
    
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Добавя задължителни полета към модела
     *
     * @param bgerp_ProtoParam $mvc
     *
     * @return void
     */
    public static function setFields(&$mvc)
    {
        $mvc->FLD('name', 'varchar(64,ci)', 'caption=Име, mandatory');
        $mvc->FLD('suffix', 'varchar(16,ci)', 'caption=Суфикс');
        $mvc->FLD('sysId', 'varchar(32)', 'input=none');
        $mvc->FNC('typeExt', 'varchar', 'caption=Име');
        $mvc->FLD('isFeature', 'enum(no=Не,yes=Да)', 'caption=Счетоводен признак за групиране->Използване,notNull,value=no,maxRadio=2,value=no,hint=Използване като признак за групиране в счетоводните справки?');
        $mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        $mvc->FLD('group', 'varchar(64,ci,nullIfEmpty)', 'caption=Група,after=suffix,placeholder=В която да се показва параметъра в списъците');
        $mvc->FLD('order', 'int', 'caption=Позиция,after=group');
        $mvc->FLD('roles', 'keylist(mvc=core_Roles,select=role,allowEmpty,groupBy=type)', 'caption=Роли,after=group');
        
        $mvc->setDbUnique('name, suffix, group');
        $mvc->setDbUnique('sysId');
    }
    
    
    /**
     * Помощна ф-я
     */
    private static function calcTypeExt($rec)
    {
        $typeExt = tr($rec->name);
        $typeExt = str_replace(array('&lt;', '&amp;'), array('<', '&'), $typeExt);
        
        if (!empty($rec->group)) {
            $group = tr($rec->group);
            $typeExt = "{$group} » {$typeExt}";
        }
        
        if (!empty($rec->suffix)) {
            $typeExt .= ' (' . str_replace(array('&lt;', '&amp;'), array('<', '&'), tr($rec->suffix)) . ')';
        }
        
        return $typeExt;
    }
    
    
    /**
     * Изчисляване на typeExt
     */
    protected static function on_CalcTypeExt($mvc, $rec)
    {
        $rec->typeExt = self::calcTypeExt($rec);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setField('driverClass', 'caption=Тип');
        
        if (isset($data->form->rec->sysId)) {
            $data->form->setReadOnly('name');
            $data->form->setReadOnly('suffix');
        }
        
        $query = $mvc->getQuery();
        $query->where("#group != '' AND #group IS NOT NULL");
        $params = arr::extractValuesFromArray($query->fetchAll(), 'group');
        if (countR($params)) {
            $params = arr::make($params, true);
        }
        
        $data->form->setSuggestions('group', array('' => '') + $params);
    }
    
    
    /**
     * Връща ид-то на параметъра по зададен sysId
     *
     * @param string $sysId
     *
     * @return int $id - ид на параметъра
     */
    public static function fetchIdBySysId($sysId)
    {
        return static::fetchField(array("#sysId = '[#1#]'", $sysId), 'id');
    }
    
    
    /**
     * След подготовка на масива за избор на опции
     */
    protected static function on_AfterMakeArray4Select($mvc, &$options, $fields = null, &$where = '', $index = 'id')
    {
        $newOptions = $options;

        // Ако има опции
        if (is_array($options)) {
            $newOptions = array();
            foreach ($options as $id => $value) {
                
                // Ако има роли за параметъра и потребителя ги няма, не може да избира параметъра
                $roles = self::$cache[$id]->roles;
                if (!empty($roles)) {
                    if (!haveRole($roles)) {
                        continue;
                    }
                }
                
                $group = self::$cache[$id]->group;
                
                // Ако имат група, и няма такава група в масива, те се групират
                if (!empty($group)) {
                    if (!array_key_exists($group, $newOptions)) {
                        $group = $mvc->getFieldType('group')->toVerbal(tr($group));
                        $newOptions[$group] = (object) array('title' => $group, 'group' => true);
                    }
                }
                
                // Махане на групата от името
                $exploded = explode(' » ', $value);
                $value = (countR($exploded) == 2) ? $exploded[1] : $value;
                
                $newOptions[$id] = $value;
            }
        }

        $options = $newOptions;
    }
    
    
    /**
     * Подготвя опциите за селектиране на параметър като към името се добавя неговия suffix
     */
    public function makeArray4Select_($fields = null, $where = '', $index = 'id', $tpl = null)
    {
        $query = static::getQuery();
        $query->XPR('orderCalc', 'int', "COALESCE(#order, 99999999)");
        if (strlen($where)) {
            $query->where($where);
        }
        $query->orderBy('group,orderCalc', 'ASC');
        $query->show('name,suffix,group,roles,group,orderCalc');
        
        $options = array();
        
        while ($rec = $query->fetch()) {
            self::$cache[$rec->id] = $rec;
            $options[$rec->{$index}] = self::calcTypeExt($rec);
        }

        return $options;
    }
    
    
    /**
     * Връща типа на параметъра
     *
     * @param mixed $id          - ид или запис на параметър
     * @param mixed $domainClass - клас на домейна на параметъра
     * @param int   $domainId    - ид на домейна на параметъра
     * @param mixed $value       - стойност
     *
     * @return FALSE|core_Type - инстанцираният тип или FALSE ако не може да се определи
     */
    public static function getTypeInstance($id, $domainClass, $domainId, $value = null)
    {
        $rec = static::fetchRec($id);
        if ($Driver = static::getDriver($rec)) {
            
            return $Driver->getType($rec, $domainClass, $domainId, $value);
        }
        
        return false;
    }


    /**
     * Връща дефолтната стойност на параметъра
     *
     * @param mixed $id          - ид или запис на параметър
     * @param mixed $domainClass - клас на домейна на параметъра
     * @param int   $domainId    - ид на домейна на параметъра
     * @param mixed $value       - стойност
     *
     * @return null|mixed
     */
    public static function getDefaultValue($id, $domainClass, $domainId, $value = null)
    {
        $rec = static::fetchRec($id);
        if ($Driver = static::getDriver($rec)) {

            return $Driver->getDefaultValue($rec, $domainClass, $domainId, $value);
        }

        return null;
    }


    /**
     * Изпълнява се преди импортирването на данните
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        core_Classes::add($rec->driverClass);
        $rec->driverClass = cls::get($rec->driverClass)->getClassId();
        
        // Импортиране на параметри при нужда
        if (isset($rec->csv_params)) {
            $params = arr::make($rec->csv_params);
            foreach ($params as $k => $v) {
                if (!isset($rec->{$k})) {
                    $rec->{$k} = $v;
                }
            }
        }
        
        // Импортиране и на ролите
        if (!empty($rec->csv_roles)) {
            $rolesArr = arr::make($rec->csv_roles);
            if (countR($rolesArr)) {
                foreach ($rolesArr as $role) {
                    if (!core_Roles::fetchByName($role)) {
                        core_Roles::addOnce($role);
                    }
                }
                
                $rec->roles = core_Roles::getRolesAsKeylist($rec->csv_roles);
            }
        }

        if (!empty($rec->csv_options)) {
            $rec->options = cond_type_abstract_Proto::options2text($rec->csv_options);
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search,driverClass';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->view = 'horizontal';
        $data->query->orderBy('createdOn=DESC,state=ASC');
        if($filterRec = $data->listFilter->rec){
            if(isset($filterRec->driverClass)){
                $data->query->where("#driverClass = {$filterRec->driverClass}");
            }
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
        if (!empty($rec->suffix)) {
            $row->suffix = $mvc->getFieldType('suffix')->toVerbal(tr($rec->suffix));
        }
    }
    
    
    /**
     * Подготвя запис за форсиране
     *
     * @param string      $sysId   - систем ид на параметър
     * @param string      $name    - име на параметъра
     * @param string      $type    - тип на параметъра
     * @param NULL|string   $options - опции на параметъра само за типовете enum и set
     * @param NULL|string $suffix  - наставка
     * @param NULL|string $groupName  - група
     *
     * @return stdClass $nRec     - ид на параметъра
     */
    protected static function makeNewRec($sysId, $name, $type, $options = array(), $suffix = null, $groupName = null)
    {
        // Проверка дали типа е допустим
        // Подготовка на записа на параметъра
        $typeName = $type;
        if(strpos($type, 'cond_type_') === false){
            expect(in_array(strtolower($type), array('double', 'text', 'varchar', 'time', 'date', 'component', 'percent', 'int', 'delivery', 'paymentmethod', 'image', 'enum', 'set', 'file')), $type);
            $typeName = "cond_type_{$type}";
        }
        
        expect($Type = cls::get($typeName));
        $nRec = new stdClass();
        $nRec->name = $name;
        $nRec->driverClass = $Type->getClassId();
        $nRec->sysId = $sysId;
        if (!empty($suffix)) {
            $nRec->suffix = $suffix;
        }

        if (!empty($groupName)) {
            $nRec->group = $groupName;
        }

        // Само за типовете enum и set, се искат опции
        if (($Type instanceof cond_type_Enum) || ($Type instanceof cond_type_Set)) {
            $nRec->options = cond_type_abstract_Proto::options2text($options);
        }
        
        return $nRec;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec->id)) {
            if ($rec->sysId || $rec->lastUsedOn) {
                $requiredRoles = 'no_one';
            }
        }
        
        if (($action == 'changestate') && isset($rec->id)) {
            if (isset($rec->sysId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Параметри функция за вербализиране
     *
     * @param int   $id          - ид на параметър
     * @param mixed $domainClass - клас на домейна на параметъра
     * @param int   $domainId    - ид на домейна на параметъра
     * @param mixed $value       - стойност за вебализиране
     *
     * @return mixed - вербализирана стойност или FALSE ако не може
     */
    public static function toVerbal($id, $domainClass, $domainId, $value)
    {
        $rec = static::fetchRec($id);
        if ($Driver = static::getDriver($rec)){
            $value = trim($value);

            $res = $Driver->toVerbal($rec, $domainClass, $domainId, $value);
            
            return $res;
        }
        
        return false;
    }
    
    
    /**
     * Ограничаване на символите на стойноста, ако е текст
     *
     * @param mixed $driverClass
     * @param mixed $value
     *
     * @return mixed $value
     */
    public static function limitValue($driverClass, $value)
    {
        $driverClass = cls::get($driverClass);
        if (($driverClass instanceof cond_type_Text) && mb_strlen($value) > 90) {
            $bHtml = mb_strcut($value, 0, 90);
            
            $title = tr('Вижте още');
            $id = md5($value);
            $value = "{$bHtml} . <br><a href=\"javascript:toggleDisplay('{$id}')\"  class= 'more-btn linkWithIcon nojs' style=\"font-weight:bold; background-image:url(" . sbf('img/16/toggle1.png', "'") . ");\"
                   >{$title}</a><div class='clearfix21 richtextHide' id='{$id}'>{$value}</div>";
            $value = cls::get('type_Html')->toVerbal($value);
        }
        
        return $value;
    }
}
