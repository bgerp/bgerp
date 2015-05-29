<?php 


/**
 * Шаблони за създаване на етикети
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Templates extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Шаблони';
    
    
    /**
     * 
     */
    var $singleTitle = 'Шаблон';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/template.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'label/tpl/SingleLayoutTemplates.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да създва етикет?
     */
    var $canCreatelabel = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'label_Wrapper, plg_RowTools, plg_Created, plg_State, plg_Search, plg_Rejected, plg_Clone, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, sizes, template=Шаблон, createdOn, createdBy';
    
    
    /**
     * 
     */
    var $rowToolsField = 'id';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, template, css';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'label_TemplateFormats';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('sizes', 'varchar(128)', 'caption=Размери, mandatory, width=100%');
        $this->FLD('template', 'html', 'caption=Шаблон->HTML');
        $this->FLD('css', 'text', 'caption=Шаблон->CSS');
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
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
        preg_match_all('/\[#([\wа-я]{1,})#\]/ui', $content, $matches);
        
        $placesArr = arr::make($matches[1], TRUE);
        
        return $placesArr;
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
    static function isPlaceExistInTemplate($id, $placeHolder)
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
    static function templateWithInlineCSS($template, $css)
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
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $row
     * @param unknown_type $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Вземаме шаблона с вкарания css
        $row->template = static::templateWithInlineCSS($row->template, $rec->css);
    }
    
    
 	/**
 	 * Изпълнява се след подготовката на формата за филтриране
 	 * 
 	 * @param unknown_type $mvc
 	 * @param unknown_type $data
 	 */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Формата
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search';
        
        // Инпутваме полетата
        $form->input(NULL, 'silent');
        
        // Подреждаме по състояние
        $data->query->orderBy('#state=ASC');
        
        // Подреждаме по дата на създаване
        $data->query->orderBy('#createdOn=DESC');
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
    public static function on_AfterPrepareEditForm($mvc, &$data)
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
        
        foreach ((array)$placeArr as $key => $val) {
            $key = self::toPlaceholder($key);
            $nArr[$key] = $val;
        }
        
        $replacedStr = strtr($string, $nArr);
        
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
    public static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
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
    public static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
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
}
