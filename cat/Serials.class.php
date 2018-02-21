<?php 



/**
 * Серийни номера на артикулите
 * 
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Serials extends core_Manager
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'label_Serials';
	
	
	/**
	 * Заглавие на модела
	 */
	public $title = 'Серийни номера';
	
	
	/**
	 * Кой има право да пише?
	 */
	public $canWrite = 'no_one';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'cat, admin, ceo';
	
	
	/**
	 * Кой има право да го изтрие?
	 */
	public $canDelete = 'no_one';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'cat_Wrapper, plg_Created, plg_Sorting';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'serial, sourceObjectId=Източник, createdOn, createdBy';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	function description()
	{
		$this->FLD('serial', 'bigint', 'caption=Сериен №,mandatory');
		$this->FLD('sourceClassId', 'class(interface=label_SequenceIntf,select=title)', 'caption=Източник->Клас');
		$this->FLD('sourceObjectId', 'int', 'caption=Източник->Обект');
		
		$this->setDbUnique('serial');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($rec->sourceClassId) && isset($rec->sourceObjectId)){
			$SourceClass = cls::get($rec->sourceClassId);
			$row->sourceObjectId = (cls::haveInterface('doc_DocumentIntf', $SourceClass)) ? $SourceClass->getLink($rec->sourceObjectId, 0) : $SourceClass->getTitleById($rec->sourceObjectId);
		}
		
		$row->serial = core_Type::getByName('varchar')->toVerbal(str_pad($rec->serial, 13, '0', STR_PAD_LEFT));
	}
	
	
	/**
	 * Връща сериен номер според източника, и го регистрира в модела
	 * 
	 * @param string $sourceClassId  - клас
	 * @param string $sourceObjectId - ид на обект
	 * @return int $serial
	 */
	public static function generateSerial($sourceClassId = NULL, $sourceObjectId = NULL)
	{
		$serial = self::getRand();
		self::assignSerial($serial, $sourceClassId, $sourceObjectId);
		
		return $serial;
	}
	
	
	/**
	 * Регистрира дадения сериен номер, към обекта (ако има)
	 * 
	 * @param string $serial            - сериен номер
	 * @param mixed $sourceClassId      - клас на обекта
	 * @param int|NULL $sourceObjectId  - ид на обекта
	 */
	public static function assignSerial($serial, $sourceClassId = NULL, $sourceObjectId = NULL)
	{
		expect((empty($sourceClassId) && empty($sourceObjectId)) || (!empty($sourceClassId) && !empty($sourceObjectId)));
		if(isset($sourceClassId)){
			expect(cls::haveInterface('label_SequenceIntf', $sourceClassId));
			$sourceClassId = cls::get($sourceClassId)->getClassId();
		}
		
		$rec = (object)array('serial' => $serial, 'sourceClassId' => $sourceClassId, 'sourceObjectId' => $sourceObjectId);
		
		return self::save($rec);
	}
	
	
	/**
	 * Връща рандом НЕ-записан сериен номер
	 * 
	 * @return int $serial
	 */
	public static function getRand()
	{
		$serial = str::getRand('#############');
		while(self::fetchField(array("#serial = [#1#]", $serial)) || cat_products_Packagings::fetchField(array("#eanCode = [#1#]", $serial))){
			$serial = str::getRand('#############');
		}
		
		return $serial;
	}
	
	
	/**
	 * Запис отговарящ на серийния номер
	 * 
	 * @param int $serial
	 * @return stdClass|NULL $res
	 */
	public static function getRecBySerial($serial)
	{
		$res = self::fetch(array("#serial = '[#1#]'", $serial));
		
		return (!empty($res)) ? $res : NULL;
	}
	
	
	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->showFields = 'serial,sourceClassId';
		$data->listFilter->setFieldTypeParams('sourceClassId', 'allowEmpty');
		$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->input();
	
		if($fRec = $data->listFilter->rec){
			if(!empty($fRec->serial)){
				$data->query->where(array("#serial LIKE '%[#1#]%'", $fRec->serial));
			}
			
			if(!empty($fRec->sourceClassId)){
				$data->query->where("#sourceClassId = '{$fRec->sourceClassId}'");
			}
		}
	}
}