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
		
		// Дали наследниците на обекта да са счетоводни пера
		if(!$mvc->getField('makeDescendantsFeatures', FALSE)){
			$mvc->FLD('makeDescendantsFeatures', "enum(no=Не,yes=Да)", 'caption=Наследниците дали да бъдат сч. признаци->Избор,notNull,value=yes');
		}
		
		// Поставяне на уникален индекс
		//$mvc->setDbUnique($mvc->nameField);
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
	 * Екшън за дървовидно разглеждане на обекта
	 */
	public function act_ListTree()
	{
		$this->requireRightFor('list');
		
		$query = $this->getQuery();
		$query->where("#parentId IS NULL");
		$query->show('id');
		$tpl = new core_ET("<table class='listTable treeView'>[#LISTS_BODY#]</table>");
		while($rec = $query->fetch()){
			$round = -1;
			$tpl->append($this->getListTpl($rec->id, $round, $rec->id), 'LISTS_BODY');
		}
		
		$this->renderWrapping($tpl);
        jquery_Jquery::run($tpl, "treeViewAction();");

		return $tpl;
	}
	
	
	/**
	 * Връща вложен списък от наследниците на даден обект
	 * 
	 * @param int $id - ид на корен
	 * @return core_ET $tpl - шаблона
	 */
	protected function getListTpl($id, &$round, $parentId)
	{
        $round++;

		if($id == $parentId){
			$parentId = NULL;
		}
		
		$desc = $this->getDescendents($id);
        $indent = 18 * $round;
		if(count($desc)){
            $plusIcon = sbf('img/16/toggle-expand.png', '');
            $minusIcon = sbf('img/16/toggle2.png', '');
            $plus = "<span><img class = 'hidden' src='{$plusIcon}' width='13' height='13'/></span>";
            $minus = "<span><img  src='{$minusIcon}' width='13' height='13'/></span>";


            $tpl = new core_ET("<tr><td  data-id='{$id}' data-parentid='{$parentId}' style='text-indent: {$indent}px'> {$plus}{$minus} [#title#]</td></tr>");
			$tpl->replace($this->getVerbal($id, $this->nameField), 'title');

			foreach ($desc as $d){
				$round2 = $round;
				$nTpl = $this->getListTpl($d->id, $round2, $id);
				$tpl->append($nTpl);
			}
		} else {
			$tpl = new core_ET("<tr><td data-id='{$id}' data-parentid = {$parentId} style='text-indent: {$indent}px'>[#LISTS#]</td></tr>");
			$title = $this->getVerbal($id, $this->nameField);
			$tpl->replace($title, 'LISTS');
		}
		
		$tpl->removeBlocks();
		$tpl->removePlaces();
		
		return $tpl;
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
		$query->show("parentId, {$this->nameField}");
		while($rec = $query->fetch()){
			$options[$rec->id] = static::getFullTitle($rec->id, $title);
		}
		
		return $options;
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
	
	
	/**
	 * След извличане на записите от базата данни
	 */
	public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
	{
		if(!count($data->recs)) return;
		
		$tree = array();
		foreach ($data->recs as $br){
			$tree[$br->parentId][] = $br;
		}
		
		$tree = $mvc->createTree($tree, $tree[NULL]);
		$data->recs = $mvc->flattenTree($tree);
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
			$row->ROW_ATTR['data-level']    .= $rec->_level;
			
			if($rec->_childrenCount > 0){
				
				$plusIcon = sbf('img/16/toggle-expand.png', '');
				$minusIcon = sbf('img/16/toggle2.png', '');
				$plus = "<span><img class = 'hidden' src='{$plusIcon}' width='13' height='13'/></span>";
				$minus = "<span><img  src='{$minusIcon}' width='13' height='13'/></span>";
				
				$row->{$mvc->nameField} .= " {$plus}{$minus}";
			}
		}
	}
	
	
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		$data->toolbar->addFnBtn('Затвори всички', NULL, 'class=closeTreeBtn');
		$data->toolbar->addFnBtn('Отвори всички', NULL, 'class=openTreeBtn');
	}
	
	
	/**
	 * След рендиране на лист таблицата
	 */
	public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
	{
		jquery_Jquery::run($tpl, "treeViewAction();");
	}
}