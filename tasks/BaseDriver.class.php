<?php



/**
 * Базов драйвер за наследяване на други драйвери за задачи
 *
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class tasks_BaseDriver extends core_BaseClass
{
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'tasks_DriverIntf';
	
	
	/**
	 * Какво да е дефолтното име на задача от драйвера
	 */
	protected $defaultTitle;
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'powerUser';
    
    
    /**
     * Кои детайли да се заредят динамично към мастъра
     */
    protected $details;

    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = NULL)
    {
    	return core_Users::haveRole($this->canSelectDriver, $userId);
    }
    
    
    /**
     * Връща дефолтното име на задача от драйвера
     * 
     * @return string
     */
    public function getDefaultTitle()
    {
    	return $this->defaultTitle;
    }
    
    
    /**
     * Обновяване на данните на мастъра
     *
     * @param int $id - ид
     * @return void
     */
    public function updateEmbedder(&$rec)
    {
    }
    
    
    /**
     * Кои детайли да се закачат динамично
     *
     * @return array $details - масив с детайли за закачане
     */
    public function getDetails()
    {
    	$details = arr::make($this->details, TRUE);
    	
    	return $details;
    }
}