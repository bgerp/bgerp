<?php



/**
 * Базов драйвер за наследяване на други драйвери за задачи
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_drivers_BaseTask extends core_BaseClass
{
    
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'planning_TaskDetailIntf';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'planning, ceo';
    
    
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
     * Добавяне на полета към формата на детайла
     * 
     * @param core_FieldSet $form
     */
    public function addDetailFields(core_FieldSet &$form)
    {
    	
    }
    
    
    /**
     * Обновяване на данните на мастъра
     * 
     * @param int $id - ид
     */
    public function updateEmbedder($id)
    {
    	
    }
    
    
    /**
     * След подготовка на тулбара на детайла
     */
    protected static function on_AfterPrepareListToolbarDetail($mvc, &$data)
    {
    	$data->toolbar->removeBtn('binBtn');
    }
}