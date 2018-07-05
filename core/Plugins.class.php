<?php



/**
 * Клас 'core_Plugins' - Мениджър на плъгини
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Plugins extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Регистър на плъгините';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Регистър на плъгините';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    public $loadList = 'plg_SystemWrapper,plg_RowTools2,plg_State';
    

    /**
     * Масив с плъгините, които се прикачат динамично
     *
     * @var array
     */
    private $attachedPlugins;
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Име,mandatory');
        $this->FLD('plugin', 'varchar(128)', 'caption=Плъгин,mandatory');
        $this->FLD('class', 'varchar(128)', 'caption=Клас,mandatory');
        $this->FLD('cover', 'enum(private=Частен,family=Фамилен)', 'caption=Обхват');
        $this->FLD('state', 'enum(active=Активно,stopped=Спряно)', 'caption=Състояние');
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от формата
     * Използва се обикновено за проверка на входните параметри
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->rec->plugin && !cls::load($form->rec->plugin, true)) {
            $form->setError('plugin', "Плъгинът|* {$rec->plugin} |не съществува");
        }
        
        if ($form->rec->class && !cls::load($form->rec->class, true)) {
            $form->setError('class', "Класът|* {$rec->class} |не съществува");
        }
    }
    

    /**
     * Форсирано инсталиране на плъгин. Ако има други със същотот име, те ще бъдат спрени
     */
    public static function forcePlugin($name, $plugin, $class, $cover = 'family', $state = 'active')
    {
        $res = static::installPlugin($name, $plugin, $class, $cover, $state, true);

        return $res;
    }
    

    /**
     * Не-форсирано инсталиране на плъгин. Ако има други със същотот име, те ще бъдат останат, а зададения няма да се закачи
     */
    public static function installPlugin($name, $plugin, $class, $cover = 'family', $state = 'active', $force = false)
    {
        if ($res = static::stopUnusedPlugin($plugin, $class)) {
            
            return $res;
        }
        
        $status = static::setupPlugin($name, $plugin, $class, $cover, $state, $force);

        if ($status === 0) {
            $res = "<li><b>{$name}</b>: Плъгинът <b>{$plugin}</b> и до сега е бил закачен към <b>{$class}</b> ({$cover}, {$state}) </li>";
        } elseif ($status === -1) {
            $res = "<li style='color:#660000;'>Друг плъгин изпълнява ролята <b>{$name}</b>, затова <b>{$plugin}</b> не е закачен към <b>{$class}</b> ({$cover}, {$state}) </li>";
        } else {
            $res = "<li style='color:green;'><b>{$name}</b>: Плъгинът <b>{$plugin}</b> беше закачен към <b>{$class}</b> ({$cover}, {$state}) </li>";
        }
        
        return $res;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('name');
    }
    
    
    /**
     * Инсталира нов плъгин, към определен клас
     */
    public static function setupPlugin($name, $plugin, $class, $cover = 'family', $state = 'active', $force = false)
    {
        // Ако плъгина е вече инсталиран - на правим нищо
        if (static::fetch(array("#name = '[#1#]' AND #state = '{$state}' AND #plugin = '{$plugin}' AND #class = '{$class}' AND #cover = '{$cover}'", $name))) {
            
            return 0;
        }

        // Изтриваме съществуващите прикачания на този плъгин към посочения клас
        static::delete("#plugin = '{$plugin}' AND #class = '{$class}'");
        
        // Ако има друг плъгин със същото име и не се изисква форсиране на този - излизаме
        if (!$force && static::fetch(array("#name = '[#1#]' AND #state = 'active'", $name))) {
            
            return -1;
        }

        // Спираме всички плъгини със същтото име
        $query = static::getQuery();
        while ($rec = $query->fetch(array("#name = '[#1#]'", $name))) {
            $rec->state = 'stopped';
            static::save($rec);
        }
        
        $rec = new stdClass();
        $rec->name = $name;
        $rec->plugin = $plugin;
        $rec->class = $class;
        $rec->state = $state;
        $rec->cover = $cover;
        
        $self = cls::get('core_Plugins');
        $self->setPlugin($rec->class, $rec->plugin, $rec->cover, $rec->name);

        return static::save($rec);
    }
    
    
    /**
     * Деинсталира даден плъгин
     */
    public function deinstallPlugin($plugin)
    {
        foreach ($this->attachedPlugins as $class => $r1) {
            foreach ($r1 as $cover => $r2) {
                foreach ($r2 as $name => $cPlg) {
                    if ($cPlg == $plugin) {
                        unset($this->attachedPlugins[$class][$cover][$name]);
                    }
                }
            }
        }

        return $this->delete("#plugin = '{$plugin}'");
    }
    
    /****************************************************************************************
     *                                                                                      *
     *         Методи за 'закачане' и 'откачане' на плъгини                                 *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Закача плъгините към посочения обект, ако има сетнати някакви
     * Ако плъгинът е описан чрез стринг, то той се смята за име на
     * класа на плъгина. Ако е описан чрез масив, то елементите му са
     * параметри на метода createEventCatcher, който създава класа на
     * плъгина
     */
    public function attach(&$obj)
    {
        // Ако не са заредени прикачените плъгини, правим им начално зареждане
        if (!is_array($this->attachedPlugins)) {
            $this->attachedPlugins = array();
            $query = $this->getQuery();
            
            while ($rec = $query->fetch("#state = 'active'")) {
                $this->setPlugin($rec->class, $rec->plugin, $rec->cover, $rec->name);
            }
        }
        
        if (count($this->attachedPlugins)) {
            // Какъв е класът на този обект?
            $objClass = strtolower(get_class($obj));
            $cover = 'private';
            
            do {
                if (isset($this->attachedPlugins[$objClass][$cover]) && count($arr = $this->attachedPlugins[$objClass][$cover])) {
                    foreach ($arr as $name => $plugin) {
                        if (cls::load($plugin, true)) {
                            $obj->loadSingle($name, $plugin);
                        } else {
                            $this->logWarning("Липсващ плъгин: {$plugin}");
                        }
                    }
                }
                
                $cover = 'family';
            } while ($objClass = strtolower(get_parent_class($objClass)));
        }
    }
    
    
    /**
     * Инсталира плъгин. Ако параметърът е стринг, то той е името на
     * класа на плъгина. Ако параметърът е масив, то елементите му са
     * параметри на метода createEventCatcher, който създава класа на плъгина
     */
    public function setPlugin($class, $plugin, $cover = 'private', $name = null)
    {
        $singletons = cls::getSingletons();
        
        if (isset($singletons[$class]) && !($singletons[$class] instanceof stdClass)) {
            
            // Ако класа вече е зареден в паметта, закачаме плъгина с `load`
            $Cls = cls::get($class);
            $Cls->load($plugin);

            // Извикваме on_AfterDescription, защото това викане вече е минало
            if (method_exists($plugin, 'on_AfterDescription')) {
                $Cls->_plugins[$plugin]->on_AfterDescription($Cls);
            }
        } else {
            
            // Ако не е закачен запомняме, че този плъгин трябва да се закачи при инстанцирането на класа
            $class = strtolower($class);
            $name = $name ? $name : $plugin;
            $this->attachedPlugins[$class][$cover][$name] = $plugin;
        }
    }
    
    
    /**
     * Рутинен метод, премахва прикачанията, свързани с класове от посочения пакет
     */
    public static function deinstallPack($pack)
    {
        $query = self::getQuery();
        $preffix = $pack . '_';
        $query->delete(array("#class LIKE '[#1#]%' OR #plugin LIKE '[#1#]%'", $preffix));
    }
    
    
    /**
     * Ако липсва кода на плъгина или класа, да не се спира съответния плъгин
     *
     * @param string $plugin
     * @param string $class
     *
     * @return string
     */
    public static function stopUnusedPlugin($plugin, $class)
    {
        $pluginLoad = cls::load($plugin, true);
        $classLoad = cls::load($class, true);
        
        // Ако не може да се зареди плъгина или класа
        if (!$pluginLoad || !$classLoad) {
            $cnt = 0;
            $str = '';
            
            // Всички плъгини, които не са спряни
            // с липсващ код на класа или на плъгина
            $query = static::getQuery();
            $query->where("#state != 'stopped'");
            
            if (!$pluginLoad) {
                $query->where(array("#plugin = '[#1#]'", $plugin));
            } elseif (!$classLoad) {
                $query->where(array("#class = '[#1#]'", $class));
            }
            
            
            while ($rec = $query->fetch()) {
                
                // Сменяме  състоянието
                $rec->state = 'stopped';
                static::save($rec, 'state');
                $cnt++;
            }
            
            if (!$pluginLoad) {
                $str = "'{$plugin}'";
            }
            
            if (!$classLoad) {
                if ($str) {
                    $str .= ' и ';
                }
                $str .= "класът '{$class}'";
            }
            
            if ($cnt) {
                $res = "<li class='debug-error'>Спрян е плъгинът '{$plugin}', защото липсва {$str}</li>";
            } else {
                $res = "<li class='debug-error'>Не е закачен плъгинът '{$plugin}', защото липсва {$str}</li>";
            }
            
            return $res;
        }
        
        return false;
    }


    /**
     * функция, която автоматично изчиства лишите линкове от менюто
     */
    public function repair()
    {
        $query = $this->getQuery();

        while ($rec = $query->fetch()) {
            if (!cls::load($rec->plugin, true)) {
                $this->delete($rec->id);

                $res .= "<li class='debug-error'>Премахнато е {$rec->name} защото липсва плъгина {$rec->plugin}</li>";

                continue;
            }

            if (!cls::load($rec->class, true)) {
                $this->delete($rec->id);

                $res .= "<li class='debug-error'>Премахнато е {$rec->name} защото липсва класа {$rec->class}</li>";

                continue;
            }
        }
    }
}
