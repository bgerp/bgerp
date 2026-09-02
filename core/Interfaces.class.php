<?php


/**
 * Клас 'core_Interfaces' - Регистър на интерфейсите
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_Interfaces extends core_Manager
{
    /**
     * Плъгини и класове за начално зареждане
     */
    public $loadList = 'plg_Created, plg_SystemWrapper, plg_RowTools2,plg_Sorting';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Никой потребител не може да добавя или редактира тази таблица
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Интерфейси';
    

    /**
     * Избягваме автоматичното регистриране на класа, защото още моделите за регистъра не са създадени
     */
    public $automaticRegisterClass = false;


    /**
     * Имена на интерфейсите, за които липсва код - име => име
     */
    protected static $missingInterfaces = array();


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Интерфейс, mandatory,width=100%');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,oldField=info');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, интерфайсите не могат да се редактират
        if (!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    
    
    /**
     * Добавя интерфейса в този регистър
     *
     * @param string $interface
     *
     * @return int|null - ид на интерфейса или NULL, ако липсва кода му
     */
    public static function add($interface)
    {
        // Ако липсва кода на интерфейса, той не може да бъде регистриран.
        // Само го запомняме, за да бъде репортван при инсталацията на пакета
        if (!cls::load($interface, true)) {
            self::$missingInterfaces[$interface] = $interface;

            return null;
        }

        $rec = new stdClass();
        
        $rec->name = $interface;
        $rec->title = cls::getTitle($interface);
        
        $exRec = self::fetch("#name = '{$interface}'");
        if ($exRec) {
            $rec->id = $exRec->id;
        } else {
            $inst = cls::get($interface);
            if ($inst->oldClassName ?? null) {
                $exRec = self::fetch("#name = '{$inst->oldClassName}'");
                if ($exRec) {
                    $rec->id = $exRec->id;
                }
            }
        }
        
        if (!$exRec || ($exRec->title != $rec->title) || ($exRec->name != $rec->name)) {
            self::save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Връща id-то на посочения интерфейс
     *
     * @param string $name
     * @param bool   $silent - ако липсва кода на интерфейса, да не се предизвиква грешка
     *
     * @return int|null
     */
    public static function fetchByName($name, $silent = false)
    {
        $id = self::add($name);

        if (!$id && $silent) {

            return null;
        }

        expect($id, 'Липсващ интерфейс', $name);

        return $id;
    }


    /**
     * Връща (и изчиства) имената на интерфейсите, за които липсва код
     *
     * @param bool $clear
     *
     * @return array име на интерфейс => име на интерфейс
     */
    public static function getMissing($clear = true)
    {
        $res = self::$missingInterfaces;

        if ($clear) {
            self::$missingInterfaces = array();
        }

        return $res;
    }
    
    
    /**
     * Връща масив с ид-та на поддържаните от класа интерфейси
     *
     * @param mixed $class string (име на клас) или object (инстанция) или int (ид на клас)
     *
     * @return array ключове - ид на интерфейси, стойности - TRUE
     */
    public static function getInterfaceIds($class)
    {
        if (is_scalar($class)) {
            $instance = cls::get($class);
        } else {
            $instance = $class;
        }
        
        cls::prepareInterfaces($instance);
        
        $list = $instance->interfaces;
        
        $result = array();
        
        if (countR($list)) {
            // Вземаме инстанция на core_Interfaces
            foreach ($list as $intf => $impl) {

                // Интерфейсите, за които липсва код, се пропускат - те се репортват при инсталация
                if (!$intfId = self::fetchByName($intf, true)) {
                    continue;
                }

                // Добавяме id в списъка
                $result[$intfId] = true;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Връща keylist с поддържаните от класа интерфейси
     *
     * @param mixed $class string (име на клас) или object (инстанция) или int (ид на клас)
     *
     * @return string keylist от ид-тата на интерфейсите
     */
    public static function getKeylist($class)
    {
        $keylist = self::getInterfaceIds($class);
        $keylist = keylist::fromArray($keylist);
        
        return $keylist;
    }
    
    
    /**
     * Рутинен метод, премахва интерфейсите, които са от посочения пакет или няма код за тях
     */
    public static function deinstallPack($pack)
    {
        $res = '';
        $query = self::getQuery();
        $preffix = $pack . '_';
        while ($rec = $query->fetch()) {
            if (strpos($rec->name, $preffix) === 0 || (!cls::load($rec->name, true))) {
                core_Interfaces::delete($rec->id);
                $res .= "<li class='debug-notice'>Беше премахнат интерфейса `{$rec->name}`</li>";
            }
        }
        
        return $res;
    }
}
