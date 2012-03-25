<?php



/**
 * Клас 'core_Plugins' - Мениджър на плъгини
 *
 *
 * @category  all
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
    function on_AfterInputEditForm($mvc, $form)
    {
        if($form->rec->plugin && !cls::load($form->rec->plugin, TRUE)) {
            $form->setError('plugin', "Плъгинът|* {$rec->plugin} |не съществува");
        }
        
        if($form->rec->class && !cls::load($form->rec->class, TRUE)) {
            $form->setError('class', "Класът|* {$rec->class} |не съществува");
        }
    }
    
    
    /**
     * Инсталира нов плъгин, към определен клас
     */
    function installPlugin($name, $plugin, $class, $cover = 'family', $state = 'active')
    {
        $this->delete("#plugin = '{$plugin}' AND #class = '{$class}'");

        $rec = new stdClass();
        $rec->name = $name;
        $rec->plugin = $plugin;
        $rec->class = $class;
        $rec->state = $state;
        $rec->cover = $cover;
        
        return $this->save($rec);
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
}