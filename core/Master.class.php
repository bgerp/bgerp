<?php



/**
 * Клас 'core_Master' - Мениджър за единичните данни на бизнес обекти
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Master extends core_Manager
{

    /**
     * Мениджърите на детайлите записи към обекта
     */
    public $details;
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle;
    
    
    /**
     * Опашка на записите чакащи ъпдейт
     */
    protected $updateQueue = array();
    
    
    /**
     * Клас на горния таб
     */
    public $tabTopClass;
    
    
    /**
     * Изпълнява се след конструирането на мениджъра
     */
    static function on_AfterDescription(core_Master &$mvc)
    {
        $mvc->attachDetails($mvc->details);
    }
    
    
    /**
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        $me = get_called_class();
        $inst = cls::get($me);
        
        if ($objId) {
            $title = $inst->getTitleForId($objId);
        } else {
            $title = $inst->className;
        }
        
        $linkArr = array();
        
        if (self::haveRightFor('single', $objId)) {
            if ($objId) {
                $linkArr = array(get_called_class(), 'single', $objId);
            } else {
                if (self::haveRightFor('list')) {
                    $linkArr = array(get_called_class(), 'list');
                }
            }
        }
        
        $link = ht::createLink($title, $linkArr);
        
        return $link;
    }
    
    
    /**
     * Връща единичния изглед на обекта
     */
    function act_Single()
    {         
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има id
        expect($id = Request::get('id', 'int'));
        
        // Трябва да има $rec за това $id
        if(!($data->rec = $this->fetch($id))) { 
            // Имаме ли въобще права за единичен изглед?
            $this->requireRightFor('single');
            
        }
        
        $data->details = arr::make($this->details);
        
        expect($data->rec, $data, $id, Request::get('id', 'int'));
        
        // Проверяваме дали потребителя може да вижда списък с тези записи
        $this->requireRightFor('single', $data->rec);
        
        // Подготвяме данните за единичния изглед
        $this->prepareSingle($data);
        
        // Ако модето е че се иска пхп дата тя се връща
        if(Mode::is('dataType', 'php')){
        	return $data;
        }
        
        // Рендираме изгледа
        $tpl = $this->renderSingle($data);
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl, $data);
        
        if (!Request::get('ajax_mode')) {
            if (Mode::is('printing')) {
                $this->logRead('Отпечатване', $id);
            } elseif(Mode::is('pdf')) {
                $this->logRead('PDF', $id);
            } else {
                $this->logRead('Виждане', $id);
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    function prepareSingle_($data)
    {
		setIfNot($data->tabTopParam, 'TabTop');
		
    	if(empty($data->details) && isset($this->details)){
    		$data->details = arr::make($this->details);
    	}
    	
        // Подготвяме полетата за показване
        $this->prepareSingleFields($data);
        
        // Подготвяме вербалните стойности на записа
        $data->row = $this->recToVerbal($data->rec, arr::combine($data->singleFields, '-single'));
        
        /*
         * Запомняме състоянието на $data->rec. Ще сравним това състояние със стойността 
         * на $data->rec след изпълнение на всички `prepare' методи (заедно с техните прихващачи)
         */
        expect(is_object($data->rec));
        $oldRec = clone $data->rec;
        
        // Подготвяме титлата
        $this->prepareSingleTitle($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareSingleToolbar($data);
        
        // Подготвяме детайлите
        if(count($data->details)) {

            // Добавяме текущ таб, ако го има в заявката
            $data->Tab = Request::get('Tab');

            foreach($data->details as $var => $class) {
                $this->loadSingle($var, $class);
                
                if($var == $class) {
                    $method = 'prepareDetail';
                } else {
                    $method = 'prepare' . $var;
                }
                $detailData = $data->{$var} = new stdClass();
                $detailData->masterMvc = $this;
                $detailData->masterId = $data->rec->id;
                $detailData->masterData = $data;
                $this->{$var}->$method($detailData);
            }
        }
        
        /*
         * Сравняваме стойността на $data->rec с предварително запомнената му стойност преди
         * изпълнението на `prepare` методите. Ако двете стойности се окажат различни, изчисляваме
         * $data->row отново. Така гарантираме, че евентуални промени на $data->rec в `prepare`
         * методите ще рефлектира върху $data->row (и в края на краищата - върху това, което вижда
         * потребителя). За отбелязване е, че така получаваме възможност мениджър-детайл да 
         * промени $data->rec (чрез $data->masterData->rec) и тези промени да се отразят на екрана.
         * 
         * По принцип recToVerbal() е метод на View слоя и като такъв той изглежда се извиква
         * прекалено рано (още преди `prepare` методите). Оказва се, обаче, че не може просто
         * да преместим извикването му по-назад (напр. тук) заради наличието на (неясно колко)
         * `prepare` методи (и техни прихващачи), които използват $data->row. Вероятно това не
         * би трябвало да е така, но промяната му би била прекалено рискова. По тази причина
         * правим компромиса да изчислим $data->row втори път, ако се налага. 
         */
        if (serialize($data->rec) != serialize($oldRec)) {
            // $data->rec се е променил в някой `prepare` метод - преизчисляваме $data->row.
            // подсигуряваме, че всички полета на $data->row които не са генерирани в 
            // recToVerbal() a другаде, ще бъдат запазени.
            $newRow = $this->recToVerbal($data->rec, arr::combine($data->singleFields, '-single'));
            foreach (array_keys((array)$newRow) as $n) {
                $data->row->{$n} = $newRow->{$n};
            }
            
            // Добавяме в лога
            self::logWrite("Преизчисляване на полетата на мастера", $data->rec->id, 7);
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя списъка с полетата, които ще се показват в единичния изглед
     */
    function prepareSingleFields_($data)
    {
        if(isset($this->singleFields)) {
            
            // Ако са зададени $this->listFields използваме ги тях за колони
            $data->singleFields = arr::make($this->singleFields, TRUE);
        } else {
            
            // Използваме за колони, всички полета, които не са означени с column = 'none'
            $fields = $this->selectFields("#single != 'none'");
            
            if (count($fields)) {
                foreach ($fields as $name => $fld) {
                    $data->singleFields[$name] = $fld->caption;
                }
            }
        }
        
        if (count($data->singleFields)) {
            
            // Ако титлата съвпада с името на полето, вадим името от caption
            foreach ($data->singleFields as $field => $caption) {
                if (($field == $caption) && $this->fields[$field]->caption) {
                    $data->singleFields[$field] = $this->fields[$field]->caption;
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя титлата в единичния изглед
     */
    function prepareSingleTitle_($data)
    {
        $title = $this->getTitleById($data->rec->id);
        
        $data->title = $this->singleTitle . "|* <b style='color:green;'>{$title}</b>";
        
        return $data;
    }
    
    
    /**
     * Подготвя лентата с инструменти за единичния изглед
     */
    function prepareSingleToolbar_($data)
    {
        $data->toolbar = cls::get('core_Toolbar');
        
        $data->toolbar->class = 'SingleToolbar';
        
        if (isset($data->rec->id) && $this->haveRightFor('edit', $data->rec)) {
            $data->toolbar->addBtn('Редакция', array(
                    $this,
                    'edit',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnEdit',
            	'ef_icon = img/16/edit-icon.png,title=Редактиране на записа');
        }
        
        if (isset($data->rec->id) && $this->haveRightFor('delete', $data->rec)) {
            $data->toolbar->addBtn('Изтриване', array(
                    $this,
                    'delete',
                    $data->rec->id,
                    'ret_url' => toUrl(array($this), 'local')
                ),
                'id=btnDelete,warning=Наистина ли желаете да изтриете документа?,order=31,title=Изтриване на записа', 'ef_icon = img/16/delete.png');
        }
        
        return $data;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderSingle_($data)
    { 
        // Рендираме общия лейаут
        $tpl = $this->renderSingleLayout($data);
        
        // Рендираме заглавието
        $data->row->SingleTitle = $this->renderSingleTitle($data);
        
        // Ако е зададено да се рендира
        if (!$data->noToolbar) {
            
            // Рендираме лентата с инструменти
            $data->row->SingleToolbar = $this->renderSingleToolbar($data);
        }
        
        // Поставяме данните от реда
        $tpl->placeObject($data->row);
        
        // Поставяме детайлите
        if(count($data->details) && $data->noDetails !== TRUE) {
            foreach($data->details as $var => $class) {
                $order = $data->{$var}->Order ? $data->{$var}->Order :  10 * (count($detailInline) + count($detailTabbed) + 1);
                
                // Стойност -1 в подредбата има смисъл на отказ, детайла да се покаже в този матер
                if($order === -1) continue;

                if($data->{$var}->TabCaption) {
                    $detailTabbed[$var] = $order;
                } else {
                    $detailInline[$var] = $order;
                }
            }
            
            if(count($detailInline)) {

                asort($detailInline);

                foreach($detailInline as $var => $order) {
                    
                    $class = $data->details[$var];

                    if($var == $class) {
                        $method = 'renderDetail';
                    } else {
                        $method = 'render' . $var;
                    }
                   
                    if($tpl->isPlaceholderExists($var)) {
                        $tpl->replace($this->{$var}->$method($data->{$var}), $var);
                    } else {
                        $tpl->append($this->{$var}->$method($data->{$var}), 'DETAILS');
                    }
                }
            }
            
            // Добавяме табове
            if(count($detailTabbed)) {
                
                asort($detailTabbed);
              	$tabArray = array();
              	
              	// Подготвяме горни и долни табове
              	$tabTop = cls::get('core_Tabs', array('htmlClass' => 'alphabet', 'urlParam' => $data->tabTopParam));
              	$tabBottom = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
              	
                foreach($detailTabbed as $var => $order) {
                	$url = getCurrentUrl();
                	
                	// Ако е зададено детайла да е в горния таб, добавяме го, иначе е в долния
                	if($data->{$var}->Tab == 'top'){
                		$tab = &$tabTop;
                		
                		// Да се погрижим да се затвори долния таб ако е бил отворен
                		unset($url[$tabBottom->getUrlParam()]);
                	} else {
                		$tab = &$tabBottom;
                	}

                    $url[$tab->getUrlParam()] = $var;
                    $url['#'] = ($data->{$var}->Tab == 'top') ? "detail{$data->tabTopParam}" : 'detailTabs';
                    
                    if (!$data->{$var}->disabled) {
                        $tab->TAB($var, $data->{$var}->TabCaption ? $data->{$var}->TabCaption : $var, toUrl($url));
                    }
				}
                
				$detailsTpl = new ET('');
				
				// Ако има избран детайл от горния таб, показваме го, ако няма винаги рендираме първия
				$selectedTop = $tabTop->getSelected();
				if(!$selectedTop){
					$selectedTop = $tabTop->getFirstTab();
				}
				
				// Ако има избран детайл от горния таб рендираме го
				if($selectedTop){
					if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
						$method = ($selectedTop ==  $data->details[$selectedTop]) ? 'renderDetail' : 'render' . $selectedTop;
						if ($this->{$selectedTop} && is_callable(array($this->{$selectedTop}, $method))) {
							$selectedHtml = $this->{$selectedTop}->$method($data->{$selectedTop});
							$tabHtml = $tabTop->renderHtml($selectedHtml, $selectedTop);
								
							$tabHtml = new ET("<div style='margin-top:20px;' class='tab-top {$this->tabTopClass}'><a id='detail{$data->tabTopParam}'></a>[#1#]</div>", $tabHtml);
							$detailsTpl->append($tabHtml);
						}
					}
				}
				
				// Проверяваме имали избран детайл от долния таб
				$selectedBottom = $tabBottom->getSelected();
				
				// Ако няма и горния детайл няма табове, показваме първия таб на долния
				if(!$selectedBottom && !count($tabTop->getTabs())){
					$selectedBottom = $tabBottom->getFirstTab();
				}
				
				// Ако има избран детайл от долния таб, добавяме го
				if($selectedBottom){
					if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
						$method = ($selectedBottom ==  $data->details[$selectedBottom]) ? 'renderDetail' : 'render' . $selectedBottom;
							
						if ($this->{$selectedBottom} && is_callable(array($this->{$selectedBottom}, $method))) {
							$selectedHtml = $this->{$selectedBottom}->$method($data->{$selectedBottom});
								
							// Ако е избран долен таб, и детайла му е само един, и няма горни табове, го рендираме без таб
							if(count($tabBottom->getTabs()) == 1 && !count($tabTop->getTabs())){
								$tabHtml = $selectedHtml;
							} else {
								$tabHtml = $tabBottom->renderHtml($selectedHtml, $selectedBottom);
							}
								
							if($tabHtml){
								$tabHtml = new ET("<div class='clearfix21'></div><div class='docStatistic'><a id='detailTabs'></a>[#1#]</div>", $tabHtml);
								$detailsTpl->append($tabHtml);
							}
						}
					}
				}
               
				// Добавяме табовете
                $tpl->append($detailsTpl, 'DETAILS');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     */
    function renderSingleLayout_(&$data)
    {
        if (isset($data->singleLayout)) {
            if (!($data->singleLayout instanceof core_ET)) {
                $data->singleLayout = new ET($data->singleLayout);
            }
            
            return $data->singleLayout;
        }
        
        if(isset($this->singleLayoutFile)) {
            $layoutText = getTplFromFile($this->singleLayoutFile);
            if(Mode::is('screenMode', 'narrow') && isset($this->singleLayoutFileNarrow)) {
            	$layoutText = getTplFromFile($this->singleLayoutFileNarrow);
            }
        } elseif(isset($this->singleLayoutTpl)) {
            $layoutText = $this->singleLayoutTpl;
        } else {
            if(count($data->singleFields)) {
                $lastGroup = '';
                foreach($data->singleFields as $field => $caption) {
                    if(strpos($caption, '->')) {
                        list($group, $caption) = explode('->', $caption);
                        $group = tr($group);
                        $fieldsHtml .= "\n<!--ET_BEGIN {$field}-->";
                        if($group != $lastGroup) {
                            $fieldsHtml .= "<tr><td colspan=2 style='padding-left:0px;padding-top:15px;font-weight:bold;border-left:none;border-right:none;'>{$group}</td></tr>\n";
                        }
                        $lastGroup = $group;
                    } else {
                        $lastGroup = '';
                    }
                    
                    $caption = tr($caption);

                    $unit = $this->fields[$field]->unit;
                    if($unit) $unit = ' ' . tr($unit);
                    
                    if($field->inlineTo) {
                        $fieldsHtml = str_replace("[#{$field->inlineTo}_inline#]", " {$caption} [#{$field}#]{$unit}", $fieldsHtml);
                    } else {
                        $fieldsHtml .= "\n<tr><td>" . tr($caption) . "</td><td>[#{$field}#]{$unit}[#{$field}_inline#]</td></tr><!--ET_END {$field}-->";
                    }
                }
            }
            
            $class = $this->cssClass ? $this->cssClass : $this->className;
            
            $layoutText = "\n<div class='singleView'>[#SingleToolbar#]<br><div class='{$class}'><h2>[#SingleTitle#]</h2>" .
            "\n<table class='listTable' style='border:none;'>{$fieldsHtml}\n</table>\n" .
            "<!--ET_BEGIN DETAILS-->[#DETAILS#]<!--ET_END DETAILS--></div></div>";
        }
        
        if(is_string($layoutText)) {
            $layoutText = tr("|*" . $layoutText);
        }

        return new ET($layoutText);
    }
    
    
    /**
     * Рендира титлата на обекта в single view
     */
    function renderSingleTitle_($data)
    {
        return new ET('[#1#]', tr($data->title));
    }
    
    
    /**
     * Рендира лентата с инструменти на единичния изглед
     */
    function renderSingleToolbar_($data)
    {
        if(cls::isSubclass($data->toolbar, 'core_Toolbar')) {
            
            return $data->toolbar->renderHtml();
        }
    }
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_(&$action, $rec = NULL, $userId = NULL)
    {
        if($action == 'single') {
            if(!($requiredRoles = $this->canSingle)) {
                $requiredRoles = $this->getRequiredRoles('read', $rec, $userId);
            }
        } else { 
            $requiredRoles = parent::getRequiredRoles_($action, $rec, $userId);
        }
        
        return $requiredRoles;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Manager $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action != 'admin' && $action != 'psingle' && !(stripos($action, 'psingle'))) {
            // Ако няма достъп до някое действие, но има достъп до частния сингъл
            // Проверяваме правата за частните действия
            if ((($userId && !haveRole($requiredRoles, $userId) || ($requiredRoles == 'no_one'))) && $mvc->haveRightFor('psingle', $rec)) {
        
                $pAction = strtolower($action);
                $pAction = $pAction . 'psingle';
        
                $canPAction = 'can' . ucfirst($pAction);
        
                if (isset($mvc->{$canPAction})) {
                    $requiredRoles = $mvc->getRequiredRoles($pAction, $rec, $userId);
                }
            }
        }
    }
    
    
    /**
     * Връща името на полето, в което ще се записват достъпните сингъли в сесията
     * 
     * @return string
     */
    public static function getAllowedContainerName_()
    {
        
        return 'AllowedContainerIdArr';
    }
    
    
    /**
     * Прикачане на детайли към този мастър
     * 
     * @param array|string $details
     */
    public function attachDetails_($details)
    {
        // Списъка с детайлите става на масив
        $details       = arr::make($details, TRUE);
        $this->details = arr::make($this->details, TRUE);
        
        if (!empty($details)) {
            
            // Зарежда mvc класовете
           
            $this->load($details);
            
            
            foreach($details as $var => $class) {
                $this->details[$var] = $class;
                 if(!($this->{$var}->Master instanceof core_Master)) {
                    
                    if($this->{$var} instanceof core_Manager) {
                        $this->{$var}->Master = &$this;
                        $this->{$var}->masterClass = $this->className;
                        $detailFields = $this->{$var}->selectFields();
                        foreach($detailFields as $fld) {
                            if($fld->type instanceof type_Key) {
                                if($fld->type->params['mvc'] == $this->className) {
                                    $this->{$var}->MasterKey = $fld->name;
                                }
                            }
                        }
                    }
                 }
            }
        }
    }
    
    
    /**
     * Има ли този мастер прикачен зададения детайл?
     * 
     * @param string $detailAlias псевдоним на детайл
     * @param string $detailName име на детайл-клас
     * @return boolean
     */
    public function hasDetail($detailAlias, $detailName = NULL)
    {
        if (isset($detailAlias)) {
            if (!isset($this->details[$detailAlias])) {
                return FALSE;
            }
            
            if (isset($detailName)) {
                return $detailName == $this->details[$detailAlias];
            }
            
            return TRUE;
        } elseif (isset($detailName)) {
            foreach ($this->details as $alias=>$name) {
                if ($name == $detailName) {
                    return TRUE;
                }
            }
            
            return FALSE;
        }

        return !empty($this->detail);
    }
    
    
    /**
     * Връща линк към сингъла на документа
     * 
     * @param integer $id - id на записа
     * @param string $fieldName - Името на полето, което ще се използва за линк
     * @param boolean $absolute
     * @param array $attr
     * 
     * @return core_Et - Линк към сингъла 
     */
    public static function getLinkToSingle_($id, $fieldName=NULL, $absolute=FALSE, $attr = array())
    {
        // Инстанция на класа
        $me = cls::get(get_called_class());
        
        // Ако е подадено името на полето
        if ($fieldName) {
            
            // Вземаме вербалното име
            $name = $me->getVerbal($id, $fieldName);
        } else {
            
            // Генерираме име
            $name = $me->singleTitle . " #" . $id;
        }
        
        // Масива за URL, ако няма права за сингъла е празен
        $url = $me->getSingleUrlArray($id);
        
        setIfNot($attr['ef_icon'], $me->getIcon($id));
        
        // Вземаме линка
        $link = ht::createLink($name, $url, NULL, $attr);
        
        return $link;
    }
    
    
    /**
     * Създава хиперлинк към единичния изглед
     * 
     * @param int $id - ид на запис
     * @param boolean $icon - дали линка да е с икона
     * @param boolean $short - дали линка да е само стрелкичка
     * @return string|core_ET - линк към единичния изглед или името ако потребителя няма права
     */
    public static function getHyperlink($id, $icon = FALSE, $short = FALSE)
    {
    	$me = cls::get(get_called_class());
    
    	$title = $me->getTitleById($id);
    
    	$attr = array();
    	
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		if($icon === TRUE) {
    			$attr['ef_icon'] = $me->singleIcon;
    		} elseif($icon) {
    			$attr['ef_icon'] = $icon;
    		}
    		$attr['class'] = 'specialLink';
    	}
    	
    	if(!$id) {
    		return "<span style='color:red;'>&nbsp;- - -</span>";
    	}
    
    	// Правим линк към единичния изглед на обекта, ако няма права за него
    	// Ако няма права не се показва като линк
    	$url = $me->getSingleUrlArray($id);
    	if(Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')){
    		$url = array();
    	}
    	
    	if($short === TRUE){
    		
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    			$title = ht::createLinkRef($title, $url, NULL, $attr);
    		}
    		
    	} else {
    		$title = ht::createLink($title, $url, NULL, $attr);
    	}
    
    	return $title;
    }
    
    
    /**
     * Създава хиперлинк към единичния изглед който е стрелка след името
     *
     * @param int $id - ид на запис
     * @param boolean $icon - дали линка да е с икона
     * @return string|core_ET - линк към единичния изглед или името ако потребителя няма права
     */
    public static function getShortHyperlink($id, $icon = FALSE)
    {
    	return static::getHyperlink($id, $icon, TRUE);
    }
    
    
    /**
     * Връща урл-то към единичния изглед на обекта, ако потребителя има
     * права за сингъла. Ако няма права връща празен масив
     * 
     * @param int|stdClass $id - ид на запис
     * @return array $url - масив с урл-то на единичния изглед
     */
    public static function getSingleUrlArray_($id)
    {
        if (is_object($id)) {
            $id = $id->id;
        }
        
    	$me = cls::get(get_called_class());
    	
    	$url = array();
    	
    	// Ако потребителя има права за единичния изглед, подготвяме линка
    	if ($me->haveRightFor('single', $id)) {
    		$url = array($me, 'single', $id, 'ret_url' => TRUE);
    	} 
    	
    	return $url;
    }
    

    /**
     * След промяна в детайлите на обект от този клас
     */
    protected static function on_AfterUpdateDetail(core_Master $mvc, $id, core_Manager $detailMvc)
    {
    	if(isset($id)){
    		
    		// Запомняне кои документи трябва да се обновят
    		$mvc->updateQueue[$id] = $id;
    	}
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    static function on_Shutdown($mvc)
    {
    	if(count($mvc->updateQueue)){
    		
    		foreach ($mvc->updateQueue as $id) {
    			$mvc->updateMaster($id);
    		}
    	}
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    function updateMaster_($id)
    {
    }
    
    
    /**
     * Линк към мастъра, подходящ за показване във форма
     * 
     * @param int $id - ид на записа
     * @return string $masterTitle - линк заглавие
     */
    public function getFormTitleLink($id)
    {
    	$masterTitle = $this->getTitleById($id);
    	$len = Mode::is('screenMode', 'narrow') ? 32 : 48;
    	 
    	$masterTitle = str::limitLen($masterTitle, $len);
    	$masterTitle = str_replace('|', '&#124;', $masterTitle);
    	
    	$url = $this->getSingleUrlArray($id);
    	
    	if(count($url)) {
    		$masterTitle = ht::createLink($masterTitle, $url, NULL, array('ef_icon' => $this->singleIcon, 'class' => 'linkInTitle'));
    	}
    	
    	$masterTitle = "<b style='color:#ffffcc;'>{$masterTitle}</b>";
    	
    	return $masterTitle;
    }
}
