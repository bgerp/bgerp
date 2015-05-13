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
class core_TreeObject extends core_Manager
{
	

	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->parentFieldName, 'parentId');
		setIfNot($mvc->nameField, 'name');
		
		// Създаваме поле за име, ако няма такова
		if(!$mvc->getField($mvc->nameField, FALSE)){
			$mvc->FLD($mvc->nameField, "varchar(64)", 'caption=Наименование, mandatory');
		}
		
		// Поставяме поле за избор на баща, ако вече не съществува такова
		if(!$mvc->getField($mvc->parentFieldName, FALSE)){
			$mvc->FLD($mvc->parentFieldName, "key(mvc={$mvc->className},allowEmpty,select={$mvc->nameField})", 'caption=В състава на');
		}
		$mvc->setField($mvc->parentFieldName, 'silent');
		
		// Дали наследниците на обекта да са счетоводни пера
		if(!$mvc->getField('makeDescendantsFeatures', FALSE)){
			$mvc->FLD('makeDescendantsFeatures', "enum(yes=Да,no=Не)", 'caption=Наследниците да бъдат ли счетоводни признаци?->Избор,notNull,value=yes');
		}
		
		$mvc->setField($mvc->nameField, 'tdClass=leafName');
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
		
		$data->form->setDefault('makeDescendantsFeatures', 'yes');
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
	 * Връща наследниците на корена
	 * 
	 * @param int $id - ид на запис
	 * @return array $res - масив със записите на наследниците
	 */
	protected function getDescendents($id)
	{
		$query = $this->getQuery();
		$query->where("#{$this->parentFieldName} = {$id}");
		$query->show("id,{$this->nameField}");
		
		return $query->fetchAll();
	}
	
	
	/**
	 * Функция, която връща подготвен масив за СЕЛЕКТ от елементи (ид, поле)
	 * на $class отговарящи на условието where
	 */
	public function makeArray4Select_($fields = NULL, $where = "", $index = 'id')
	{
		$options = array();
		
		$query = $this->getQuery();
		$query->show("{$this->parentFieldName}, {$this->nameField}");
		
		while($rec = $query->fetch()){
			$options[$rec->id] = static::getFullTitle($rec->id, $title);
		}
		
		usort($options, array($this, "sortOptions"));
	
		return $options;
	}
	
	
	/**
	 * Подреждане на опциите
	 */
	private function sortOptions($a, $b)
	{
		if($a == $b) return 0;
	
		return (strnatcasecmp($a, $b) < 0) ? -1 : 1;
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
			if($rec->{$me->parentFieldName}){
				if(static::fetchField($rec->{$me->parentFieldName}, 'makeDescendantsFeatures') == 'yes'){
					
					$feature = static::getVerbal($rec->parentId, $me->nameField);
					$featureValue = static::getVerbal($rec->id, $me->nameField);
					
					return array($feature => $featureValue);
				}
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * След извличане на записите от базата данни
	 */
	public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
	{
		if(!count($data->recs)) return;
		
		// За всички записи
		foreach ($data->recs as &$rec){
			
			// Взимаме баща им
			$parentId = $rec->{$mvc->parentFieldName};
			
			// Проверяваме дали е сетнат в $data->recs, ако не е го извличаме, продължаваме докато
			// всички бащи присъстват в $data->recs. Правим това за да се подсигурим че при
			// вече филтрирани записи по някакъв признак, да не се показват само намерените 
			// редове, а и техните бащи
			while($parentId){
				if(!isset($data->recs[$parentId])){
					$parentRec = $mvc->fetch($parentId);
					$parentRec->show = TRUE;
					$rec->show = TRUE;
					$data->recs[$parentId] = $parentRec;
					$parentId = $parentRec->{$mvc->parentFieldName};
				} else {
					$parentId = NULL;
				}
			}
		}
		
		$tree = array();
		foreach ($data->recs as $br){
			$tree[$br->parentId][] = $br;
		}
		
		$tree = $mvc->createTree($tree, $tree[NULL]);
		$data->recs = $mvc->flattenTree($tree);

        $data->listTableClass = 'treeView';
	}
	
	
	/**
	 * Създава дърво от записите
	 * 
	 * @param array $list - масив
	 * @param int $parent - ид на бащата бащата (NULL ако няма)
	 * @return array $tree - записите в дървовидна структура
	 */
	private function createTree(&$list, $parent, $round = -1)
	{
		$round++;
		$tree = array();
	    
	    foreach ($parent as $k => $l){
	    	if(is_null($l->parentId)){
	    		$round = 0;
	    	}
	        if(isset($list[$l->id])){
	            $l->children = $this->createTree($list, $list[$l->id], $round);
	        }
	        $l->_level = $round;
	        $tree[] = $l;
	    } 
	    
	    return $tree;
	}
	
	
	/**
	 * Дървовидния масив
	 * 
	 * @param array $array
	 * @return array - сортираните записи
	 */
	private function flattenTree($array)
	{
		$return = array();
		
		foreach ($array as $key => $value) {
			$return[$value->id] = $value;
			if(count($value->children)){
				$return = $return + $this->flattenTree($value->children);
			}
			$value->_childrenCount = count($value->children);
			unset($value->children);
		}
		
		return $return;
	}

	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-list'])){
			$row->ROW_ATTR['data-parentid'] .= $rec->parentId;
			$row->ROW_ATTR['data-id']       .= $rec->id;
			$row->ROW_ATTR['class']    .= ' treeLevel' . $rec->_level;
			
			if($rec->_childrenCount > 0){
				
				$plusIcon = sbf('img/16/toggle-expand.png', '');
				$minusIcon = sbf('img/16/toggle2.png', '');
				$plus = "<img class = 'toggleBtn plus' src='{$plusIcon}' width='13' height='13'/>";
				$minus = "<img class = 'toggleBtn minus' src='{$minusIcon}' width='13' height='13'/>";
				
				$row->{$mvc->nameField} = " {$plus}{$minus}" . $row->{$mvc->nameField};
			}
			
			if($mvc->haveRightFor('add')){
				$url = array($mvc, 'add', 'parentId' => $rec->id, 'ret_url' => TRUE);
				$img = ht::createElement('img', array('src' => sbf('img/16/add.png', ''), 'style' => 'width: 13px; padding: 0px 2px;'));
				$row->_addBtn = ht::createLink($img, $url, FALSE, 'title=Добавяне на нов поделемент');
			}
			
			if($rec->show === TRUE){
				$row->ROW_ATTR['class'] .= " searchResult";
			}
		}
	}
	
	
	/**
	 * Извиква се след подготовката на колоните ($data->listFields)
	 */
	protected static function on_AfterPrepareListFields($mvc, $data)
	{
		arr::placeInAssocArray($data->listFields, array('_addBtn' => ' '), NULL, $mvc->nameField);
	}
	
	
	/**
	 * След рендиране на лист таблицата
	 */
	public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
	{
		jquery_Jquery::run($tpl, "treeViewAction();");
	}
	
	
	/**
	 * Връща масив от вида `< име на баща > => < име на наследник >`, ако бащата на обекта
	 * има чекнато децата му да са свойства. За да е един обект свойство трябва или да има баща
	 * и децата му да са свойства или да няма баща
	 *
	 * @param array $ids - масив с ид-та на обекти от този клас
	 * @return array - масив със свойства и стойностти
	 */
	public static function getFeaturesArray($ids)
	{
		$self = cls::get(get_called_class());
		$features = array();
	
		if(!count($ids)) return $features;
	
		foreach ($ids as $id){
			$rec = $self->fetch($id);
			
			// Намираме името на обекта
			$nameVerbal = $self->getVerbal($rec->id, $self->nameField);
			$keyVerbal = $nameVerbal;
			
			// Ако има баща и е указано децата му да са свойства
			if(!empty($rec->parentId)){
				if($self->fetchField($rec->parentId, 'makeDescendantsFeatures') == 'yes'){
					$keyVerbal = $self->getVerbal($rec->parentId, $self->nameField);
				} else {
					
					// Ако не трябва да са наследници пропускаме
					continue;
				}
			}
			
			// задаваме свойството
			$features[$keyVerbal] = $nameVerbal;
		}
		
		return $features;
	}
	
	
	/**
	 * Подготвя навигацията по страници
	 */
	function prepareListPager_(&$data)
	{
		// Предефинираме метода, за да не заработи страницирането на данните
		// В $data->recs ни трябват всички записи, за да можем да подготвим дървовидната структура
		// При зареждане ще се показват само записите без бащи (корените) а децата 
		// им ще се показват с JavaScript.
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec)){
			if($mvc->fetch("#{$mvc->parentFieldName} = {$rec->id}")){
				$requiredRoles = 'no_one';
			}
		}
	}
}
