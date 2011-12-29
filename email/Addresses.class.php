<?php 
/**
 * Модел съдържащ актуална информация, кой имейл адрес на кой обект (визитка или друг) отговаря.
 * 
 * @category   bgerp
 * @package    email
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 *
 */
class email_Addresses extends core_Manager
{
    /**
     *  Заглавие на таблицата
     */
    var $title = "Имейл адреси";
    
    var $singleTitle = 'Имейл адрес';
    
    var $singleIcon  = 'img/16/inbox-image-icon.png';
    
    /**
     * Права
     */
    var $canRead = 'admin, ceo';
    
    
    /**
     *  
     */
    var $canEdit = 'admin, ceo';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, ceo';
    
    
    /**
     *  
     */
    var $canView = 'admin, ceo';
    
    
    /**
     *  
     */
    var $canList = 'admin, ceo';
    
    /**
     *  
     */
    var $canDelete = 'admin, ceo';
    
	
    /**
     * 
     */
	var $loadList = 'email_Wrapper, plg_Modified';
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("email", "email", "caption=Имейл");
		$this->FLD("classId", "class", 'caption=Клас');
		$this->FLD('objectId', 'int', 'caption=Обект');
		
		$this->setDbUnique('email, classId, objectId');
		$this->setDbIndex('email');
	}


	/**
	 * Последно модифицирания обект, който притежава този имейл адрес
	 *
	 * @param string $email
	 * @return stdClass {classId: ..., objectId: ... }
	 */
	public static function getObjectByEmail($email)
	{
		return static::fetch("#email = '{$email}'");
	}
	
	
	/**
	 * Създава или променя вече съществуваща връзка м/у имейл и обект
	 *
	 * @param string $email
	 * @param int $classId key(mvc=core_Classes)
	 * @param int $objectId
	 * @return boolean FALSE при неуспех
	 */
	public static function addEmail($email, $classId, $objectId)
	{
		if ( !($rec = static::fetch("#email = '{$email}'")) )  {
			$rec = new stdClass();
		}
		
		$rec->email    = $email;
		$rec->classId  = $classId;
		$rec->objectId = $objectId;
		
		$result = static::save($rec);
		
		return $result;
	}
}