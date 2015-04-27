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
		$query->where("#id = 1");
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
        $indent = 20 * $round;
		if(count($desc)){

			$tpl = new core_ET("<tr><td  data-id='{$id}' data-parentid='{$parentId}' style='padding-left: {$indent}px'>[#title#]</td></tr>");
			$tpl->replace($this->getVerbal($id, $this->nameField), 'title');
			//$tpl = new core_ET("<li>[#title#]<ul>[#DESC#]</ul></li><!--ET_END DESC-->");
			//bp($desc, $tpl);

			foreach ($desc as $d){
				$round2 = $round;
				$nTpl = $this->getListTpl($d->id, $round2, $id);
				$tpl->append($nTpl);
			}
            //bp($round);
		} else {
			$tpl = new core_ET("<tr><td data-id='{$id}' data-parentid = {$parentId} style='padding-left: {$indent}px'>[#LISTS#]</td></tr>");
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
	 * След подготовката на туулбара на списъчния изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		$data->toolbar->addBtn('Дърво', array($mvc, 'listTree'));
	}
}