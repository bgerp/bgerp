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
	 * От кои класове може да се избира драйвера
	 */
	public $availableClasses;
	
	
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
    protected $detail;

    
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
    
    
    /**
     * Кой детайл да бъде добавен към мастъра
     * 
     * @return varchar - името на детайла
     */
    public function getDetail()
    {
    	return $this->detail;
    }
}