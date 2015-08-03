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
class planning_drivers_BaseTask extends core_ProtoInner
{
    
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'planning_TaskDetailIntf';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'planning, ceo';
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectInnerObject($userId = NULL)
    {
    	return core_Users::haveRole($this->canSelectSource, $userId);
    }
    
    
    /**
     * Добавяне на полета към формата на детайла
     * 
     * @param core_FieldSet $form
     */
    public function addDetailFields_(core_FieldSet &$form)
    {
    	
    }
    
    
    /**
     * След като са добавени полета към формата на детайла
     */
    public static function on_AfterAddDetailFields($Driver, $res, $form)
    {
    	// Ако има кеширани данни, извличаме ги
    	if($form->rec->data){
    		foreach ($form->rec->data as $name => $value){
    			$form->setDefault($name, $value);
    		}
    	}
    }
    
    
    /**
     * Проверява въведената форма
     * 
     * @param core_Form $form
     */
    public function checkDetailForm(core_Form &$form)
    {
    	
    }
    
    
    /**
     * Промяна на подготовката на детайла
     * 
     * @param stdClass $data
     */
    public function prepareDetailData_(&$data)
    {
    	// Ако има записи 
    	if(count($data->recs)){
    		
    		$form = planning_TaskDetails::getForm();
    		$this->addDetailFields($form);
    		
    		// За всички $recс от детайла
    		foreach ($data->recs as $id => &$rec){
    			$values = (array)$rec;
    			foreach ($values as $name => $value){
    				if($form->getFieldType($name) != $data->mvc->getFieldType($name)){
    					$data->rows[$id]->{$name} = $form->getFieldType($name)->toVerbal($value);
    				}
    			}
    			
    			if(isset($rec->data)){
    				
    				// Ако има в блоб полето кеширани стойностти от драйвера добавяме ги в $data->recs и $data->rows
    				foreach ($rec->data as $key => $value){
    					if(!$form->getField($key, FALSE)) continue;
    					
    					$rec->{$key} = $rec->data->{$key};
    					$data->rows[$id]->{$key} = $form->getFieldType($key)->toVerbal($rec->{$key});
    				
    					// Добавяме полетата от драйвера в лист изгледа
    					if(!isset($data->listFields[$key])){
    						if(isset($rec->{$key})){
    							$data->listFields[$key] = $form->getField($key)->caption;
    						}
    					}
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Рендиране на информацията на детайла
     */
    public function renderDetailData($data)
    {
    	$tpl = getTplFromFile('planning/tpl/BaseTaskDetailLayout.shtml');
    	$tpl->replace(cls::get('planning_TaskDetails')->renderListTable($data), 'TABLE');
    	
    	return $tpl;
    }
    
    
    /**
     * Обновяване на данните на мастъра
     */
    public function updateEmbedder()
    {
    	
    }
}