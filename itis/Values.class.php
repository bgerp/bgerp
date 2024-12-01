<?php


/**
 * Модел за дълги текстови стойности
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 */
class itis_Values extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, itis_Wrapper, plg_Sorting, plg_Created';
    
    
    /**
     * Заглавие
     */
    public $title = 'Групи IT устройства';
    
    
    /**
     * Права за запис
     */
    public $canWrite = 'ceo,itis,admin';
    
    
    /**
     * Права за четене
     */
    public $canRead = 'ceo,itis,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,itis';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,itis';
    
 
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('hash', 'varchar(32)', 'caption=Стойност');

        $this->FLD('value', 'text', 'caption=Стойност');

        $this->setDbIndex('hash');
    }
    
    
    /**
     * Извлича Id-то според стойността
     */
    public static function getId($value)
    {
        if($value === null || $value === '') return null;
        
        $hash = md5($value);

        $id = self::fetchField(array("#hash = '[#1#]' AND #value = '[#2#]'", $hash, $value));

        if(!$id) {
            $id = self::save((object) array('value' => $value, 'hash' => $hash));
        }

        return $id;         
    }


    /**
     * Извлича Id-то според стойността
     */
    public static function getValue($id)
    {
       if($id === null || $id === '') return null;

        return self::fetchField($id, 'value');
    }

}
