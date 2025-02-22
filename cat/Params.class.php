<?php


/**
 * Мениджър за продуктови параметри
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продуктови параметри
 */
class cat_Params extends bgerp_ProtoParam
{
    /**
     * Заглавие
     */
    public $title = 'Продуктови параметри';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Продуктов параметър';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cat_Wrapper, plg_Search, plg_State2,plg_SaveAndNew, plg_Sorting, plg_Rejected';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'cat,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';


    /**
     * Кой може да оттегля?
     */
    public $canReject = 'cat,ceo';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'group, name, suffix,  sysId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,typeExt,order,driverClass=Вид,state,roles,valueType=Стойност,showInPublicDocuments=Показване в документи->Външни,showInTasks=Показване в документи->Пр. операции,createdOn,createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'cat_ParamFormulaVersions';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';


    /**
     * Работен кеш
     */
    protected static $cacheNormalizedNames = array();


    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setFields($this);
        $this->FLD('valueType', 'enum(optional=Опционална,mandatory=Задължителна,readonly=Само за четене)', 'caption=Задаване стойности на планиращ параметър при създаване на Операция->Избор,notNull,value=optional');
        $this->FLD('showInPublicDocuments', 'enum(no=Не,yes=Да)', 'caption=Показване на параметъра->Външни документи,notNull,value=yes,maxRadio=2');
        $this->FLD('showInTasks', 'enum(no=Не,yes=Да)', 'caption=Показване на параметъра->Пр. операции,notNull,value=no,maxRadio=2');
        $this->FLD('editInLabel', 'enum(yes=Да,no=Не)', 'caption=Показване на параметъра->Редакция в етикет,notNull,value=yes,maxRadio=2');
        $this->FLD('state', 'enum(active=Активен,closed=Затворен,rejected=Оттеглен)', 'caption=Видимост,input=none,notSorting,notNull,value=active,smartCenter');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('showInPublicDocuments', 'yes');
        if (isset($data->form->rec->sysId)) {
            $data->form->setReadOnly('showInTasks');
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'cat/csv/Params.csv';
        $fields = array(
            0 => 'name',
            1 => 'driverClass',
            2 => 'suffix',
            3 => 'sysId',
            4 => 'csv_options',
            5 => 'showInPublicDocuments',
            6 => 'state',
            7 => 'csv_params',
            8 => 'showInTasks',
            9 => 'group',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща нормализирано име на параметъра
     *
     * @param mixed  $rec       - ид или запис на параметър
     * @param bool   $upperCase - всички букви да са в долен или в горен регистър
     * @param string $lg        - език на който да е преведен
     *
     * @return string $name      - нормализирано име
     */
    public static function getNormalizedName($rec, $upperCase = false, $lg = 'bg')
    {
        $id = is_numeric($rec) ? $rec : $rec->id;
        $key = "{$id}_{$lg}";
        if(!array_key_exists($key, static::$cacheNormalizedNames)){
            $rec = cat_Params::fetchRec($rec, 'name,suffix,group');

            core_Lg::push($lg);
            $name = tr($rec->name) . ((!empty($rec->suffix)) ? ' (' . tr($rec->suffix) . ')': '');
            if(!empty($rec->group)) {
                $group = tr($rec->group);
                $name = "{$group} {$name}";
            }
            $name = preg_replace('/\s+/', '_', $name);
            $name = str_replace('/', '_', $name);
            $name = str_replace('.', '_', $name);
            $name = ($upperCase) ? mb_strtoupper($name) : mb_strtolower($name);
            core_Lg::pop();
            static::$cacheNormalizedNames[$key] = $name;
        }

        return static::$cacheNormalizedNames[$key];
    }
    
    
    /**
     * Разбира масив с параметри на масив с ключвове, преведените
     * имена на параметрите
     *
     * @param array $params    - масив с параметри
     * @param bool  $upperCase - дали имената да са в долен или горен регистър
     * @return array $arr      - масив
     */
    public static function getParamNameArr($params, $upperCase = false)
    {
        $arr = array();
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                expect($rec = cat_Params::fetch($key, 'name,suffix'));
                
                // Името на параметъра се превежда на местния език
                $key1 = self::getNormalizedName($rec, $upperCase);
                $arr[$key1] = $value;
                
                // Името на параметъра се превежда на глобалния език
                $key2 = self::getNormalizedName($rec, $upperCase, 'en');
                $arr[$key2] = $value;
            }
        }
        
        return $arr;
    }
    
    
    /**
     * Рендира блок с параметри за артикули
     *
     * @param array $paramArr
     * @return core_ET $tpl
     */
    public static function renderParamBlock($paramArr, $paramCaption = null)
    {
        $tpl = getTplFromFile('cat/tpl/products/Params.shtml');
        $lastGroupId = null;
        $paramCaption = $paramCaption ?? tr('Параметри');

        $tpl->replace($paramCaption, 'PARAM_CAPTION');
        if (is_array($paramArr)) {
            foreach ($paramArr as &$row2) {
                $block = clone $tpl->getBlock('PARAM_GROUP_ROW');
                if ($row2->group != $lastGroupId) {
                    $block->replace($row2->group, 'group');
                }
                $lastGroupId = $row2->group;
                unset($row2->group);
                $block->placeObject($row2);
                $block->removeBlocks();
                $block->removePlaces();
                $tpl->append($block, 'ROWS');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Форсира параметър (ако има такъв го връща, ако няма създава)
     *
     * @param string      $sysId       - систем ид на параметър
     * @param string      $name        - име на параметъра
     * @param string      $type        - тип на параметъра
     * @param NULL|string   $options     - опции на параметъра само за типовете enum и set
     * @param NULL|string $suffix      - наставка
     * @param NULL|bool   $showInTasks - може ли да се показва в производствена операция
     * @param NULL|bool   $groupName   - група
     *@param NULL|bool   $params   - параметри
     *
     * @return int - ид на параметъра
     */
    public static function force($sysId, $name, $type, $options = array(), $suffix = null, $showInTasks = false, $showInPublicDocuments = true, $groupName = null, $params = null)
    {
        // Ако има параметър с това систем ид,връща се
        if($sysId){
            $id = self::fetchIdBySysId($sysId);
            if (!empty($id)) return $id;
        } else {

            // Ако няма сис ид все пак се проверява дали няма такъв параметър
            $where = "#name = '{$name}' AND #suffix = '{$suffix}' AND ";
            $where .= ($groupName) ? "#group = '{$groupName}'" : "#group IS NULL";
            if($exId = static::fetchField($where)){

                 return $exId;
            }
        }

        $nRec = static::makeNewRec($sysId, $name, $type, $options, $suffix, $groupName);
        $nRec->showInTasks = ($showInTasks) ? 'yes' : 'no';
        $nRec->showInPublicDocuments = ($showInPublicDocuments) ? 'yes' : 'no';
        if (isset($params)) {
            $params = arr::make($params);
            foreach ($params as $k => $v) {
                if (!isset($rec->{$k})) {
                    $nRec->{$k} = $v;
                }
            }
        }

        // Създаване на параметъра
        core_Users::forceSystemUser();
        $id = self::save($nRec);
        core_Users::cancelSystemUser();

        return $id;
    }
    
    
    /**
     * Връща параметрите, които се показват само в задачите
     *
     * @return array $res
     */
    public static function getTaskParamIds()
    {
        $query = self::getQuery();
        $query->where("#showInTasks = 'yes' AND #state != 'closed'");
        $res = arr::extractValuesFromArray($query->fetchAll(), 'id');
        
        return $res;
    }
    
    
    /**
     * Кои са публичните параметри
     *
     * @return array $res
     */
    public static function getPublic()
    {
        $res = array();
        $query = self::getQuery();
        $query->where("#showInPublicDocuments = 'yes' AND #state = 'active'");
        $query->show('id,typeExt');
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec->typeExt;
        }
        
        return $res;
    }


    /**
     * Наличните опции за избор на параметри за производствените операции
     *
     * @param mixed $exParamIds - ид-та на съществуващите записи
     * @return array $options
     */
    public static function getTaskParamOptions($exParamIds = null)
    {
        $options = array();
        $taskParamIds = cat_Params::getTaskParamIds();
        $exParamIds = is_array($exParamIds) ? $exParamIds : keylist::toArray($exParamIds);
        $allowedParamIds = $taskParamIds + $exParamIds;
        foreach ($allowedParamIds as $paramId){
            $options[$paramId] = cat_Params::getVerbal($paramId, 'typeExt');
        }

        return $options;
    }


    /**
     * Връща масив от подадените параметри обърнати в удобен вид за писане във формули
     *
     * @param array $params      - подадените параметри
     * @param array $idToNameArr - масив с мапване на параметрите като ид-та към тези с имена
     * @return array $res        - обърнати във вид удобен за използване във формули
     */
    public static function getFormulaParamMap($params, &$idToNameArr = array())
    {
        $strings = $ids = array();
        $params = keylist::isKeylist($params) ? keylist::toArray($params) : $params;

        if (is_array($params)) {
            foreach ($params as $paramId => $value) {
                // Ако ключа е не е ид, но е лайв параметър - взима се такъв
                if(!is_numeric($paramId)){
                    if(strpos($paramId, '$') !== false){
                        $strings[$paramId] = $value;
                    }
                    continue;
                }

                if(cat_Params::haveDriver($paramId, 'cond_type_YesOrNo')){
                    $value = ($value == 'yes') ? 1 : 0;
                }
                if (!is_numeric($value)) continue;
                $normalizedName = cat_Params::getNormalizedName($paramId);

                $key = '$' . $normalizedName;
                $strings[$key] = $value;

                $key1 = "#{$paramId}#";
                $ids[$key1] = $value;
                $idToNameArr[$key1] = $key;
            }
        }

        $res = $strings + $ids;

        return $res;
    }


    /**
     * Масив с параметри върнати от `getFormulaParamMap($params)` обърнати в
     * съджешчъни за формула
     *
     * @param array $map
     * @return array $context
     */
    public static function formulaMapToSuggestions($map)
    {
        $context = array();
        $scopeKeys = array_keys($map);
        foreach ($scopeKeys as $v){
            $k = $v;
            if(strpos($k, "#") === 0){
                $paramId = str_replace('#', '', $k);
                $paramName = cat_Params::getNormalizedName($paramId);
                $v = "{$v} ({$paramName})";
            }
            $context[$k] = (object) array('val' => $k, 'search' => $v, 'template' => $v);
        }

        return $context;
    }


    /**
     * След подготовка на сингъл тулбара
     *
     * @param $mvc
     * @param $data
     * @return void
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (log_Data::haveRightFor('list')) {
            $historyCnt = log_Data::getObjectCnt($mvc, $data->rec->id);
            if ($historyCnt) {
                $data->toolbar->addBtn("История|* ({$historyCnt})", array('log_Data', 'list', 'class' => $mvc->className, 'object' => $data->rec->id, 'ret_url' => true), 'id=btn-history', 'ef_icon = img/16/book_open.png, title=Разглеждане на историята на файла, row=2');
            }
        }
    }
}
