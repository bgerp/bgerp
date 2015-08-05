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
     * Обновяване на данните на мастъра
     *
     * @param int $id - ид
     * @param return void
     */
    public function updateEmbedder($id)
    {
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
     * Възможност за промяна след подготовката на формата на детайла
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareEditFormDetail(&$data)
    {
    }


    /**
     * Възможност за промяна след събмита на формата на детайла
     *
     * @param core_Form $form
     * @return void
     */
    public function inputEditFormDetail(core_Form $form)
    {
    }
    
    
    /**
     * Възможност за промяна след подготовката на детайла
     *
     * @param core_ET $tpl
     * @param stdClass $data
     * @return void
     */
    public function prepareDetail(&$data)
    {
    }
    
    
    /**
     * Възможност за промяна след подготовката на лист тулбара
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareListToolbarDetail(&$data)
    {
    	$data->toolbar->removeBtn('binBtn');
    }
    
    
    /**
     * Възможност за промяна след обръщането на данните във вербален вид
     *
     * @param stdClass $row
     * @param stdClass $rec
     * @return void
     */
    public function recToVerbalDetail(&$row, $rec)
    {
    }
    
    
    /**
     * Възможност за промяна след рендирането на детайла
     * 
     * @param core_ET $tpl
     * @param stdClass $data
     * @return void
     */
    public function renderDetail(&$tpl, $data)
    {
    }
    
    
    /**
     * Възможност за промяна след рендирането на шаблона на детайла
     *
     * @param core_ET $tpl
     * @param stdClass $data
     * @return void
     */
    public function renderDetailLayout(&$tpl, $data)
    {
    }
}