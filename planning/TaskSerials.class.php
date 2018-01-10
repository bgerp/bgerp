<?php



/**
 * Клас 'planning_TaskSerials' - Серийни номера по производствените операции
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_TaskSerials extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Серийни номера по производствените операции';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'debug';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canWrite = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,planning_Wrapper';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'serial=С. номер,taskId,jobId=Задание,labelNo=Етикети->№,domain=Етикети->Домейн,packagingId=Етикети->Опаковка,quantityInPack=Етикети->К-во,createdOn,createdBy';

    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('serial', 'bigint', 'caption=Брояч,mandatory');
		$this->FLD('quantityInPack', 'double(smartRound)', 'caption=К-во в опаковка,mandatory');
		$this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Опаковка,mandatory');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory');
		$this->FLD('taskId', 'key(mvc=planning_Tasks,select=title)', 'caption=Операция,mandatory');
		$this->FLD('labelNo', 'int', 'caption=Номер на етикета,mandatory');
		$this->FLD('domain', 'enum(auto,labels)', 'caption=Домейн,mandatory,notNull,value=auto');
		
		$this->setDbUnique('serial');
		$this->setDbUnique('taskId,labelNo,domain');
	}
	
	
	/**
	 * Връща следващия сериен номер
	 * 
	 * @return string $serial
	 */
	public static function getNextSerial()
	{
		// Намираме последния въведен код
		$query = static::getQuery();
		$query->XPR('maxSerial', 'int', 'MAX(#serial)');
		$startCounter = $query->fetch()->maxSerial;
		if(!$startCounter){
			$startCounter = core_Packs::getConfigValue('planning', 'PLANNING_TASK_SERIAL_COUNTER');
		};
		$serial = $startCounter;
		
		// Инкрементираме кода, докато достигнем свободен код
		$serial++;
		while(self::fetchField("#serial = '{$serial}'")){
			$serial++;
		}
		
		return $serial;
	}
	
	
	/**
	 * Връща следващия сериен номер, автоинкрементиран
	 *
	 * @param int $taskId - ид на операция за прозиводство
	 * @return string $serial - сериен номер
	 */
	public static function forceAutoNumber($rec)
	{
		$query = self::getQuery();
		$query->where("#domain = 'auto'");
		$query->XPR('maxLabelNo', 'int', 'MAX(#labelNo)');
		$labelNo = $query->fetch()->maxLabelNo;
		$labelNo++;
		
		$tInfo = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, 'production', $rec->fixedAsset);
		
		$rec = (object)array('taskId'         => $rec->taskId, 
							 'labelNo'        => $labelNo,
							 'domain'         => 'auto',
							 'productId'      => $tInfo->productId,
							 'packagingId'    => $tInfo->packagingId,
							 'quantityInPack' => $tInfo->quantityInPack,
							 'serial'         => self::getNextSerial());
		
		self::save($rec);
		
		return $rec->serial;
	}
	
	
	/**
	 * Изпълнява се след подготвянето на формата за филтриране
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
	{
		$data->query->orderBy('serial', 'DESC');
	}
	
	
	/**
	 * Форсира сериен номер
	 * 
	 * @param int $taskId          - ид 
	 * @param number $labelNo      - номер на етикета
	 * @return int - намерения сериен номер
	 */
	public static function force($taskId, $labelNo = 0)
	{
		if($rec = static::fetch(array("#taskId = [#1#] AND #labelNo = '[#2#]' AND #domain = 'labels'", $taskId, $labelNo))){
			return $rec->serial;
		}
		$tInfo = planning_Tasks::fetch($taskId);
		
		$rec = (object)array('taskId'         => $taskId, 
						     'labelNo'        => $labelNo, 
							 'domain'         => 'labels',
							 'productId'      => $tInfo->productId,
							 'packagingId'    => $tInfo->packagingId,
							 'quantityInPack' => $tInfo->quantityInPack,
							 'serial'         => static::getNextSerial());
		
		static::save($rec);
		
		return $rec->serial;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->taskId = planning_Tasks::getHyperlink($rec->taskId, TRUE);
		$row->ROW_ATTR['class'] = 'state-active';
		
		if($originId = planning_Tasks::fetchField($rec->taskId, 'originId')){
			$row->jobId = doc_Containers::getDocument($originId)->getLink();
		}
		
		if(isset($rec->productId)){
			$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
		}
	}
	

	/**
	 * Проверява дали даден сериен номер е допустим
	 * Допустими са само серийни номера генерирани от системата (автоматично или чрез разпечатване
	 * на етикети от операцията). Трябва серийния номер да отговаря на Артикула.
	 * Ако номера е за произведен артикул, той трябва да е генериран от същата операция
	 * Ако влагаме то номера трябва да е генериран от операция към същото задание
	 * 
	 * 
	 * @param bigint $serial       - сериен номер
	 * @param int $productId       - ид на артикул, на който добавяме номера
	 * @param int $taskId          - операция към която се опитваме да добавим номер в прогреса
	 * @param production|input $type  - дали е за производим артикул или е за вложим/отпадък
	 * @param int|NULL $id         - ид
	 * @return FALSE|string $error - FALSE ако номера е допустим, или текст с какъв е проблема
	 */
	public static function isSerialInvalid($serial, $productId, $taskId, $type, $id = NULL)
	{
		// Трябва да има сериен номер
		expect($serial);
		$error = '';
		
		// Проверяваме имали въобще такъв сериен номер в системата
		$serialRec = self::fetch(array("#serial = '[#1#]'", $serial));
		
		// Ако няма връщаме грешката
		if(!$serialRec){
			$error = 'Несъществуващ сериен номер';
		} else {
			
			// Ако има сериен номер, проверяваме дали е за същия артикул
			if($serialRec->productId != $productId){
				
				// Ако не е връщаме грешката
				$error = "Въведения сериен номер е за друг артикул";
				$error .= "|* <b>" . cat_Products::getHyperlink($serialRec->productId, TRUE) . "</b>";
			} else {
				// Ако серийния номер е за същия артикул
				
				// И произвеждаме
				if($type == 'production'){
					
					// То серийния номер на производимия артикул трябва да е по същата операция
					// Ако е по друга сетваме подходяща грешка
					if($serialRec->taskId != $taskId){
						$error = "Въведения сериен номер е по друга операция";
						$error .= "|* " . planning_Tasks::getLink($serialRec->taskId, 0);
					}
				} else {
					
					// Намираме заданията по които са породени операцията от номера и текущата операция
					$productTaskOriginId = planning_Tasks::fetchField($serialRec->taskId, 'originId');
					$taskOriginId = planning_Tasks::fetchField($taskId, 'originId');
					
					// Двете задачи трябва да са към едно и също задание
					// Не можем да влагаме заготовка която е произведена със операция по друго задание
					if($taskOriginId != $productTaskOriginId){
						$error = "Въведения сериен номер е по операция към  друго задание";
						$error .= "|* " . planning_Tasks::getLink($serialRec->taskId, 0);
					}
				}
			}
		}
		
		// Ако не е намерена грешка ще върнем FALSE
		if($error == ''){
			$error = FALSE;
		}
		
		// Връщаме резултата
		return $error;
	}
	
	
	/**
	 * Връща серийния номер като линк, ако е от друга операция
	 * 
	 * @param int $taskId                    - в коя операция ще се показва
	 * @param string $serial                 - серийния номер
	 * @return core_ET|string $serialVerbal  - серийния номер като линк, или вербалното му представяне
	 */
	public static function getLink($taskId, $serial)
	{
		$serialVerbal = core_Type::getByName('varchar(32)')->toVerbal($serial);
		$paddLength = planning_Setup::get('SERIAL_STRING_PAD');
		$serialVerbal = str_pad($serialVerbal, $paddLength, '0', STR_PAD_LEFT);
		if(Mode::isReadOnly()) return $serialVerbal;
		
		// Линк към прогреса филтриран по сериен номер
		if(planning_ProductionTaskDetails::haveRightFor('list')){
			$serialVerbal = ht::createLink($serialVerbal, array('planning_ProductionTaskDetails', 'list', 'search' => $serial), FALSE, "title=Към историята на серийния номер");
		}
		
		return $serialVerbal;
	}
}