<?php 



/**
 * Шаблони за създаване на етикети
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Templates extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Шаблони';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Шаблон';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/template.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'label/tpl/SingleLayoutTemplates.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да създва етикет?
     */
    public $canCreatelabel = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_RowTools2, plg_Created, plg_State, plg_Search, plg_Rejected, plg_Clone, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, sizes, template=Шаблон, lang=Език, classId, createdOn, createdBy';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, template, css';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'label_TemplateFormats';
    
    
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'sysId';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('sizes', 'varchar(128)', 'caption=Размери, mandatory, width=100%');
        $this->FLD('classId', 'class(interface=label_SequenceIntf, select=title, allowEmpty)', 'caption=Интерфейс');
        $this->FLD('template', 'html', 'caption=Шаблон->HTML');
        $this->FLD('css', 'text', 'caption=Шаблон->CSS');
        $this->FLD('sysId', 'varchar', 'input=none');
        $this->FLD('lang', 'varchar(2)', 'caption=Език,notNull,defValue=bg,value=bg,mandatory,width=2em');
        
        $this->setDbUnique('sysId');
    }
    
    
    /**
     * След подготовка на тулбара за еденичния изглед
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        // Ако имаме права за добавяне на етикет
        if ($mvc->haveRightFor('createlabel', $data->rec->id)) {
        
        	// Добавяме бутон за нов етикет
            $data->toolbar->addBtn('Нов етикет', array('label_Labels', 'add', 'templateId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/star_2.png, title=Създаване на нов етикет');
        }
    }
    
    
    /**
     * Връща всички медии, които отговарят на размерите на медията на шаблона
     * 
     * @param integer $id
     */
    public static function getMediaForTemplate($id)
    {
        $sizes = self::fetchField($id, 'sizes');
        $mediaArr = label_Media::getMediaArrFromSizes($sizes);
        
        return $mediaArr;
    }
    
    
    /**
     * Връща шаблона
     * 
     * @param integer $id - id на записа
     * 
     * @return core_Et - Шаблона на записа
     */
    public static function getTemplate($id)
    {
        // Масив с шаблоните
        static $tplArr = array();
        
        // Ако преди е бил извлечен
        if ($tplArr[$id]) return $tplArr[$id];
        
        // Вземаме записа
        $rec = self::fetch($id);
        
        // Вкарваме CSS-а, като инлай в шаблона
        $tplArr[$id] = $rec->template;
        
        return $tplArr[$id];
    }
    
    
    /**
     * Връща плейсхолдерите на стринга
     * 
     * @param string $content
     * 
     * @return array
     */
    public static function getPlaceholders($content)
    {
        $hash = md5($content);
    	if(!array_key_exists($hash, static::$cache)){
    		preg_match_all('/\[#([\wа-я\(\)]{1,})#\]/ui', $content, $matches);
    		$placesArr = arr::make($matches[1], TRUE);
    		
    		static::$cache[$hash] = $placesArr;
    	}
        
    	return static::$cache[$hash];
    }
    
    
    /**
     * Вкарва CSS-a към шаблона, като инлайн
     * 
     * @param integer $id
     * @param core_Et $template
     */
    public static function addCssToTemplate($id, $template=NULL)
    {
        // Масив с шаблоните
        static $templateArrCss = array();
        
        if (!$template) {
            $template = self::getTemplate($id);
        }
        
        // Хеша на шаблона - предпазва от повторно генерира за един и същи шаблон
        $hash = md5($template);
        
        // Ако преди е бил извлечен
        if ($templateArrCss[$hash]) return $templateArrCss[$hash];
        
        // Вземаме записа
        $rec = self::fetch($id);
        
        // Вкарваме CSS-а, като инлайн
        $templateArrCss[$hash] = self::templateWithInlineCSS($template, $rec->css);
        
        return $templateArrCss[$hash];
    }
    
    
    /**
     * Проверява подадения плейсхолдер дали се съдържа в шаблона
     * 
     * @param integer $id - id на записа
     * @param string $placeHolder - Име на плейсхолдера
     * 
     * @return boolean
     */
    public static function isPlaceExistInTemplate($id, $placeHolder)
    {
        // Вземаме шаблона
        $template = self::getTemplate($id);
        
        // Масив с шаблоните
        static $placesArr = array();
        
        // Ако не е генериран преди
        if (!$placesArr[$id]) {
            
            // Масив с плейсхолдерите
            $placesArrAll = self::getPlaceHolders($template);
            
            // Ключовете и стойностите да са равни
            $placesArr[$id] = arr::make($placesArrAll, TRUE);
        }
        
        // Ако плейсхолдера се съдържа в шаблона
        if ($placesArr[$id][$placeHolder]) {
            
            return TRUE;
        }
    }
    
    
    /**
     * Вкарва CSS'a в шаблона, като инлайн стил
     * 
     * @param string $template - HTML
     * @param string $css - CSS
     * 
     * @return string
     */
    public static function templateWithInlineCSS($template, $css)
    {
        // Вкарваме темплейта в блок, който после ще отрежим
        $template = '<div id="begin">' . $template . '<div id="end">'; 
        
        // Вземаме пакета
        $conf = core_Packs::getConfig('csstoinline');
        
        // Класа
        $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
        
        // Инстанция на класа
        $inst = cls::get($CssToInline);
        
        // Стартираме процеса
        $template =  $inst->convert($template, $css);
        
        // Вземамема само шаблона, без допълнителните добавки
        $template = str::cut($template, '<div id="begin">', '<div id="end">');
        
        // Очакваме да не е NULL
        expect($template !== NULL);
        
        return $template;
    }
    
    
    /**
     * След подготовка на вербалното представяне на реда
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Вземаме шаблона с вкарания css
        $row->template = static::templateWithInlineCSS($row->template, $rec->css);
    }
    
    
 	/**
 	 * Изпълнява се след подготовката на формата за филтриране
 	 * 
 	 * @param core_Master_type $mvc
 	 * @param stdClass $data
 	 */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Формата
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $form->FNC('fState', 'enum(, draft=Чернови, active=Използвани)', 'caption=Състояние, allowEmpty,autoFilter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search, fState';
        
        // Инпутваме полетата
        $form->input('fState', 'silent');
        
        // Подреждаме по състояние
        $data->query->orderBy('#state=ASC');
        
        // Подреждаме по дата на създаване
        $data->query->orderBy('#createdOn=DESC');

        if ($state = $data->listFilter->rec->fState) {
            $data->query->where(array("#state = '[#1#]'", $state));
        }
    }
    
    
    /**
     * Активира шаблона
     * 
     * @param integer $id - id на записа
     * 
     * @retunr integer - id на записа
     */
    public static function activateTemplate($id)
    {
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Очакваме да не е оттеглен
        expect($rec->state != 'rejected');
        
        // Ако състоянието не е 'active'
        if ($rec->state != 'active') {
            
            // Сменяме състоянито на активно
            $rec->state = 'active';
            
            // Записваме
            $id = static::save($rec);
            
            // Активираме използваните броячи в шаблона
            label_TemplateFormats::activateCounters($rec->id);
            
            return $id;
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Добавяме всички възжможни избори за медия
        $sizesArr = label_Media::getAllSizes();
        $sizesArr = array('' => '') + $sizesArr;
        $data->form->setSuggestions('sizes', $sizesArr);
    }
    
    
    /**
     * Замества плейсхолдерите с тяхната стойност
     * 
     * @param string $string
     * @param array $placeArr
     * 
     * @return string
     */
    public static function placeArray($string, $placeArr)
    {
        if (!$string || !$placeArr) return $string;
        
        $nArr = array();
        $placeholders = self::getPlaceholders($string);
       
        // Всички плейсхолдъри, подменяме ги с главни букви
        if(is_array($placeholders)){
        	$replacePlaceholders = array();
        	foreach ($placeholders as $p){
        		$new = mb_strtoupper($p);
        		$newPlaceholder = self::toPlaceholder($new);
        		$oldPlaceholder = self::toPlaceholder($p);
        		$replacePlaceholders[$oldPlaceholder] = $newPlaceholder;
        	}
        	
        	if(count($replacePlaceholders)){
        		$string = strtr($string, $replacePlaceholders);
        	}
        }
        
        // Заместване на плейсхолдърите със стойностите
        foreach ((array)$placeArr as $key => $val) {
        	$key = mb_strtoupper($key);
            $key = self::toPlaceholder($key);
            $nArr[$key] = $val;
        }
       
        $replacedStr = strtr($string, $nArr);
       
        // Връщане на заместения стринг
        return $replacedStr;
    }
    
    
    /**
     * Връща плейсхолдера от стринга
     * 
     * @param string $str
     * 
     * @return string
     */
    public static function toPlaceholder($str)
    {
        return "[#{$str}#]";
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param label_Labels $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис
        if ($rec) {
            
            // Ако редактираме
            if ($action == 'edit') {
                
                // Ако е оттеглено
                if ($rec->state == 'rejected') {
                    
                    // Оттеглените да не могат да се редактират
                    $requiredRoles = 'no_one';
                }
            }
            
            // Ако оттегляме
            if ($action == 'reject') {
                
                // Ако е активно
                if ($rec->state == 'active') {
                    
                    // Активните да не могат да се оттеглят
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако ще се клонира, трябва да има права за добавяне
        if ($action == 'cloneuserdata') {
            if (!$mvc->haveRightFor('add', $rec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако ще добавяме нов етикет
        if ($action == 'createlabel') {
            
            // Ако състоянието е оттеглено
            if ($rec && $rec->state == 'rejected') {
                
                // Никой да не може да създава
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Премахваме някои полета преди да клонираме
     * @see plg_Clone
     * 
     * @param label_Labels $mvc
     * @param object $rec
     * @param object $nRec
     */
    protected static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
    {
        unset($nRec->state);
        unset($nRec->exState);
        unset($nRec->lastUsedOn);
        unset($nRec->searchKeywords);
        unset($nRec->createdOn);
        unset($nRec->createdBy);
    }
    
    
    /**
     * Премахваме някои полета преди да клонираме
     * @see plg_Clone
     * @todo да се премахне след като се добави тази функционалността в плъгина
     * 
     * @param label_Labels $mvc
     * @param object $rec
     * @param object $nRec
     */
    protected static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
        // Клонира и детайлите след клониране на мастера
        $detailsArr = arr::make($mvc->details);
        foreach ($detailsArr as $detail) {
            $detailInst = cls::get($detail);
            $query = $detailInst->getQuery();
            $masterKey = $mvc->{$detail}->masterKey;
            $query->where("#{$masterKey} = {$rec->id}");
            while($dRec = $query->fetch()) {
                unset($dRec->id);
                $dRec->{$masterKey} = $nRec->id;
                $detailInst->save($dRec);
            }
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$res = '';
    	$added = $skipped = $updated = 0;
    	
    	// Добавяне на дефолтни шаблони
    	$templateArr = array('defaultTpl' => 'label/tpl/DefaultLabelBG.shtml', 'defaultTplEn' => 'label/tpl/DefaultLabelEN.shtml');
    	foreach ($templateArr as $sysId => $tplPath){
    		$title = ($sysId == 'defaultTpl') ? 'Базов шаблон за етикети' : 'Default label template';
    		$lang = ($sysId == 'defaultTpl') ? 'bg' : 'en';
    		
    		// Ако няма запис
    		$exRec = self::fetch("#sysId = '{$sysId}'");
    		
    		if(!$exRec){
    			$exRec = new stdClass();
    			$exRec->sysId = $sysId;
    			$exRec->title = $title;
    			core_Classes::add('planning_Tasks');
    			$exRec->classId = planning_Tasks::getClassId();
    		}
    		$exRec->sizes = '100x72 mm';
    		$exRec->state = 'active';
    		$exRec->lang = $lang;
    		
    		// Ако има промяна в шаблона, ъпдейтва се
    		$templateHash = md5_file(getFullPath($tplPath));
    		if(md5($exRec->template) != $templateHash || $exRec->title != $title){
    			($exRec->id) ? $updated++ : $added;
    			$exRec->template = getFileContent($tplPath);
    			
    			if(isset($exRec->id)){
    				label_TemplateFormats::delete("#templateId = {$exRec->id}");
    			}
    			
    			core_Users::forceSystemUser();
    			self::save($exRec);
    			
    			// Добавяне на плейсхолдърите
    			$arr = $this->getPlaceholders($exRec->template);
    			if(is_array($arr)){
    				foreach ($arr as $placeholder){
    					$type = (in_array($placeholder, array('BARCODE', 'preview'))) ? 'html' : 'caption';
    					$dRec = (object)array('placeHolder' => $placeholder, 'type' => $type, 'templateId' => $exRec->id);
    					label_TemplateFormats::save($dRec);
    				}
    			}
    			
    			core_Users::cancelSystemUser();
    		} else {
    			$skipped++;
    		}
    	}
    	
    	$class = ($added > 0 || $updated > 0) ? ' class="green"' : '';
    	$res = "<li{$class}>Добавени са {$added} шаблона за етикети, обновени са {$updated}, пропуснати са {$skipped}</li>";
    	 
    	return $res;
    }
}
