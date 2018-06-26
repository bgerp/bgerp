<?php



/**
 * Мениджър за класове които използват вградени драйвери. При създаване се избира драйвер,
 * който в последствие поема част от управлението на мастъра
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @deprecated
 */
class core_Embedder extends core_Master
{
	
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $innerObjectInterface;
	
	
	/**
	 * Как се казва полето за избор на вътрешния клас
	 */
	public $innerClassField;
	
	
	/**
	 * Как се казва полето за данните от формата на драйвъра
	 */
	public $innerFormField;
	
	
	/**
	 * Как се казва полето за записване на вътрешните данни
	 */
	public $innerStateField;
	
	
	/**
	 * Кеш на инстанцираните вградени класове
	 */
	protected $Drivers = array();
	
	
	/**
	 * Кои полета от мениджъра преди запис да се обновяват със стойностти от драйвера
	 */
	protected $fieldsToBeManagedByDriver;
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		setIfNot($mvc->innerClassField, 'innerClass');
		setIfNot($mvc->innerFormField, 'innerForm');
		setIfNot($mvc->innerStateField, 'innerState');
		
		expect($mvc->innerObjectInterface);
		expect(is_subclass_of($mvc->innerObjectInterface, 'core_InnerObjectIntf'));
		
		// Добавяме задължителните полета само ако не е дефинирано, че вече съществуват
		
		if(!isset($mvc->fields[$mvc->innerClassField])){
			$mvc->FLD($mvc->innerClassField, "class(interface={$mvc->innerObjectInterface}, allowEmpty, select=title)", "caption=Вид,mandatory,silent,refreshForm");
		}
		
		if(!isset($mvc->fields[$mvc->innerFormField])){
			$mvc->FLD($mvc->innerFormField, "blob(1000000, serialize, compress)", "caption=Филтър,input=none,column=none");
		}
		
		if(!isset($mvc->fields[$mvc->innerStateField])){
			$mvc->FLD($mvc->innerStateField, "blob(1000000, serialize, compress)", "caption=Данни,input=none,column=none,single=none");
		}
		
		// Кои полета да се помнят след изтриване
		$fieldsBeforeDelete = "id, {$mvc->innerClassField}, {$mvc->innerFormField}, {$mvc->innerStateField}";
		$mvc->fetchFieldsBeforeDelete = $fieldsBeforeDelete;
	}
	
	
	/**
	 * Взима избрания драйвър за записа
	 * 
	 * @param mixed $id - ид/запис
	 */
	public function getDriver_($id)
	{
		$rec = $this->fetchRec($id);
		$rec->{$this->innerClassField} = ($rec->{$this->innerClassField}) ? $rec->{$this->innerClassField} : $this->fetchField($rec->id, $this->innerClassField);
		
		if($rec->id){
			$innerForm = (isset($rec->{$this->innerFormField})) ? $rec->{$this->innerFormField} : $this->fetchField($rec->id, $this->innerFormField);
			$innerState = (isset($rec->{$this->innerStateField})) ? $rec->{$this->innerStateField} : $this->fetchField($rec->id, $this->innerStateField);
		}
		
		if(empty($this->Drivers[$rec->id])){
			if(cls::load($rec->{$this->innerClassField}, TRUE)){
				$Driver = cls::get($rec->{$this->innerClassField});
				$Driver->EmbedderRec = new core_ObjectReference($this, $rec->id);
				$this->Drivers[$rec->id] = $Driver;
			} else {
				
				return FALSE;
			}
		}
		
		// За всеки случай задава наново вътрешното състояние и форма
		$this->Drivers[$rec->id]->setInnerForm($innerForm);
		$this->Drivers[$rec->id]->setInnerState($innerState);
		
		return $this->Drivers[$rec->id];
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = &$form->rec;
		
		// Извличаме класовете с посочения интерфейс
		$interfaces = core_Classes::getOptionsByInterface($mvc->innerObjectInterface, 'title');
		if(count($interfaces)){
			foreach ($interfaces as $id => $int){
				if(!cls::load($id, TRUE)) continue;
				
				$Driver = cls::get($id);
				
				// Ако потребителя не може да го избира, махаме го от масива
				if(!$Driver->canSelectInnerObject()){
					unset($interfaces[$id]);
				}
			}
		}
	
		// Ако няма достъпни драйвери полето е readOnly иначе оставяме за избор само достъпните такива
		if(!count($interfaces)) {
			$form->setReadOnly($mvc->innerClassField);
		} else {
			$form->setOptions($mvc->innerClassField, $interfaces);
			
			// Ако е възможен точно един драйвер, задаваме го по подразбиране да е избран
			if(count($interfaces) == 1){
				$form->setDefault($mvc->innerClassField, key($interfaces));
				$form->setReadOnly($mvc->innerClassField);
			}
		}
	
		// Ако има запис, не може да се сменя източника и попълваме данните на формата с тези, които са записани
		if($id = $rec->id) {
			$form->setReadOnly($mvc->innerClassField);
			
			$filter = (is_object($rec->{$mvc->innerFormField})) ? clone $rec->{$mvc->innerFormField} : $rec->{$mvc->innerFormField};
			
			foreach ((array)$filter as $key => $value){
				if(empty($rec->{$key})){
					$rec->{$key} = $value;
				}
			}
			
			// Махаме от река полетата за вътрешната форма и състояние, защотот те ще се генерират в последствие
			unset($rec->{$mvc->innerFormField}, $rec->{$mvc->innerStateField});
			
		}
		
		// Ако има източник инстанцираме го
		if($rec->{$mvc->innerClassField}) {
			
			if($Driver = $mvc->getDriver($rec)){
				$fieldsBefore = arr::make(array_keys($form->selectFields()), TRUE);
				
				// Източника добавя полета към формата
				$Driver->addEmbeddedFields($form);
				
				$form->input(NULL, 'silent');
					
				// Източника модифицира формата при нужда
				$Driver->prepareEmbeddedForm($form);
				
				$mvc->invoke('AfterPrepareEmbeddedForm', array($form));
				
				// Намираме всички полета за показване, и ги маркираме за изтриване след смяна на драйвер
				$fieldsAfter = arr::make(array_keys($form->selectFields()), TRUE);
				$removeFields = array_diff_assoc($fieldsAfter, $fieldsBefore);
				$removeFields = implode('|', array_keys($removeFields));
				$form->setField($mvc->innerClassField, "removeAndRefreshForm={$removeFields}");
			} else {
				$form->info = tr("|*<b style='color:red'>|Има проблем при зареждането на драйвера|*</b>");
			}
		}
		
		$form->input(NULL, 'silent');
	}
	
	
	/**
	 * Изпълнява се след въвеждането на данните от заявката във формата
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{ 
		if($form->rec->{$mvc->innerClassField}){
			
			// Инстанцираме драйвера
			if($Driver = $mvc->getDriver($form->rec)){
				// Проверяваме може ли въпросния драйвер да бъде избран
				if(!$Driver->canSelectInnerObject()){
					$form->setError($mvc->innerClassField, 'Нямате права за избрания източник');
				}
					
				// Източника проверява подадената форма
				$Driver->checkEmbeddedForm($form);
			}
		}
		 
		if($form->isSubmitted()) {
			$form->rec->{$mvc->innerFormField} = clone $form->rec;
		}
	}
	
	
	/**
	 * След подготовка на сингъла
	 */
	public static function on_AfterPrepareSingle($mvc, &$res, $data)
	{
		if($Driver = $mvc->getDriver($data->rec)){
			// Драйвера подготвя данните
			$embeddedData = $Driver->prepareEmbeddedData();
			
			// Предизвикваме ивент, ако мениджъра иска да обработи подготвените данни
			$mvc->invoke('AfterPrepareEmbeddedData', array(&$data, &$embeddedData));
			
			$data->embeddedData = $embeddedData;
		}
	}
	
	
	/**
	 * Вкарваме css файл за единичния изглед
	 */
	public static function on_AfterRenderSingle($mvc, &$tpl, $data)
	{
		if($Driver = $mvc->getDriver($data->rec)){
			$Driver->renderEmbeddedData($tpl, $data->embeddedData);
		} else {
			$tpl->append(new ET(tr("|*<h2 class='red'>|Проблем при показването на драйвера|*</h2>")));
		}
	}
	
	
	/**
	 * Преди запис
	 */
	public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
	{
		$innerClass = (!empty($rec->{$mvc->innerClassField})) ? $rec->{$mvc->innerClassField} : $mvc->fetchField($rec->id, $mvc->innerClassField);
		
		// Подсигуряваме се, че няма по погрешка да забършим полетата за вътрешното състояние
		if($rec->id){
			$rec->{$mvc->innerStateField} = (!empty($rec->{$mvc->innerStateField})) ? $rec->{$mvc->innerStateField} : $mvc->fetchField($rec->id, $mvc->innerStateField);
			$rec->{$mvc->innerFormField} = (!empty($rec->{$mvc->innerFormField})) ? $rec->{$mvc->innerFormField} : $mvc->fetchField($rec->id, $mvc->innerFormField);
		}
		
		if(!cls::load($innerClass, TRUE)) return;
		$innerDrv = cls::get($innerClass);
		
		// Извикваме на драйвера събитие, той да се грижи за вътрешното си състояние
		if($res = $innerDrv->invoke('BeforeSave', array(&$rec->{$mvc->innerStateField}, &$rec->{$mvc->innerFormField}, &$rec, $fields, $mode))){
			
			// Ако има зададени полета за поддръжка
			if(!empty($mvc->fieldsToBeManagedByDriver)){
				$fieldsToManage = arr::make($mvc->fieldsToBeManagedByDriver, TRUE);
				
				// За всяко от тях присвояваме зададената стойност от вътрешното състояние на формата
				foreach ($fieldsToManage as $fName){
					$rec->$fName = $rec->{$mvc->innerFormField}->$fName;
				}
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
	{
		$innerClass = (!empty($rec->{$mvc->innerClassField})) ? $rec->{$mvc->innerClassField} : $mvc->fetchField($rec->id, $mvc->innerClassField);
		
		$innerDrv = cls::get($innerClass);
		
		return $innerDrv->invoke('AfterSave', array(&$rec->{$mvc->innerStateField}, $rec->{$mvc->innerFormField}, &$rec, $fields, $mode));
	}
	
	
	/**
	 * Изпълнява се след извличане на запис чрез ->fetch()
	 */
	public static function on_AfterRead($mvc, $rec)
	{
		if(cls::load($rec->{$mvc->innerClassField}, TRUE)){
			$innerDrv = cls::get($rec->{$mvc->innerClassField});
			
			return $innerDrv->invoke('AfterRead', array(&$rec->{$mvc->innerStateField}, &$rec));
		}
	}
	
	
	/**
	 * След изтриване на записи
	 */
	public static function on_AfterDelete($mvc, $numRows, $query, $cond)
	{
		foreach ($query->getDeletedRecs() as $rec) {
			$innerDrv = cls::get($rec->{$mvc->innerClassField});
			
			$innerDrv->invoke('AfterDelete', array(&$rec->{$mvc->innerStateField}, &$rec));
		}
	}
	
	
	/**
	 * Преди изтриване на запис
	 */
	public static function on_BeforeDelete($mvc, &$res, &$query, $cond)
	{
		$_query = clone($query);
		
		while ($rec = $_query->fetch($cond)) {
			$innerDrv = cls::get($rec->{$mvc->innerClassField});
			
			$innerDrv->invoke('BeforeDelete', array(&$res, &$query, $cond));
		}
	}
	
	
	/**
	 * Добавя ключови думи за пълнотекстово търсене
	 */
	public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
	{
		if(!empty($rec->{$mvc->innerClassField})){
			
			if($Driver = $mvc->getDriver($rec)){
				$Driver->alterSearchKeywords($res);
			}
		}
	}
}