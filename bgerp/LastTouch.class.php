<?php



/**
 * Портален изглед на състоянието на системата
 *
 * Има възможност за костюмиране за всеки потребител
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_LastTouch extends core_Manager
{
    
    
    /**
     * Неща за зареждане в началото
     */
    public $loadList = 'bgerp_Wrapper';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Последно докосване';
    
    // Права
    
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('resource', 'varchar', 'caption=Ресурс, mandatory');
        $this->FLD('userId', 'user', 'caption=Потребител, mandatory');
        $this->FLD('lastTime', 'datetime', 'caption=Последно,input=none');
        
        $this->setDbUnique('resource,userId');
    }
    
    
    /**
     * Прави докосване на ресурса
     */
    public static function set($resource, $userId = null)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }

        $rec = self::fetch(array("#resource = '[#1#]' AND #userId = '[#2#]'", $resource, $userId));

        if (!$rec) {
            $rec = (object) array('resource' => $resource, 'userId' => $userId);
        }

        $rec->lastTime = dt::now();

        self::save($rec);
    }


    /**
     * Кога е докосван за последно ресурса
     */
    public static function get($resource, $userId = null)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }

        $rec = self::fetch(array("#resource = '[#1#]' AND #userId = '[#2#]'", $resource, $userId));

        if ($rec) {
            return $rec->lastTime;
        }
    }
}
