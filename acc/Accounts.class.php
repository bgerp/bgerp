<?php

/**
 * Мениджър на счетоводни сметки
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class acc_Accounts extends core_Manager
{
	/**
	 *  @todo Чака за документация...
	 */
	var $menuPage = 'Счетоводство';


	/**
	 *  @todo Чака за документация...
	 */
	var $title = 'Сметкоплан';


	/**
	 *  @todo Чака за документация...
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, acc_Wrapper, Lists=acc_Lists';


	/**
	 * Права
	 */
	var $canRead = 'admin,acc,broker,designer';


	/**
	 *  @todo Чака за документация...
	 */
	var $canEdit = 'admin,acc';


	/**
	 *  @todo Чака за документация...
	 */
	var $canAdd = 'admin,acc,broker,designer';


	/**
	 *  @todo Чака за документация...
	 */
	var $canView = 'admin,acc,broker,designer';


	/**
	 *  @todo Чака за документация...
	 */
	var $canDelete = 'admin,acc';


	/**
	 *  @todo Чака за документация...
	 */
	var $listItemsPerPage = 300;


	/**
	 *  @todo Чака за документация...
	 */
	var $listFields = 'num,title,type,lists=Номенклатури,lastUseOn,state,tools=Пулт';


	/**
	 *  @todo Чака за документация...
	 */
	var $rowToolsField = 'tools';


	/**
	 * @var acc_Lists
	 */
	var $Lists;

	private static $idToNumMap;
	private static $numToIdMap;


	/**
	 *  Описание на модела (таблицата)
	 */
	function description()
	{
		$this->FLD('num', 'varchar(5, size=5)', "caption=Номер,mandatory,remember=info, export");
		$this->FLD('title', 'varchar', 'caption=Сметка,mandatory,remember=info, export');
		$this->FLD('type', 'enum(,dynamic=Смесена,active=Активна,passive=Пасивна,transit=Корекционна)',
        'caption=Тип,remember,mandatory, export');
		$this->FLD('strategy', 'enum(,FIFO,LIFO,MAP)',
        'caption=Стратегия, export');
		$this->FLD('groupId1', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
        'caption=Разбивка по номенклатури->Ном. 1,remember, export');
		$this->FLD('groupId2', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
        'caption=Разбивка по номенклатури->Ном. 2,remember, export');
		$this->FLD('groupId3', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
        'caption=Разбивка по номенклатури->Ном. 3,remember, export');
		$this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=hidden');

		$this->XPR('isSynthetic', 'int', 'CHAR_LENGTH(#num) < 3', 'column=none');

		$this->setDbUnique('num');
	}


	/**
	 *  @todo Чака за документация...
	 */
	function on_CalcIsSynthetic($mvc, &$rec) {
		$rec->isSynthetic = (strlen($rec->num) < 3);
	}


	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * Забранява изтриването на вече използвани сметки
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass|NULL $rec
	 * @param int|NULL $userId
	 */
	function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if ($rec->id && $action == 'delete') {
			$rec = $mvc->fetch($rec->id);

			if ($rec->lastUseOn) {
				// Използвана сметка - забранено изтриване
				$requiredRoles = 'no_one';
			}
		}
	}


	/**
	 * Преди извличане на записите от БД
	 *
	 * @param core_Mvc $mvc
	 * @param StdClass $res
	 * @param StdClass $data
	 */
	function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		// Сортиране на записите по num
		$data->query->orderBy('num');
	}


	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;

		if (!empty($form->rec->id)) {
			$rec = $form->rec;
			expect($rec &&
			is_object($rec) &&
			array_key_exists('lastUseOn', (array)$rec)
			);

			if ($rec->lastUseOn) {
				$form->setReadOnly('groupId1');
				$form->setReadOnly('groupId2');
				$form->setReadOnly('groupId3');
			}
		}
	}


	/**
	 *  @todo Чака за документация...
	 */
	function isUniquenum($rec)
	{
		$preCond = '1 = 1';

		if (!empty($rec->id)) {
			$preCond = "#id != {$rec->id}";
		}
		$result = !($this->fetch(array("#num = '[#1#]' AND {$preCond}", $rec->num)));

		return $result;
	}


	/**
	 *  Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	function on_AfterInputEditForm($mvc, &$form)
	{
		if (empty($form->rec->num)) {
			return;
		}

		// Изчисление на FNC поле "isSynthetic"
		$this->on_CalcIsSynthetic($mvc, $form->rec);

		if (!$this->isUniquenum($form->rec)) {
			$form->setError('num', 'Съществува сметка с този номер');
		}

		// Определяне на избраните номенклатури.
		$groupFields = array();

		foreach (range(1,3) as $i) {
			if (!empty($form->rec->{"groupId{$i}"})) {
				$groupFields[] = "groupId{$i}";
			}
		}

		if ($form->rec->isSynthetic) {
			//
			// Синтетична сметка
			//

			// Валидация: сметките с тип "синтетична" НЕ допускат задаване на номенклатури;
			// всички останали сметки допускат задаване на номенклатури

			if (!empty($groupFields)) {
				$form->setError(implode(',', $groupFields),
                "Не се допуска задаването на номенклатури за синтетични сметки");
			}
		} else {
			//
			// Аналитична сметка
			//

			// Колко от избраните номенклатури имат размерност?
			$nDimensions = 0;

			foreach ($groupFields as $groupId) {
				$dimensional = $this->Lists->fetchField($form->rec->{$groupId}, 'dimensional');

				if ($dimensional == 'yes') {
					$nDimensions++;
				}

				if ($nDimensions > 1) {
					break;
				}
			}

			// Валидация: Аналитична сметка може да има най-много една оразмерима номенклатура.
			//            Ако има такава, с/ката е "оразмерима"; ако няма - "неоразмерима"

			if ($nDimensions > 1) {
				$form->setError(implode(',', $groupFields),
                "Допуска се най-много една номенклатура с размерност");
			}
		}

		// Валидация: Стратегия (LIFO, FIFO, MAP) не се допуска за "неоразмерими" сметки.

		if (!empty($form->rec->strategy) && empty($nDimensions)) {
			$form->setError('strategy',
            "Стратегия се допуска само ако поне една от номенклатурите има размерност");
		}
	}


	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		if($rec->state == 'active') {
			$row->ROW_ATTR .= new ET(' class="level-'. strlen($rec->num) . '"');
		}

		if($rec->groupId1) {
			$listRec = $mvc->Lists->fetch($rec->groupId1);
			$row->lists .= "<div class='acc-detail'><a href='" .
			toUrl(array('acc_Items', 'listId' => $rec->groupId1)) .
            "'>{$listRec->caption}</a></div>";
		}

		if($rec->groupId2) {
			$listRec = $mvc->Lists->fetch($rec->groupId2);
			$row->lists .= "<div class='acc-detail'><a href='" .
			toUrl(array('acc_Items', 'listId' => $rec->groupId2)) .
            "'>{$listRec->caption}</a></div>";
		}

		if($rec->groupId3) {
			$listRec = $mvc->Lists->fetch($rec->groupId3);
			$row->lists .= "<div class='acc-detail'><a href='" .
			toUrl(array('acc_Items', 'listId' => $rec->groupId3)) .
            "'>{$listRec->caption}</a></div>";
		}

		if($rec->type) {
			$row->type = "<div class='acc-detail'>" .
			$row->type . "</div>";
		}

		if($rec->strategy) {
			$row->type .= "<div class='acc-detail'>" .
			$mvc->getVerbal($rec, 'strategy') . "</div>";
		}
	}


	/**
	 *  @todo Чака за документация...
	 */
	function makeArray4Select($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
	{
		$query = $this->getQuery();

		$res = array();

		if (!$where) {
			$fields = 'id, num, title, isSynthetic';
			$query->show($fields);
		}

		$query->orderBy('#num');


		/**
		 * Структура за преброяване на листата на синтетичните с/ки. Използва се за премахване
		 * на синтетичните сметки, под които няма аналитични сметки.
		 */
		$leafCount = array();

		while ($rec = $query->fetch($where)) {
			$title = $this->getRecTitle($rec);

			if ($rec->isSynthetic) {
				$res[$rec->{$index}] = (object)array(
                    'title' => $title,
                    'group' => TRUE
				);
				$leafCount[$rec->num] = array(0, $rec->{$index});
			} else {
				$res[$rec->{$index}] = $title;

				for ($i = 0; $i < strlen($rec->num)-1; $i++) {
					$leafCount[substr($rec->num, 0, $i+1)][0]++;
				}
			}
		}


		/**
		 * Окастряне на сухите клони на дървото - клоните, които нямат листа.
		 */
		foreach ($leafCount as $num=>$d) {
			if ($d[0] == 0) {
				unset($res[$d[1]]);
			}
		}

		return $res;
	}


	/**
	 *  @todo Чака за документация...
	 */
	function getRecTitle($rec)
	{
		return $rec->num . '. ' . $rec->title;
	}


	/**
	 * Записи за инициализиране на таблицата
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $res
	 */
	function on_AfterSetupMvc($mvc, &$res)
	{
		$data = array(
		array(
                'num' => 1,
                'title' => 'СМЕТКИ ЗА КАПИТАЛИ'
                ),
                array(
                'num' => 10,
                'title' => 'Капитал'
                ),
                array(
                'num' => 101,
                'title' => 'Основен капитал'
                ),
                array(
                'num' => 102,
                'title' => 'Допълнителен (запасен) капитал'
                ),
                array(
                'num' => 107,
                'title' => 'Сметка на собственика'
                ),
                array(
                'num' => 11,
                'title' => 'Резерви'
                ),
                array(
                'num' => 111,
                'title' => 'Общи резерви'
                ),
                array(
                'num' => 112,
                'title' => 'Преоценъчни резерви'
                ),
                array(
                'num' => 12,
                'title' => 'Финансови резултати'
                ),
                array(
                'num' => 121,
                'title' => 'Непокрита загуба от минали години'
                ),
                array(
                'num' => 122,
                'title' => 'Неразпределена печалба от минали години'
                ),
                array(
                'num' => 123,
                'title' => 'Печалби и загуби от текущата година'
                ),
                array(
                'num' => 124,
                'title' => 'Резултат при несъстоятелност и ликвидация'
                ),
                array(
                'num' => 13,
                'title' => 'Финансирания'
                ),
                array(
                'num' => 131,
                'title' => 'Финансиране за дълготрайни активи'
                ),
                array(
                'num' => 132,
                'title' => 'Финансиране на текущата дейност'
                ),
                array(
                'num' => 15,
                'title' => 'Получени заеми'
                ),
                array(
                'num' => 151,
                'title' => 'Получени краткосрочни заеми'
                ),
                array(
                'num' => 152,
                'title' => 'Получени дългосрочни заеми'
                ),
                array(
                'num' => 153,
                'title' => 'Облигационни заеми'
                ),
                array(
                'num' => 159,
                'title' => 'Други заеми и дългове'
                ),
                array(
                'num' => 2,
                'title' => 'СМЕТКИ ЗА ДЪЛГОТРАЙНИ АКТИВИ'
                ),
                array(
                'num' => 20,
                'title' => 'Дълготрайни материални активи'
                ),
                array(
                'num' => 201,
                'title' => 'Земи, гори и трайни насаждения'
                ),
                array(
                'num' => 203,
                'title' => 'Сгради'
                ),
                array(
                'num' => 204,
                'title' => 'Машини, съоръжения и оборудване'
                ),
                array(
                'num' => 205,
                'title' => 'Транспортни средства'
                ),
                array(
                'num' => 206,
                'title' => 'Стопански инвентар'
                ),
                array(
                'num' => 207,
                'title' => 'Разходи за придобиване на ДМА'
                ),
                array(
                'num' => 208,
                'title' => 'Ликвидация на ДМА'
                ),
                array(
                'num' => 209,
                'title' => 'Други дълготрайни материални активи'
                ),
                array(
                'num' => 21,
                'title' => 'Дълготрайни нематериални активи'
                ),
                array(
                'num' => 212,
                'title' => 'Продукти от развойна дейност'
                ),
                array(
                'num' => 3,
                'title' => 'СМЕТКИ ЗА МАТЕРИАЛНИ ЗАПАСИ'
                ),
                array(
                'num' => 30,
                'title' => 'Материали, продукция и стоки'
                ),
                array(
                'num' => 301,
                'title' => 'Доставки'
                ),
                array(
                'num' => 302,
                'title' => 'Материали'
                ),
                array(
                'num' => 303,
                'title' => 'Продукция'
                ),
                array(
                'num' => 304,
                'title' => 'Стоки'
                ),
                array(
                'num' => 3201,
                'title' => 'Полимери'
                ),
                array(
                'num' => 4,
                'title' => 'Доставчици и свързани с тях сметки'
                ),
                array(
                'num' => 401,
                'title' => 'Доставчици'
                ),
                array(
                'num' => 402,
                'title' => 'Доставчици по аванси'
                ),
                array(
                'num' => 41,
                'title' => 'Клиенти и свързани с тях сметки'
                ),
                array(
                'num' => 411,
                'title' => 'Клиенти'
                ),
                array(
                'num' => 412,
                'title' => 'Клиенти по аванси'
                ),
                array(
                'num' => 45,
                'title' => 'Разчети с бюджета и с ведомства'
                ),
                array(
                'num' => 451,
                'title' => 'Разчети с общините'
                ),
                array(
                'num' => 452,
                'title' => 'Разчети за данък върху печалбата'
                ),
                array(
                'num' => 453,
                'title' => 'Разчети за данък върху добавената стойност'
                ),
                array(
                'num' => 46,
                'title' => 'Разчети с осигурители'
                ),
                array(
                'num' => 461,
                'title' => 'Разчети с Националния осигурителен институт'
                ),
                array(
                'num' => 462,
                'title' => 'Разчети за доброволно социално осигуряване'
                ),
                array(
                'num' => 463,
                'title' => 'Разчети за здравно осигуряване'
                ),
                array(
                'num' => 464,
                'title' => 'Разчети за еднократни помощи и детски надбавки'
                ),
                array(
                'num' => 469,
                'title' => 'Други разчети с осигурители'
                ),
                array(
                'num' => 49,
                'title' => 'Разни дебитори и кредитори'
                ),
                array(
                'num' => 491,
                'title' => 'Доверители'
                ),
                array(
                'num' => 492,
                'title' => 'Разчети за гаранции'
                ),
                array(
                'num' => 493,
                'title' => 'Разчети със собственици'
                ),
                array(
                'num' => 494,
                'title' => 'Разчети по застраховане'
                ),
                array(
                'num' => 495,
                'title' => 'Разчети по лихви'
                ),
                array(
                'num' => 5,
                'title' => 'СМЕТКИ ЗА ФИНАНСОВИ СРЕДСТВА'
                ),
                array(
                'num' => 501,
                'title' => 'Каси'
                ),
                array(
                'num' => 502,
                'title' => 'Разплащателни сметки'
                ),
                array(
                'num' => 6,
                'title' => 'СМЕТКИ ЗА РАЗХОДИ'
                ),
                array(
                'num' => 7,
                'title' => 'СМЕТКИ ЗА ПРИХОДИ'
                ),
                array(
                'num' => 9,
                'title' => 'ЗАДБАЛАНСОВИ СМЕТКИ'
                )
                );

                if(!$mvc->fetch("1=1")) {

                	$nAffected = 0;

                	foreach ($data as $rec) {
                		$rec = (object)$rec;

                		if (!$this->fetch("#title='{$rec->title}'")) {
                			if ($this->save($rec)) {
                				$nAffected++;
                			}
                		}
                	}
                }

                if ($nAffected) {
                	$res .= "<li>Добавени са {$nAffected} записа.</li>";
                }
	}


	/**
	 * Извлича масив с индекс ид на сметка и стойност - номер на съотв. с/ка, както и обратния му
	 */
	private function fetchIdToNumMap()
	{
		self::$idToNumMap = array();

		$query = $this->getQuery();

		while ($r = $query->fetch()) {
			self::$idToNumMap[$r->id] = $r->num;
		}

		self::$numToIdMap = array_flip(self::$idToNumMap);
	}


	/**
	 *
	 * Връща номер на сметка по ид на сметка.
	 *
	 * @param int $id ид на сметка
	 * @return string номер на сметка
	 */
	function getNumById($id)
	{
		if (!isset(self::$idToNumMap)) {
			$this->fetchIdToNumMap();
		}

		if (!isset(self::$idToNumMap[$id])) {
			return false;
		}

		return self::$idToNumMap[$id];
	}


	/**
	 *
	 * Връща ид на сметка по номер на сметка
	 *
	 * @param string $num номер на сметка
	 * @return int ид на сметка
	 */
	function getIdByNum($num)
	{
		if (!isset(self::$numToIdMap)) {
			$this->fetchIdToNumMap();
		}

		if (!isset(self::$numToIdMap[$num])) {
			return false;
		}

		return self::$numToIdMap[$num];
	}


	/**
	 * Factory метод - създава обект стратегия (наследник на @link acc_Strategy) според
	 * стратегията на зададената сметка.
	 *
	 * @param int $accountId ид на аналитична с/ка
	 * @return acc_Strategy
	 */
	function createStrategyObject($accountId)
	{
		$strategyType = $this->fetch($accountId, 'strategy');
		$strategy = FALSE;

		switch ($strategy) {
			case 'LIFO':
				$strategy = new acc_strategy_LIFO($accountId);
				break;
			case 'FIFO':
				$strategy = new acc_strategy_FIFO($accountId);
				break;
			case 'MAP':
				$strategy = new acc_strategy_MAP($accountId);
				break;
		}

		return $strategy;
	}


	/**
	 * Връща типа (активна, пасивна) на зададената с/ка.
	 *
	 * @param int $accountId ид на аналитична с/ка
	 * @return string
	 */
	function getType($accountId)
	{
		return $this->fetch($accountId, 'type');
	}


    /**
     * Метода зарежда данни за изнициализация от CSV файл.
     * Полетата num, title, type, strategy идват от CSV файал.
     * groupId1, groupId2, groupId3 се парсват, намира се позицията на '(' 
     * и на ')'. Това, което е вътре е полето num от Lists и по него вземаме
     * id от Lists.   
     */
    function act_LoadCsv()
    {
        /* Prepare $csvAccData */
        if (($handle = fopen(__DIR__ . "/csv/Acc.csv", "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $csvRowFormatted['num']      = $csvRow[0];
                $csvRowFormatted['title']    = $csvRow[1];
                $csvRowFormatted['type']     = $csvRow[2];
                $csvRowFormatted['strategy'] = $csvRow[3];
                $csvRowFormatted['groupId1'] = $this->getListsNum($csvRow[4]);
                $csvRowFormatted['groupId2'] = $this->getListsNum($csvRow[5]);
                $csvRowFormatted['groupId3'] = $this->getListsNum($csvRow[6]);
                
                $csvAccData[] = $csvRowFormatted;
                unset($csvRowFormatted);
            }
            
            fclose($handle);
        }       
        /* END Prepare $csvAccData */
        
        $data = $csvAccData;
                    
        if(!$this->fetch("1=1")) {

            $nAffected = 0;
    
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                if (!$this->fetch("#title='{$rec->title}'")) {
                    if ($this->save($rec)) {
                        $nAffected++;
                   }
                }
            }
        }
        
        /*
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} записа.</li>";
        }
        */
        
        return new Redirect(array('acc_Accounts', 'list'));
    }	
    
    
    /* Връща 'id' от acc_Lists по подаден стринг, от който се взема 'num'
     * 
     * @param string $string
     * @return int $idLists
     */
    function getListsNum($string)
    {
    	/* parse $string and get 'num' field for Lists */
    	$string = strip_tags($string);
    	$string = trim($string);
    	
    	$startPos = strpos($string, '(');
        $endPos   = strpos($string, ')');
        
        if ($startPos && $endPos && ($endPos > $startPos)) {
            $num = substr($string, $startPos + 1, $endPos - $startPos - 1);
            $num = str_replace(' ', '', $num);
            $num = (int) $num;
        } else {
            return NULL;
        }
        /* END parse $string and get 'num' field for Lists */
        
        /* Find for this $num the 'id' in acc_Lists */
        $Lists = cls::get('acc_Lists');
        
        if ($idLists = $Lists->fetchField("num={$num}", 'id')) {
            return $idLists; 
        } else {
            // error
        }
        /* END Find for this $num the 'id' in acc_Lists */
    }

}