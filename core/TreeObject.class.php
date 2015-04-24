<?php



/**
 * Клас 'core_TreeObject' - клас за наследяване на обект с дървовидна структура
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
abstract class core_TreeObject extends core_Manager
{
	
	
	/**
	 * След сетъп кой да е първия създаден обект, от който ще тръгне наследяването
	 * 
	 * @param mixed - име или NULL ако не искаме да има такъв обект
	 */
	public $defaultParent;
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->parentFieldName, 'parentId');
		setIfNot($mvc->nameField, 'name');
		setIfNot($mvc->systemIdFieldName, 'sysId');
		
		// Създаваме поле за име, ако няма такова
		if(!$mvc->getField($mvc->nameField, FALSE)){
			$mvc->FLD($mvc->nameField, "varchar(64)", 'caption=Наименование, mandatory');
		}
		
		// Поставяме поле за избор на баща, ако вече не съществува такова
		if(!$mvc->getField($mvc->parentFieldName, FALSE)){
			$mvc->FLD($mvc->parentFieldName, "key(mvc={$mvc->className},allowEmpty,select={$mvc->nameField})", 'caption=В състава на');
		}
		
		// Дали наследниците на обекта да са счетоводни пера
		$mvc->FLD('makeDescendantsFeatures', "enum(no=Не,yes=Да)", 'caption=Наследниците дали да бъдат сч. признаци->Избор,notNull,value=yes');
		
		if(!$mvc->getField($mvc->systemIdFieldName, FALSE)){
			$mvc->FLD($mvc->systemIdFieldName, 'varchar', 'input=none');
		}
		
		// Поставяне на уникален индекс
		$mvc->setDbUnique($mvc->nameField);
		$mvc->setDbUnique($mvc->systemIdFieldName);
	}
	
	
	/**
	 * Какво правим след сетъпа на модела?
	 */
	protected static function on_AfterSetupMVC($mvc, &$res)
	{
		// Ако има данни за дефолт параметър
		if($mvc->defaultParent){
			$arr = arr::make($mvc->defaultParent, TRUE);
			expect(array_key_exists('title', $arr));
			expect(array_key_exists('systemId', $arr));
			$new = FALSE;
			
			// Ако има дефолт параметър с това систем ид, и името му е различно обновяваме го
			if($rec = $mvc->fetch("#{$mvc->systemIdFieldName} = '{$arr['systemId']}'")){
				if($rec->{$mvc->nameField} != $arr['title']){
					$rec->{$mvc->nameField} = $arr['title'];
				} else {
					$rec = NULL;
				}
				
			// Иначе създаваме нов
			} else {
				$rec = new stdClass();
				$rec->{$mvc->systemIdFieldName} = $arr['systemId'];
				$rec->{$mvc->nameField} = $arr['title'];
				$new = TRUE;
			}
			
			if(isset($rec)){
				
				// Записваме дефолтния запис
				$defaultId = $mvc->save($rec, NULL, 'REPLACE');
				
				// Ако сме добавили нов, правим всички записи които нямат бащи да наследяват този
				if($new === TRUE){
					$query = $mvc->getQuery();
					$query->where("#{$mvc->parentFieldName} IS NULL AND #id != {$defaultId}");
				
					while($dRec = $query->fetch()){
						$dRec->{$mvc->parentFieldName} = $defaultId;
						$mvc->save($dRec, $mvc->parentFieldName);
					}
				}
			}
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$options = $mvc->getParentOptions($data->form->rec);
		if(count($options)){
			$data->form->setOptions($mvc->parentFieldName, $options);
		} else {
			$data->form->setReadOnly($mvc->parentFieldName);
		}
		
		// Ако имаме дефолтно име за баща, то полето за баща трябва да е задължително
		if($defaultParent = $mvc->getDefaultParentId()){
			$data->form->setField($mvc->parentFieldName, 'mandatory');
			$data->form->setDefault($mvc->parentFieldName, $defaultParent);
		}
	}
	
	
	/**
	 * Връща възможните опции за избор на бащи
	 * 
	 * @param stdClass $rec
	 * @return $options
	 */
	protected function getParentOptions($rec)
	{
		$where = '';
		if($rec->id){
			$where = "#id != {$rec->id}";
		}
		
		if($this->getField('state', FALSE)){
			$where .= "#state != 'rejected'";
		}
		
		// При редакция оставяме само тези опции, в чиите бащи не участва текущия обект
		$options = $this->makeArray4Select($this->nameField, $where);
		if(count($options) && isset($rec->id)){
			foreach ($options as $id => $title){
				$this->traverseTree($id, $rec->id, $notAllowed);
				if(count($notAllowed) && in_array($id, $notAllowed)){
					unset($options[$id]);
				}
			}
		}
		
		return $options;
	}
	
	
	/**
	 * Търси в дърво, дали даден обект не е баща на някой от бащите на друг обект
	 * 
	 * @param int $objectId - ид на текущия обект
	 * @param int $needle - ид на обекта който търсим
	 * @param array $notAllowed - списък със забранените обекти
	 * @param array $path
	 * @return void
	 */
	private function traverseTree($objectId, $needle, &$notAllowed, $path = array())
	{
		// Добавяме текущия продукт
		$path[$objectId] = $objectId;
		
		// Ако стигнем до началния, прекратяваме рекурсията
		if($objectId == $needle){
			foreach($path as $p){
				
				// За всеки продукт в пътя до намерения ние го
				// добавяме в масива notAllowed, ако той, вече не е там
				$notAllowed[$p] = $p;
			}
			
			return;
		}
		
		// Намираме бащата на този обект и за него продължаваме рекурсивно
		if($parentId = static::fetchField($objectId, $this->parentFieldName)){
			self::traverseTree($parentId, $needle, $notAllowed, $path);
		}
	}
	
	
	/**
	 * Връща Пълното заглавие на обекта с бащите му преди името му
	 */
	public static function getFullTitle($id)
	{
		$me = cls::get(get_called_class());
		$parent = $me->fetchField($id, $me->parentFieldName);
		$title = $me->getVerbal($id, $me->nameField);
		
		while($parent && ($pRec = self::fetch($parent, "{$me->parentFieldName},{$me->nameField}"))) {
			$title = $pRec->{$me->nameField} . ' » ' . $title;
			$parent = $pRec->{$me->parentFieldName};
		}
		
		return $title;
	}
	
	
	/**
	 * Функция, която връща подготвен масив за СЕЛЕКТ от елементи (ид, поле)
	 * на $class отговарящи на условието where
	 */
	public function makeArray4Select_($fields = NULL, $where = "", $index = 'id')
	{
		$options = array();
		
		$query = $this->getQuery();
		$query->show("parentId, {$this->nameField}");
		while($rec = $query->fetch()){
			$options[$rec->id] = static::getFullTitle($rec->id, $title);
		}
		
		return $options;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'edit' || $action == 'delete') && isset($rec->{$mvc->systemIdFieldName})){
			$requiredRoles = 'no_one';
		}
	}
	
	
	/**
	 * Кой е дефолтния баща на всички обекти модела
	 */
	public function getDefaultParentId()
	{
		// Ако има данни за дефолт параметър
		if($this->defaultParent){
			$arr = arr::make($this->defaultParent, TRUE);
			
			return $this->fetchField("#{$this->systemIdFieldName} = '{$arr['systemId']}'", 'id');
		}
		
		return FALSE;
	}
	
	
	/**
	 * Връща бащата на обекта като свойство а подадения обект стойност
	 * 
	 * @param int $id
	 * @return array |boolean
	 */
	public static function getFeature($id)
	{
		$me = cls::get(get_called_class());
		
		if($rec = static::fetch($id)){
			if($rec->parentId){
				if(static::fetchField($rec->parentId, 'makeDescendantsFeatures') == 'yes'){
					
					$feature = static::getVerbal($rec->parentId, $me->nameField);
					$featureValue = static::getVerbal($rec->id, $me->nameField);
					
					return array($feature => $featureValue);
				}
			}
		}
		
		return FALSE;
	}
}