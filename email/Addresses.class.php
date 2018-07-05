<?php 


/**
 * Модел съдържащ актуална информация, кой имейл адрес на кой обект (визитка или друг) отговаря.
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Addresses extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = 'Имейл адреси';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Имейл адрес';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/inbox-image-icon.png';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, ceo';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'email_Wrapper, plg_Modified';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('email', 'email', 'caption=Имейл');
        $this->FLD('classId', 'class', 'caption=Клас');
        $this->FLD('objectId', 'int', 'caption=Обект');
        
        $this->setDbUnique('email, classId, objectId');
        $this->setDbIndex('email');
    }
    
    
    /**
     * Последно модифицирания обект, който притежава този имейл адрес
     *
     * @param  string   $email
     * @return stdClass {classId: ..., objectId: ... }
     */
    public static function getObjectByEmail($email)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->orderBy('modifiedOn=ASC,id=ASC');     // търсим най-старата релация [имейл] -> [обект]
        $rec = $query->fetch("#email = '{$email}'");
        
        return $rec;
    }
    
    
    /**
     * Създава или променя вече съществуваща връзка м/у имейл и обект
     *
     * @param  string  $email
     * @param  int     $classId  key(mvc=core_Classes)
     * @param  int     $objectId
     * @return boolean FALSE при неуспех
     */
    public static function addEmail($email, $classId, $objectId)
    {
        $rec = (object) compact('email', 'classId', 'objectId');
        
        // Запис в режим `ignore`. Ако имейл адреса вече е бил регистриран на същия обект -
        // нищо не се променя.
        $result = static::save($rec, null, 'ignore');
        
        return $result;
    }
    
    
    /**
     * Прекъсва връзката между обект и всички негови регистрирани имейл адреси.
     *
     * @param  string  $email
     * @param  int     $classId  key(mvc=core_Classes)
     * @param  int     $objectId
     * @return boolean FALSE при неуспех
     */
    public static function removeEmails($classId, $objectId)
    {
        return static::delete("#classId = {$classId} AND #objectId = {$objectId}");
    }
}
