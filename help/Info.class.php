<?php 

/**
 * Подсистема за помощ - Информация
 *
 *
 * @category  bgerp
 * @package   help
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class help_Info extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Помощна информация';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Статия';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'help_Wrapper, plg_Created, plg_State2, plg_RowTools2, plg_Search';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'menu,text=@';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'debug';
    public $canAdd = 'debug';
    public $canDelete = 'debug';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'debug';
    public $canDeletesysdata = 'debug';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, menu, class, lg, text, kWords';
    
    
    /**
     * Път до файла с данните
     */
    const DATA_FILE = 'help/data/HelpInfo2.csv';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FNC('title', 'varchar', 'caption=Област');
        
        $this->FLD('menu', 'varchar', 'caption=Меню');
        $this->FLD('class', 'varchar(64)', 'caption=Мениджър,mandatory,silent');
        $this->FLD('lg', 'varchar(2)', 'caption=Език,mandatory,silent');
        $this->FLD('text', 'richtext(bucket=Notes)', 'caption=Информация, hint=Текст на информацията за помощ');
        $this->FLD('url', 'url', 'caption=URL, hint=Линк към документацията на bgerp.com');
        $this->FLD('kWords', 'text(rows=2)', 'caption=Ключови думи');
        
        $this->setDbUnique('class,lg');
    }
    
    
    /**
     * Изчисляване на полето 'titla'
     */
    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = $rec->class . " ({$rec->lg})";
    }
    
    
    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Запис CSV', array($mvc, 'saveCSV'), 'warning=Наистина ли искате да запишете CSV файла?');
            $data->toolbar->addBtn('Извличане от менюто', array($mvc, 'InsertClasses'), 'warning=Наистина ли искате да извлечете пътищата от менюто?');
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->url) {
            $row->text .= "<div style='float:right;font-size:0.8em;'>" . ht::createLink('» виж документацията', $rec->url, null, 'target=_blank') . '</div>';
        }
        if (!$row->menu) {
            $row->menu = "<font color='red'>{$rec->class}</font>";
        }
        
        if ($row->menu) {
            if (cls::load($rec->class, true)) {
                try {
                    $mvc = cls::get($rec->class);
                } catch (Throwable $e) {
                    reportException($e);
                }
                if ($mvc && $mvc->haveRightfor('list')) {
                    $row->menu = ht::createLink($row->menu, array($rec->class), null, 'class=button');
                } else {
                    $row->menu = ht::createLink($row->menu, null, null, 'class=button btn-disabled');
                }
            } else {
                $row->menu = ht::createLink($row->menu, null, null, 'class=button btn-disabled');
            }
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec    Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if ($rec->class && !$rec->menu) {
            $rec->menu = self::getMenuPath($rec->class);
        }
    }
    
    
    /**
     * След подготвяне на формата за филтриране
     *
     * @param blast_EmailSend $mvc
     * @param stdClass        $data
     */
    public function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Подреждаме записите, като неизпратените да се по-нагоре
        $data->query->orderBy('menu', 'ASC');
    }
    
    
    /**
     *
     *
     * @param string $cond
     * @param string $fields
     * @param bool   $cache
     *
     * @return object
     */
    public static function fetch($cond, $fields = '*', $cache = true)
    {
        if ($cond == '-1') {
            $rec = new stdClass();
            $rec->title = 'Помощ за bgERP';
            $rec->class = '';
            $rec->lg = '';
            $rec->text = '';
            
            return $rec;
        }
        
        return parent::fetch($cond, $fields, $cache);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните
        $file = self::DATA_FILE;
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'menu',
            1 => 'class',
            2 => 'lg',
            3 => 'text',
            4 => 'url',
            5 => 'kWords'
        );
        
        $this->importing = true;
        
        // Дефолт стойностите за форматирането по подразбиране
        $format = array();
        $format['length'] = 0;
        $format['delimiter'] = ',';
        $format['enclosure'] = '"';
        $format['escape'] = '\\';
        $format['skip'] = '#';
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($this, $file, $fields, null, $format, true);
        
        // Връщаме вербалното представяне на резултата от импортирането
        return $cntObj->html;
    }
    
    
    /**
     * Добавя заготовки за класовете, които намери в менюто и пекетите
     */
    public function act_InsertClasses()
    {
        requireRole('debug');
        
        $cnt = 0;
        $classes = array();
        $mQuery = bgerp_Menu::getQuery();
        while ($mRec = $mQuery->fetch()) {
            $classes[$mRec->ctr] = $mRec->ctr;
        }
        $pQuery = core_Packs::getquery();
        while ($pRec = $pQuery->fetch()) {
            $classes[$pRec->startCtr] = $pRec->startCtr;
        }
        
        $query = self::getquery();
        $query->delete("#text = '' OR #text IS NULL");
        
        $toSave = array();
        
        foreach ($classes as $class) {
            if (!$class || !cls::load($class, true)) {
                continue;
            }
            if (!self::fetch("#class = '{$class}'")) {
                $toSave[$class] = $class;
            }
            
            $inst = cls::get($class);
            $plugins = arr::make($inst->loadList, true);
            if (count($plugins)) {
                foreach ($plugins as $plg) {
                    $plg = cls::get($plg);
                    if ($plg instanceof plg_ProtoWrapper) {
                        $plg->description();
                        if (count($plg->tabs)) {
                            foreach ($plg->tabs as $obj) {
                                $ctr = $obj->url['Ctr'];
                                if ($ctr && !self::fetch("#class = '{$ctr}'")) {
                                    $toSave[$ctr] = $ctr;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        foreach ($toSave as $class) {
            if (cls::load($class, true) === true) {
                $inst = cls::get($class);
                
                if ($inst instanceof core_Mvc) {
                    $reflector = new \ReflectionClass($inst);
                    $file = str_replace('\\', '/', $reflector->getFileName());
                    $bgerp = str_replace('\\', '/', EF_APP_PATH);
                    
                    if (stripos($file, $bgerp) !== false) {
                        $rec = (object) array(
                            'class' => $class,
                            'lg' => 'bg',
                        );
                        self::save($rec);
                        $cnt++;
                    }
                }
            }
        }
        
        if ($cnt > 0) {
            $res = "Добавени бяха {$cnt} нови записа.";
        } else {
            $res = 'Не бяха добавени нови записи.';
        }
        
        $res .= ' Към ' . ht::createLink('help_Info', array('help_Info'));
        
        return $res;
    }
    
    
    /**
     * След всяко обновяване на модела прави опит да запише csv файла
     */
    public function act_SaveCSV()
    {
        requireRole('debug');
        
        $query = self::getQuery();
        $recs = array();
        while ($r = $query->fetch()) {
            $recs[] = $r;
        }
        
        // Дефолт стойностите за форматирането по подразбиране
        $params = array();
        $params['delimiter'] = ',';
        $params['decPoint'] = ',';
        $params['dateFormat'] = 'd.m.Y';
        $params['datetimeFormat'] = 'd.m.y H:i';
        $params['thousandsSep'] = '';
        $params['enclosure'] = '"';
        $params['decimals'] = 2;
        $params['columns'] = 'none';
        $params['mandatory'] = 'text';
        
        $csv = csv_Lib::createCsv($recs, $this, array('menu', 'class', 'lg', 'text', 'url', 'kWords'), $params);
        $csv = str_replace(array("\n\r", "\r\n"), array("\n", "\n"), $csv);
        
        $path = getFullPath(self::DATA_FILE);
        if (file_put_contents($path, $csv)) {
            $res = "Файлът `{$path}` беше записан успешно.";
        } else {
            $res = "Неуспешен запис на файлът `{$path}`";
        }
        
        return $res;
    }
    
    
    /**
     * Връща пътя в менюто до зададения клас в записа
     */
    public static function getMenuPath($class)
    {
        $res = '';
        
        if (cls::load($class, true) === true) {
            $manager = cls::get($class);
            
            $plugins = arr::make($manager->loadList, true);
            if (count($plugins)) {
                foreach ($plugins as $plg) {
                    $plg = cls::get($plg);
                    if ($plg instanceof plg_ProtoWrapper) {
                        $plg->description();
                        if (count($plg->tabs)) {
                            $path = $menu = $subMenu = $pack = '';
                            foreach ($plg->tabs as $caption => $obj) {
                                $ctr = $obj->url['Ctr'];
                                if ($ctr == $class) {
                                    $path = str_replace('->', ' » ', $caption);
                                }
                                if ($mRec = bgerp_Menu::fetch("#ctr = '{$ctr}'")) {
                                    $menu = $mRec->menu;
                                    $subMenu = $mRec->subMenu;
                                } elseif ($pRec = core_Packs::fetch("#startCtr = '{$ctr}'")) {
                                    $pack = $pRec->name;
                                }
                                if ($path && $menu) {
                                    break;
                                }
                            }
                            if ($path) {
                                if ($menu) {
                                    $res = "{$menu} » {$subMenu} » {$path}";
                                } elseif ($pack) {
                                    $res = "Пакет `{$pack}` » {$path}";
                                }
                            }
                        }
                        break;
                    }
                }
            }
            if (!$res) {
                if ($mRec = bgerp_Menu::fetch("#ctr = '{$class}'")) {
                    $res = "{$menu} » {$subMenu}";
                } elseif ($pRec = core_Packs::fetch("#startCtr = '{$class}'")) {
                    $res = "Пакет `{$pRec->name}`";
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        setIfNot($data->_infoTitlebgERPName, 'bgERP');
        if (!$data->_infoTitleVersionName) {
            $cVersion = core_setup::CURRENT_VERSION;
            $cVersionArr = explode('-', $cVersion, 2);
            if (Mode::is('screenMode', 'narrow')) {
                $vStr = "<br>" . mb_strtolower($cVersionArr[1]);
            } else {
                $vStr = ' (' . $cVersionArr[1] . ')';
            }
            
            $data->_infoTitleVersionName = $cVersionArr[0] . $vStr;
        }
        
        $data->title = "|Помощ за|* {$data->_infoTitlebgERPName} {$data->_infoTitleVersionName}";
        
        $eArr = explode('(', $data->title);
    }
}
