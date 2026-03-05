<?php


/**
 * Регистър - описание на всички входове и изходи на контролерите
 * Съдържа и текущите им стойности, време за измерване/установяване и евентуално грешки
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_Indicators extends core_Detail
{
    /**
     * Масив в който се намират всички текущи стойности на индикаторите
     */
    public static $contex;
    
    
    /**
     * Масив с всички задаедени изходи
     */
    public static $outputs;


    /**
     * @var int - Брой записи на страница
     */
    public $listItemsPerPage = 100;


    /**
     * Необходими мениджъри
     */
    public $loadList = 'plg_RowTools2, sens2_Wrapper, plg_AlignDecimals, plg_RefreshRows, plg_Rejected, plg_State2,plg_Sorting';
    
    
    /**
     * Заглавие
     */
    public $title = 'Текущи стойности на индикаторите';
    
    
    /**
     * Заглавие ед. ч.
     */
    public $singleTitle = 'Индикатор';
    
    
    /**
     * На колко време ще се актуализира листа
     */
    public $refreshRowsTime = 25000;
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'debug';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'debug';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, sens';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin, sens';
    
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на Условията на доставка
     */
    public $canChangestate = 'sens,admin';
    
    public $masterKey = 'controllerId';
    
    
    public $listFields = 'title,value,controllerId,error,lastValue,state';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('controllerId', 'key(mvc=sens2_Controllers, select=name, allowEmpty, find=everywhere)', 'caption=Контролер, mandatory, silent,refreshForm');
        $this->FLD('port', 'varchar(64)', 'caption=Порт, mandatory');
        $this->FLD('name', 'varchar(64,nullIfEmpty)', 'caption=Наименование,column=none');
        $this->FLD('value', 'double(minDecimals=0, maxDecimals=4, smartRound)', 'caption=Стойност,smartCenter,input=none');
        $this->FLD('lastValue', 'datetime', 'caption=Към момент,oldFieldName=time,input=none');
        $this->FLD('lastUpdate', 'datetime', 'caption=Обновяване,column=none,input=none');
        $this->FLD('error', 'varchar(128)', 'caption=Грешки,input=none');
        $this->FLD('state', 'enum(active=Активен, rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
        $this->FLD('uom', 'varchar(16)', 'caption=Мярка,column=none');
        $this->FLD('format', "table(columns=cond|value|statusText|statusType,captions=Условие|Стойност|Име|Състояние,widths=4em|6em|10em|6em,cond_opt=EQ|GT|LT|GTE|LTE,statusType_opt=|on|off|ok|warning|danger)", 'caption=Форматиране,single=none,column=none');
        $this->FNC('title', 'varchar(64)', 'caption=Заглавие,column=none');

        $this->FNC('isOutput', 'enum(yes,no)', 'caption=Изход ли е?,column=none');
        
        $this->setDbUnique('controllerId,port,uom');
        $this->setDbUnique('name');
    }
    
    
    /**
     *
     * @param array $idArr
     *
     * @return string|core_ET
     */
    public static function renderIndicator($idArr)
    {
        $res = '';
        
        if (!$idArr) {
            
            return $res;
        }
        
        $rowsArr = array();
        
        foreach ($idArr as $id) {
            $rec = self::fetch($id);
            
            if (!$rec) {
                continue ;
            }
            
            $row = self::recToVerbal($rec);
            
            $row->valAndUom = $row->value . "<span class='measure'>" . $row->uom . '</span>';
            
            $rowsArr[] = $row;
        }
        
        $table = cls::get('core_TableView', array('mvc' => get_called_class()));
        $res = $table->get($rowsArr, array('title' => 'Индикатор', 'valAndUom' => 'Стойност', 'error' => 'Грешки', 'lastValue' => 'Към момент'));
        
        return $res;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if($form->rec->name !== null) {
                $form->rec->name = str_replace(' ', '_', $form->rec->name);
            }
            if (strlen($form->rec->name) && !preg_match('/^[\\p{L}0-9_\.]+$/u', $form->rec->name)) {
                $form->setError('name', 'Наименованието трябва да съдържа само букви, цифри, точка и символа `_`');
            }
        }
    }
    
    
    /**
     * Изпълнява се при подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        if ($rec->id) {
            $form->setField('controllerId', 'input');
            $form->setReadOnly('controllerId');
            $form->setReadOnly('port');
        }
        
        if ($rec->controllerId && !$rec->port) {
            $ap = sens2_Controllers::getActivePorts($rec->controllerId);
            
            foreach ($ap as $port => $pRec) {
                if (!self::fetch(array("#controllerId = {$rec->controllerId} AND #port = '[#1#]'", $port)) || $port == $rec->port) {
                    $opt[$port] = $pRec->caption;
                }
            }
            
            $form->setOptions('port', $opt);
        }
    }
    
    
    /**
     * Дали порта е изходящ
     */
    public static function on_CalcIsOutput($mvc, $rec, $escape = true)
    {
        static $outputs;
        
        if (!$outputs[$rec->controllerId]) {
            $drv = sens2_Controllers::getDriver($rec->controllerId, true);

            if ($drv !== false) {
                $outputs[$rec->controllerId] = $drv->getOutputPorts();
            }
        }
        
        if ($outputs[$rec->controllerId][$rec->port]) {
            $rec->isOutput = 'yes';
        } else {
            $rec->isOutput = 'no';
        }
    }
    
    
    /**
     * Изчислява стойността на функционалното поле 'title'
     */
    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = $mvc->getRecTitle($rec);
    }
    
    
    public static function getContex($scriptId)
    {
        if (!self::$contex[$scriptId]) {
            $query = self::getQuery();
            $query->where("#state != 'rejected'");
            self::$contex[$scriptId] = array();
            while ($iRec = $query->fetch()) {
                self::$contex[$scriptId]['$' . $iRec->title] = (double) $iRec->value;
                $controller = self::getVerbal($iRec, 'controllerId');
                self::$contex[$scriptId]['$' . $controller . '.' . $iRec->port] = (double) $iRec->value;
            }
        }
        
        return self::$contex[$scriptId];
    }
    
    
    /**
     * Записва текущи данни за вход или изход
     * Ако липсва запис за входа/изхода - създава го
     */
    public static function setValue($controllerId, $port, $value, $time)
    {
        $ap = sens2_Controllers::getActivePorts($controllerId);
        
        $uom = $ap[$port]->uom;
        
        $query = self::getQuery();
        
        while ($r = $query->fetch(array("#controllerId = {$controllerId} AND #port = '[#1#]'", $port))) {
            if ($r->uom != $uom) {
                if ($r->state != 'rejected') {
                    self::reject($r->id);
                }
            } else {
                $rec = $r;
            }
        }
        
        if (!$rec) {
            $rec = new stdClass();
        } else {
            // Ако имаме повторение на последните данни - не правим запис
            if (($rec->value == $value && $rec->lastValue == $time) || ($rec->lastValue > $time)) {
                
                return;
            }
        }
        
        $rec->controllerId = $controllerId;
        $rec->port = $port;
        $rec->value = $value;
        $rec->uom = $uom;
        $rec->lastValue = $time;
        $rec->lastUpdate = $time;
        $rec->state = 'active';
        
        // Ако имаме грешка, поставяме я в правилното място
        $value = trim($value);
        if ($value === '') {
            $value = 'Празна стойност';
        }
        if (!is_numeric($value)) {
            unset($rec->value, $rec->lastValue);
            $rec->error = $value;
        } else {
            $rec->error = '';
        }
        
        
        self::save($rec);
        
        if (!$rec->error) {
            // Записваме и в контекста, ако има такъв
            if (self::$contex) {
                $title = self::getRecTitle($rec);
                self::$contex['$' . $title] = $value;
            }
            
            return $rec->id;
        }
    }
    
    
    /**
     * Връща заглавието на дадения запис (името на порта)
     */
    public static function getRecTitle($rec, $escape = true)
    {
        if ($rec->name) {
            $title = $rec->name;
            if ($escape) {
                $res = type_Varchar::escape($title);
            }
            
            return $title;
        }
        
        $cRec = sens2_Controllers::fetch($rec->controllerId);
        
        $title = sens2_Controllers::getVerbal($cRec, 'name') . '.';
        
        $nameVar = $rec->port . '_name';

        if ($cRec->config->{$nameVar}) {
            $title .= $cRec->config->{$nameVar};
        } else {
            $title .= $rec->port;
        }
        
        if ($escape) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->EXT('ctrState', 'sens2_Controllers', 'externalName=state,externalKey=controllerId');
        $data->query->where("#ctrState = 'active'");
        $data->query->orderBy('#controllerId,#port', 'DESC');
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('driver', 'class(interface=sens2_ControllerIntf, allowEmpty, select=title)', 'caption=Драйвер,silent,placeholder=Драйвер,removeAndRefreshForm=controllerId');
        $data->listFilter->view = 'horizontal';
        $ctr = Request::get('Ctr');
        if ($mvc instanceof $ctr) {
            $data->listFilter->showFields = 'title, controllerId, driver';
        } else {
            $data->listFilter->showFields = 'title';
        }
        $data->listFilter->input(null, 'silent');

        if (isset($data->listFilter->rec->controllerId)) {
            $data->query->where(array("#controllerId = '[#1#]'", $data->listFilter->rec->controllerId));
        }

        if (isset($data->listFilter->rec->driver)) {
            $cQuery = sens2_Controllers::getQuery();
            $cQuery->where(array("#driver = '[#1#]'", $data->listFilter->rec->driver));
            $cQuery->groupBy('id');
            $cQuery->show('id');
            $allControllers = $cQuery->fetchAll();
            $controllerOptArr = array();
            if ($allControllers) {
                $optKeys = array_keys($allControllers);
                $optKeys = arr::make($optKeys, true);
                $data->query->in('controllerId', $optKeys);
                $controllerOptArr = $data->listFilter->getField('controllerId')->type->prepareOptions();
                foreach ($controllerOptArr as $id => $name) {
                    if (!isset($optKeys[$id])) {
                        unset($controllerOptArr[$id]);
                    }
                }
            } else {
                $data->query->where("1=2");
            }
            $data->listFilter->getField('controllerId')->type->options = $controllerOptArr;
        }

        if (strlen($data->listFilter->rec->title)) {
            $data->query->EXT('cName', 'sens2_Controllers', 'externalName=name,externalKey=controllerId');

            $data->query->where(array("LOWER(#port) LIKE '%[#1#]%'", mb_strtolower($data->listFilter->rec->title)));
            $data->query->orWhere(array("LOWER(#name) LIKE '%[#1#]%'", mb_strtolower($data->listFilter->rec->title)));
            $data->query->orWhere(array("LOWER(#cName) LIKE '%[#1#]%'", mb_strtolower($data->listFilter->rec->title)));
        }

        $data->listFilter->fields['controllerId']->refreshForm = 'controllerId';
        $data->listFilter->fields['driver']->refreshForm = 'driver';

        $data->query->orderBy('lastValue', "DESC");
        $data->query->orderBy('id', "DESC");
    }
    
    
    /**
     * Изпълнява се след подготовката на редовете на листовия изглед
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if (is_array($data->rows)) {
            foreach ($data->rows as $id => &$row) {
                if (strlen($data->recs[$id]->value)) {
                    $row->value .= "<span class='measure'>" . self::getVerbal($data->recs[$id], 'uom') . '</span>';
                    if(isset($row->statusText)) {
                        $row->value = $row->statusText . ' ' . $row->value;
                    }
                }
            }
        }
    }
    
    
    /**
     * Добавяме означението за съответната мерна величина
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        static $configs = array();
        static $params = array();
        
        if ($rec->lastValue) {
            $color = dt::getColorByTime($rec->lastValue);
            $row->lastValue = ht::createElement('span', array('style' => "color:#{$color}"), $row->lastValue);
        }
        
        if ($rec->error && $rec->lastUpdate) {
            $color = dt::getColorByTime($rec->lastUpdate);
            $row->error = ht::createElement('span', array('style' => "font-size:0.8em;color:#{$color}"), $row->error);
        }
        
        // Определяне на вербалното име на порта
        
        if (!$configs[$rec->controllerId]) {
            $configs[$rec->controllerId] = sens2_Controllers::fetchField($rec->controllerId, 'config');
        }
        
        if (!$params[$rec->controllerId]) {
            $driver = sens2_Controllers::getDriver($rec->controllerId);
            $ctrRec = sens2_Controllers::fetch($rec->controllerId);
            $params[$rec->controllerId] = arr::combine($driver->getInputPorts($ctrRec->config), $driver->getOutputPorts($ctrRec->config));
        }
        
        $var = $rec->port . '_name';
        if ($configs[$rec->controllerId]->{$var}) {
            $row->port = type_Varchar::escape($rec->port . ' (' . $configs[$rec->controllerId]->{$var} . ')');
        } elseif (!empty($params[$rec->controllerId][$rec->port]->caption)) {
            $row->port = $rec->port . ' (' . type_Varchar::escape($params[$rec->controllerId][$rec->port]->caption . ')');
        } else {
            $row->port = $rec->port;
        }
        
        // Може ли потребителя да вижда хронологията на сметката
        $attr = array('title' => 'Хронологични записи');
        $attr = ht::addBackgroundIcon($attr, 'img/16/clock_history.png');
        
        core_RowToolbar::createIfNotExists($row->_rowTools);
        $ddTools = &$row->_rowTools;
        $url = array('sens2_DataLogs', 'List', 'indicatorId' => $rec->id);
        $ddTools->addLink('Записи', $url, 'ef_icon=img/16/clock_history.png,title=Хронологични записи');
        
        $row->controllerId = sens2_Controllers::getLinkToSingle($rec->controllerId, 'name');
        
        if ($rec->isOutput == 'no') {
            $icon = 'property.png';
        } else {
            $icon = 'hand-point.png';
        }
        
        $rec->format = type_Table::toArray($rec->format);
        
        foreach($rec->format as $r) {
            if(!isset($r->cond) || !isset($r->value)) continue;
            switch($r->cond) {
                case 'EQ': $cond = $rec->value == $r->value;
                    break;
                case 'GT': $cond = $rec->value > $r->value; 
                    break;
                case 'LT': $cond = $rec->value < $r->value;
                    break;
                case 'GTE' : $cond = $rec->value >= $r->value;
                    break;
                case 'LTE' : $cond = $rec->value <= $r->value;
                    break;
                default:
                    error("Unknown condition {$r->cond}");
            }
    
            switch($r->statusType) {
                case 'ok': $color = 'green';
                    break;
                case 'on': $color = 'blue';
                    break;
                case 'off': $color = 'grey';
                    break;
                case 'warning' : $color = 'orange';
                    break;
                case 'danger' : $color = 'red';
                    break;
                default:
                    $color = '#333';
            }
            if($cond) {
                 $row->statusText = "<small style='color:{$color}'>" . type_Varchar::escape($r->statusText) . '</small>';
                 break;
            }
        }

        $row->title = ht::createLink($row->title, $url, null, "ef_icon=img/16/{$icon}");

        if ($rec->error) {
            $row->ROW_ATTR['class'] = 'state-closed';
        }
    }
}
