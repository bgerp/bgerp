<?php 



/**
 * Серийни номера 
 * 
 * @category  bgerp
 * @package   label
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Serials extends core_Manager
{
	
	
	/**
	 * Кой е максималния сериен номер
	 */
	const LABEL_MAX_SERIAL = 999999999999;
	
	
	/**
	 * Заглавие на модела
	 */
	public $title = 'Серийни номера';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'no_one';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'no_one';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'label, admin, ceo';
	
	
	/**
	 * Кой има право да го изтрие?
	 */
	public $canDelete = 'no_one';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'label_Wrapper, plg_Created, plg_Sorting';
	
	
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
		$this->FLD('sourceClassId', 'class(interface=label_SequenceIntf)', 'caption=Източник->Клас');
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
			$row->sourceObjectId = (method_exists($SourceClass, 'getLink')) ? $SourceClass->getLink($rec->sourceObjectId, 0) : $SourceClass->getTitleById($rec->sourceObjectId);
		}
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
		self::asignSerial($serial, $sourceClassId, $sourceObjectId);
		
		return $serial;
	}
	
	
	/**
	 * Регистрира дадения сериен номер, към обекта (ако има)
	 * 
	 * @param string $serial            - сериен номер
	 * @param mixed $sourceClassId      - клас на обекта
	 * @param int|NULL $sourceObjectId  - ид на обекта
	 */
	public static function asignSerial($serial, $sourceClassId = NULL, $sourceObjectId = NULL)
	{
		expect((empty($sourceClassId) && empty($sourceObjectId)) || (!empty($sourceClassId) && !empty($sourceObjectId)));
		if(isset($sourceClassId)){
			expect(cls::haveInterface('label_SequenceIntf', $sourceClassId));
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
		$serial = rand(1, self::LABEL_MAX_SERIAL);
		while(self::fetchField(array("#serial = [#1#]", $serial))){
			$serial = rand(1, self::LABEL_MAX_SERIAL);
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
}