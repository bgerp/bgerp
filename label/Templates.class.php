<?php 

/**
 * Шаблони за създаване на етикети
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
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
     * Кой има право да променя?
     */
    public $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'labelMaster, admin, ceo';
    
    
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
     * Необходими роли за оттегляне на документа
     */
    public $canRestore = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да клонира системни данни?
     */
    public $canClonesysdata = 'ceo, powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_RowTools2, plg_Created, plg_State2, plg_Search, plg_Rejected, plg_Clone, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, sizes, template=Шаблон, lang=Език, classId, peripheralDriverClassId, createdOn, createdBy, lastUsedOn=Последно, state';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, template, css, sizes';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'label_TemplateFormats';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'label_TemplateFormats';
    
    
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'sysId,state,exState,lastUsedOn,createdOn,createdBy';
    
    
    /**
     * Кои са системните плейсхолдъри на етикетите
     */
    public static $systemPlaceholders = array('Текущ_етикет', 'Общо_етикети', 'Страница');
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('sizes', 'varchar(128)', 'caption=Размери, mandatory, width=100%');
        $this->FLD('classId', 'class(interface=label_SequenceIntf, select=title, allowEmpty)', 'caption=Източник->Клас');
        $this->FLD('peripheralDriverClassId', 'class(interface=peripheral_PrinterIntf, select=title, allowEmpty)', 'caption=Източник->Периферия');
        
        $this->FLD('template', 'html(tinyEditor=no)', 'caption=Шаблон->HTML');
        $this->FLD('css', 'text', 'caption=Шаблон->CSS');
        $this->FLD('sysId', 'varchar', 'input=none');
        $this->FLD('lang', 'varchar(2)', 'caption=Език,notNull,defValue=bg,value=bg,mandatory,width=2em');
        $this->FLD('rendererClassId', 'class(interface=label_TemplateRendererIntf, select=title, allowEmpty)', 'caption=Източник->Рендер');
        
        $this->setDbUnique('sysId');
        $this->setDbIndex('classId');
    }
    
    
    /**
     * След подготовка на тулбара за еденичния изглед
     *
     * @param labeL_Templates $mvc
     * @param stdClass        $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        // Ако имаме права за добавяне на етикет
        if (label_Prints::haveRightFor('add', (object) array('templateId' => $data->rec->id))) {
            
            // Добавяме бутон за нов етикет
            $data->toolbar->addBtn('Нов етикет', array('label_Prints', 'add', 'templateId' => $data->rec->id, 'ret_url' => true), 'ef_icon = img/16/price_tag_label.png, title=Създаване на нов етикет');
        }
        
        // Ако имаме права за листване на етикети
        if (label_Prints::haveRightFor('list')) {
            
            $pQuery = label_Prints::getQuery();
            $pQuery->where(array("#templateId = '[#1#]'", $data->rec->id));
            $pQuery->show('id');
            $cnt = $pQuery->count();
            
            if ($cnt) {
                $data->toolbar->addBtn("Отпечатвания|* ({$cnt})", array('label_Prints', 'list', 'templateId' => $data->rec->id, 'ret_url' => true), 'ef_icon = img/16/view.png, title=Показване на всички етикети от този шаблон');
            }
        }
    }
    
    
    /**
     * Връща всички медии, които отговарят на размерите на медията на шаблона
     *
     * @param int $id
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
     * @param int $id - id на записа
     *
     * @return core_Et - Шаблона на записа
     */
    public static function getTemplate($id)
    {
        // Масив с шаблоните
        static $tplArr = array();
        
        // Ако преди е бил извлечен
        if ($tplArr[$id]) {
            
            return $tplArr[$id];
        }
        
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
        $matches = null;
        if (!array_key_exists($hash, static::$cache)) {
            preg_match_all('/\[#([\wа-я\(\)]{1,})#\]/ui', $content, $matches);
            $placesArr = arr::make($matches[1], true);
            
            static::$cache[$hash] = $placesArr;
        }
        
        return static::$cache[$hash];
    }
    
    
    /**
     * Вкарва CSS-a към шаблона, като инлайн
     *
     * @param int     $id
     * @param core_Et $template
     */
    public static function addCssToTemplate($id, $template = null)
    {
        // Масив с шаблоните
        static $templateArrCss = array();
        
        if (!$template) {
            $template = self::getTemplate($id);
        }
        
        // Хеша на шаблона - предпазва от повторно генерира за един и същи шаблон
        $hash = md5($template);
        
        // Ако преди е бил извлечен
        if ($templateArrCss[$hash]) {
            
            return $templateArrCss[$hash];
        }
        
        // Вземаме записа
        $rec = self::fetch($id);
        
        // Вкарваме CSS-а, като инлайн
        $templateArrCss[$hash] = self::templateWithInlineCSS($template, $rec->css);
        
        return $templateArrCss[$hash];
    }
    
    
    /**
     * Проверява подадения плейсхолдер дали се съдържа в шаблона
     *
     * @param int    $id          - id на записа
     * @param string $placeHolder - Име на плейсхолдера
     *
     * @return bool
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
            $placesArr[$id] = arr::make($placesArrAll, true);
        }
        
        // Ако плейсхолдера се съдържа в шаблона
        if ($placesArr[$id][$placeHolder]) {
            
            return true;
        }
    }
    
    
    /**
     * Вкарва CSS'a в шаблона, като инлайн стил
     *
     * @param string $template - HTML
     * @param string $css      - CSS
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
        $template = $inst->convert($template, $css);
        
        // Вземамема само шаблона, без допълнителните добавки
        $template = str::cut($template, '<div id="begin">', '<div id="end">');
        
        // Очакваме да не е NULL
        expect($template !== null);
        
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
     * @param label_Templates $mvc
     * @param stdClass        $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Формата
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $sourceOptions = array('-1' => 'Без източник') + core_Classes::getOptionsByInterface('label_SequenceIntf', 'title');
        
        $form->FNC('fClassId', 'varchar', 'caption=Източник');
        $form->setOptions('fClassId', array('' => '') + $sourceOptions);
        
        $q = $mvc->getQuery();
        $q->groupBy('sizes');
        $q->show('sizes');
        $sArr = array();
        $sArr[''] = '';
        while ($qR = $q->fetch()) {
            $sArr[$qR->sizes] = $qR->sizes;
        }
        $form->setSuggestions('sizes', $sArr);
        
        $form->showFields = 'search,fClassId,sizes';
        if (!core_Request::get('Rejected', 'int')) {
            $form->FNC('fState', 'enum(, active=Използвани, closed=Затворени)', 'caption=Всички, allowEmpty,autoFilter');
            $form->showFields .= ', fState';
            $form->setDefault('fState', 'active');
            
            // Инпутваме полетата
            $form->input('fState,fClassId,sizes', 'silent');
        }
        
        // Подреждане по състояние
        $data->query->orderBy('createdOn', 'DESC');
        
        if ($state = $data->listFilter->rec->fState) {
            $data->query->where(array("#state = '[#1#]'", $state));
        }
        
        if ($classId = $data->listFilter->rec->fClassId) {
            if ($classId == '-1') {
                $data->query->where('#classId IS NULL');
            } else {
                $data->query->where(array("#classId = '[#1#]'", $classId));
            }
        }
        $sizes = $data->listFilter->rec->sizes;
        $sizes = trim($sizes);
        if ($sizes) {
            $data->query->where(array("#sizes = '[#1#]'", $sizes));
        }
    }
    
    
    /**
     * Активира шаблона
     *
     * @param int $id - id на записа
     *
     * @retunr integer|NULL - id на записа
     */
    public static function activateTemplate($id)
    {
        if (!$id) {
            
            return ;
        }
        
        // Вземаме записа
        $rec = static::fetch($id);
        
        if (!$rec) {
            
            return ;
        }
        
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
     * @param stdClass     $data
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
     * @param array  $placeArr
     *
     * @return string
     */
    public static function placeArray($string, $placeArr, $templateId = null)
    {
        if (!$string || !$placeArr) {
            
            return $string;
        }
        
        $nArr = array();
        $placeholders = self::getPlaceholders($string);
        
        if(isset($templateId)){
            
            // Ако има избран допълнителен клас за рендиране, дава му се възможност да се променят данните на етикета
            if($rendererClassId = label_Templates::fetchField($templateId, 'rendererClassId')){
                if(cls::load($rendererClassId, true)){
                    $RendererIntf = cls::getInterface('label_TemplateRendererIntf', $rendererClassId);
                    $RendererIntf->modifyLabelData($templateId, $string, $placeholders, $placeArr);
                }
            }
        }
        
        // Всички плейсхолдъри, подменяме ги с главни букви
        if (is_array($placeholders)) {
            $replacePlaceholders = array();
            foreach ($placeholders as $p) {
                $new = mb_strtoupper($p);
                $newPlaceholder = self::toPlaceholder($new);
                $oldPlaceholder = self::toPlaceholder($p);
                $replacePlaceholders[$oldPlaceholder] = $newPlaceholder;
            }
            
            if (countR($replacePlaceholders)) {
                $string = strtr($string, $replacePlaceholders);
            }
        }
        
        // Заместване на плейсхолдърите със стойностите
        foreach ((array) $placeArr as $key => $val) {
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
     * @param label_Templates $mvc
     * @param string          $requiredRoles
     * @param string          $action
     * @param stdClass        $rec
     * @param int             $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
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
        }
        
        // Ако ще се клонира, трябва да има права за добавяне
        if ($action == 'cloneuserdata') {
            if (!$mvc->haveRightFor('add', $rec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Добавя шаблон от файл. Обновява съществуващ файл само ако има промяна в някой от параметрите му
     *
     * @param string      $title             - име на шаблона
     * @param string      $filePath          - път към файла на шаблона
     * @param string      $sysId             - систем ид на шаблона
     * @param array       $sizes             - размери на шаблона, масив с 2 елемента: широчина и височина
     * @param string|NULL $lang              - език на шаблона
     * @param mixed       $class             - клас към който да е шаблона
     * @param mixed       $peripheralClassId - драйвър на периферията
     * @param mixed       $rendererClassId   - клас за рендиране
     * 
     *
     * @return stdClass|FALSE - записа на шаблона или FALSE ако не е променян
     */
    public static function addFromFile($title, $filePath, $sysId, $sizes = array(), $lang = 'bg', $class = null, $peripheralClassId = null, $rendererClassId = null, $cssPath = null)
    {
        // Проверки на данните
        expect(in_array($lang, array('bg', 'en')), $lang);
        expect(is_array($sizes) && countR($sizes) == 2, $sizes);
        $sizes = array_values($sizes);
        $sizes = implode('x', $sizes) . ' mm';
        expect($path = getFullPath($filePath), $path);
        $templateHash = md5_file($path);

        if(isset($cssPath)){
            expect($cssPath = getFullPath($cssPath), $cssPath);
            $cssTemplateHash = md5_file($cssPath);
        }

        // Има ли шаблон с това систем ид
        $exRec = self::fetch(array("#sysId = '[#1#]'", $sysId));

        if (!$exRec) {
            $exRec = new stdClass();
            $exRec->sysId = $sysId;
        }
        
        if (isset($class)) {
            $classId = cls::get($class)->getClassId();
        }
        
        $isContentTheSame = md5($exRec->template) == $templateHash;
        if(isset($cssPath)){
            $isContentTheSame = $isContentTheSame && md5($exRec->css) == $cssTemplateHash;
        }

        // Ако подадените параметри са същите като съществуващите, не се обновява/създава нищо
        if ($isContentTheSame && $exRec->title == $title && $exRec->title == $title && $exRec->sizes == $sizes && $exRec->lang == $lang && $exRec->classId == $classId) {
            
            return false;
        }
        
        // Обновяване на контента, ако има промяна
        if ($isContentTheSame !== true) {
            $exRec->template = getFileContent($path);
            if(isset($cssPath)){
                $exRec->css = getFileContent($cssPath);
            }
        }
        
        if (isset($classId)) {
            $exRec->classId = $classId;
        }
        $exRec->title = $title;
        $exRec->sizes = $sizes;
        $exRec->lang = $lang;
        $exRec->state = 'active';
        
        if (isset($classId)) {
            $exRec->classId = $classId;
        }
        
        if (isset($peripheralClassId)) {
            $exRec->peripheralDriverClassId = cls::get($peripheralClassId)->getClassId();
        }
        
        if (isset($rendererClassId)) {
            $exRec->rendererClassId = cls::get($rendererClassId)->getClassId();
        }
        
        // Създаване/обновяване на шаблона
        static::save($exRec);
        //bp();
        return $exRec;
    }
    
    
    /**
     * Добавяне  на дефолтен шаблон
     * 
     * @param string $sysId
     * @param $array $array
     * @param int $modified
     * @param int $skipped
     * 
     * @return void
     */
    public static function addDefaultLabelsFromArray($sysId, $array, &$modified, &$skipped)
    {
        $tRec = self::addFromFile($array['title'], $array['path'], $sysId, $array['sizes'], $array['lang'], $array['class'], $array['peripheralDriverClass'], $array['rendererClassId'], $array['cssPath']);
        
        if ($tRec !== false) {
            label_TemplateFormats::delete("#templateId = {$tRec->id}");
            $arr = static::getPlaceholders($tRec->template);

            if (is_array($arr)) {
                foreach ($arr as $placeholder) {
                    if (in_array($placeholder, self::$systemPlaceholders)) {
                        continue;
                    }
                    
                    if ($placeholder == 'BARCODE') {
                        $params = array('Showing' => 'barcodeAndStr', 'BarcodeType' => 'code128', 'Ratio' => '4', 'Width' => '160', 'Height' => '60', 'Rotation' => 'yes');
                        label_TemplateFormats::addToTemplate($tRec->id, $placeholder, 'barcode', $params);
                    } elseif ($placeholder == 'EAN') {
                        $params = array('Showing' => 'barcodeAndStr', 'BarcodeType' => 'ean13', 'Ratio' => '4', 'Width' => '260', 'Height' => '70', 'Rotation' => 'no');
                        label_TemplateFormats::addToTemplate($tRec->id, $placeholder, 'barcode', $params);
                    } elseif($placeholder == 'QR_CODE'){
                        $params = array('Showing' => 'barcodeAndStr', 'BarcodeType' => 'qr', 'Ratio' => '4', 'Width' => '60', 'Height' => '60', 'Rotation' => 'no');
                        label_TemplateFormats::addToTemplate($tRec->id, $placeholder, 'barcode', $params);
                    } elseif($placeholder == 'QR_CODE_90'){
                        $params = array('Showing' => 'barcodeAndStr', 'BarcodeType' => 'qr', 'Ratio' => '4', 'Width' => '90', 'Height' => '90', 'Rotation' => 'no');
                        label_TemplateFormats::addToTemplate($tRec->id, $placeholder, 'barcode', $params);
                    } elseif($placeholder == 'BARCODE_WORK_CARDS'){
                        $params = array('Showing' => 'barcode', 'BarcodeType' => 'code128', 'Ratio' => '4', 'Width' => '120', 'Height' => '60', 'Rotation' => 'no');
                        label_TemplateFormats::addToTemplate($tRec->id, $placeholder, 'barcode', $params);
                    } elseif(is_array($array['htmlPlaceholders']) && in_array($placeholder, $array['htmlPlaceholders'])){
                        $params = array();
                        label_TemplateFormats::addToTemplate($tRec->id, $placeholder, 'html', $params);
                    } else {
                        $type = 'caption';
                        $params = array();
                        if ($placeholder == 'PREVIEW') {
                            $type = ($placeholder == 'PREVIEW') ? 'image' : 'caption';
                            $params = array('Width' => planning_Setup::get('TASK_LABEL_PREVIEW_WIDTH'), 'Height' => planning_Setup::get('TASK_LABEL_PREVIEW_HEIGHT'));
                        }
                        
                        label_TemplateFormats::addToTemplate($tRec->id, $placeholder, $type, $params);
                    }
                }
            }
            $modified++;
        } else {
            $skipped++;
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $res = '';
        $modified = $skipped = 0;
        $array = array('defaultTplPack' => array('title' => 'Етикети от опаковки', 'path' => 'label/tpl/DefaultLabelPack.shtml', 'lang' => 'bg', 'class' => 'cat_products_Packagings', 'sizes' => array('100', '72')),
                       'defaultTplPack' => array('title' => 'Етикети от протоколи за производство', 'path' => 'label/tpl/DefaultLabelProductionNote.shtml', 'lang' => 'bg', 'class' => 'planning_DirectProductionNote', 'sizes' => array('100', '72')),
                       'defaultTplPackiningList' => array('title' => 'Packaging List label', 'path' => 'label/tpl/DefaultLabelPallet.shtml', 'lang' => 'en', 'class' => 'store_ShipmentOrders', 'sizes' => array('170', '105')),
                       'defaultTplPriceList' => array('title' => 'Ценоразпис без EAN', 'path' => 'label/tpl/DefaultPricelist.shtml', 'lang' => 'bg', 'class' => 'price_reports_PriceList', 'sizes' => array('64.5', '33.5')),
                       'defaultTplPriceListEan' => array('title' => 'Ценоразпис с EAN', 'path' => 'label/tpl/DefaultPricelistEAN.shtml', 'lang' => 'bg', 'class' => 'price_reports_PriceList', 'sizes' => array('64.5', '33.5')),
                       'defaultTplHrCodes' => array('title' => 'QR на служител', 'path' => 'label/tpl/DefaultHrCodes.shtml', 'lang' => 'bg', 'class' => 'planning_Hr', 'sizes' => array('64.5', '33.5')),
                       'defaultTplWorkCards' => array('title' => 'Стойности на раб. карти', 'path' => 'label/tpl/DefaultWorkCards.shtml', 'lang' => 'bg', 'class' => 'planning_WorkCards', 'sizes' => array('100', '72')),
        );
        
        core_Users::forceSystemUser();
        foreach ($array as $sysId => $cArr) {
            static::addDefaultLabelsFromArray($sysId, $cArr, $modified, $skipped);
        }
        core_Users::cancelSystemUser();
        
        $class = ($modified > 0) ? ' class="green"' : '';
        $res = "<li{$class}>Променени са са {$modified} шаблона за етикети, пропуснати са {$skipped}</li>";
        
        return $res;
    }
    
    
    /**
     * Извлича шаблоните към класа
     * 
     * @param mixed $class
     * @return array $res
     */
    public static function getTemplatesByClass($class, $ignoreWithPeripheralDriver = true)
    {
        $Class = cls::get($class);
        $tQuery = label_Templates::getQuery();
        $tQuery->where("#classId = '{$Class->getClassId()}' AND #state != 'rejected' AND #state != 'closed'");
        if($ignoreWithPeripheralDriver){
            $tQuery->where("#peripheralDriverClassId IS NULL");
        }
        
        $res = array();
        while ($tRec = $tQuery->fetch()) {
            $res[$tRec->id] = $tRec;
        }
        
        return $res;
    }
}
