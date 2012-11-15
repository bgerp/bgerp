<?php



/**
 * Клас 'core_Plugins' - Мениджър на плъгини
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Plugins extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Регистър на плъгините';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_SystemWrapper,plg_RowTools,plg_State';
    
    
    /**
     * Описание на модела
     */
    function description()
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
    static function on_AfterInputEditForm($mvc, $form)
    {
        if($form->rec->plugin && !cls::load($form->rec->plugin, TRUE)) {
            $form->setError('plugin', "Плъгинът|* {$rec->plugin} |не съществува");
        }
        
        if($form->rec->class && !cls::load($form->rec->class, TRUE)) {
            $form->setError('class', "Класът|* {$rec->class} |не съществува");
        }
    }
    

    /**
     * Форсирано инсталиране на плъгин. Ако има други със същотот име, те ще бъдат спрени
     */
    static function forcePlugin($name, $plugin, $class, $cover = 'family', $state = 'active')
    {
        $res = static::installPlugin($name, $plugin, $class, $cover, $state, TRUE);

        return $res;

    }
    

    /**
     * Не-форсирано инсталиране на плъгин. Ако има други със същотот име, те ще бъдат останат, а зададения няма да се закачи
     */
    static function installPlugin($name, $plugin, $class, $cover = 'family', $state = 'active', $force = FALSE)
    {
        $status = static::setupPlugin($name, $plugin, $class, $cover, $state, $force);

        if($status === 0) {
            $res = "<li><b>{$name}</b>: Плъгинът <b>{$plugin}</b> и до сега е бил закачен към <b>{$class}</b> ({$cover}, {$state}) </li>";
        } elseif($status === -1) {
            $res = "<li style='color:#660000;'>Друг плъгин изпълнява ролята <b>{$name}</b>, затова <b>{$plugin}</b> не е закачен към <b>{$class}</b> ({$cover}, {$state}) </li>";
        } else {
            $res = "<li style='color:green;'><b>{$name}</b>: Плъгинът <b>{$plugin}</b> беше закачен към <b>{$class}</b> ({$cover}, {$state}) </li>";
        }

        return $res;

    }


    /**
     * Инсталира нов плъгин, към определен клас
     */
    static function setupPlugin($name, $plugin, $class, $cover = 'family', $state = 'active', $force = FALSE)
    {   
        // Ако плъгина е вече инсталиран - на правим нищо
        if(static::fetch(array("#name = '[#1#]' AND #state = '{$state}' AND #plugin = '{$plugin}' AND #class = '{$class}' AND #cover = '{$cover}'", $name))) {

            return 0;
        }

        // Изтриваме съществуващите прикачания на този плъгин към посочения клас
        static::delete("#plugin = '{$plugin}' AND #class = '{$class}'");
        
        // Ако има друг плъгин със същото име и не се изисква форсиране на този - излизаме
        if(!$force && static::fetch(array("#name = '[#1#]' AND #state = 'active'", $name))) {
            
            return -1;
        }

        // Спираме всички плъгини със същтото име
        $query = static::getQuery();
        while($rec = $query->fetch(array("#name = '[#1#]'", $name))) {
            $rec->state = 'stopped';
            static::save($rec);
        }
        
        $rec = new stdClass();
        $rec->name = $name;
        $rec->plugin = $plugin;
        $rec->class = $class;
        $rec->state = $state;
        $rec->cover = $cover;
        
        return static::save($rec);
    }
    
    
    /**
     * Деинсталира даден плъгин
     */
    function deinstallPlugin($plugin)
    {
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
    function attach(&$obj)
    {
        // Ако не са заредени прикачените плъгини, правим им начално зареждане
        if(!is_array($this->attachedPlugins)) {
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
                if (count($arr = $this->attachedPlugins[$objClass][$cover])) {
                    foreach ($arr as $name => $plugin) {
                        if (cls::load($plugin, TRUE)) {
                            $obj->loadSingle($name, $plugin);
                        } else {
                            DEBUG::log("Липсващ плъгин: {$plugin}");
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
     * параметри на метода createEventCatcher, който създава класа на
     * плъгина
     */
    function setPlugin($class, $plugin, $cover = 'private', $name = NULL)
    {
        $class = strtolower($class);
        $name = $name ? $name : $plugin;
        $this->attachedPlugins[$class][$cover][$name] = $plugin;
    }
    
    
    /**
     * Рутинен метод, премахва прикачанията, свързани с класове от посочения пакет
     */
    static function deinstallPack($pack)
    {
        $query = self::getQuery();
        $preffix = $pack . "_";
        $query->delete(array("#class LIKE '[#1#]%' OR #plugin LIKE '[#1#]%'", $preffix));
    }


    /**
     * функция, която автоматично изчиства лишите линкове от менюто
     */
    function repair()
    {
        $query = $this->getQuery();

        while($rec = $query->fetch()) {

            if(!cls::load($rec->plugin, TRUE)) {
                $this->delete($rec->id);

                $res .= "<li style='color:red;'>Премахнато е {$rec->name} защото липсва плъгина {$rec->plugin}</li>";

                continue;
            }

            if(!cls::load($rec->class, TRUE)) {
                $this->delete($rec->id);

                $res .= "<li style='color:red;'>Премахнато е {$rec->name} защото липсва класа {$rec->class}</li>";

                continue;
            }

        }

    }

}