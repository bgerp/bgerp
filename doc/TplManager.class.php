<?php 

/**
 * Мениджър за шаблони, които ще се използват от документи.
 * Добавя възможността спрямо шаблона да се скриват/показват полета от мастъра
 * За целта в класа и неговите детайли трябва да се дефинира '$toggleFields',
 * където са изброени незадължителните полета, които могат да се скриват/показват.
 * Задават се във вида: "field1=caption1,field2=caption2"
 *
 * Ако избраният мениджър има тези полета, то отдолу на формата се появява възможност за
 * избор на кои от тези незадължителни полета да се показват във въпросния шаблон. Ако никое
 * не е избрано. То се показват всичките
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_TplManager extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Изгледи на документи';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Изглед';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_SaveAndNew, plg_State2, plg_Modified, doc_Wrapper, plg_RowTools, plg_Sorting, plg_Search';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой може да го изтрива?
     */
    public $canDelete = 'ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'doc/tpl/SingleTemplateLayout.shtml';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name, lang, docClassId, createdOn, createdBy, modifiedOn, modifiedBy, state';
    
    
    /**
     * Кеш на скриптовете
     *
     * @var array
     */
    protected static $cacheScripts = array();
    
    
    /**
     * Кеш за константите
     */
    protected static $cacheConstants = array();
    
    
    /**
     * Кои уеб константи от настройките на пакетите са за дефолтни шаблони
     */
    protected static $templateSetupConstants = array('dec_Setup' => array('DEC_DEF_TPL_BG', 'DEC_DEF_TPL_EN'), 
                                                     'eshop_Setup' => array('ESHOP_SALE_DEFAULT_TPL_BG', 'ESHOP_SALE_DEFAULT_TPL_EN'),
                                                     'sales_Setup' => array('SALE_SALE_DEF_TPL_BG', 'SALE_SALE_DEF_TPL_EN', 'SALE_INVOICE_DEF_TPL_BG', 'SALE_INVOICE_DEF_TPL_EN'),
    );


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, docClassId, lang, content, narrowContent';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory, width=100%');
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Документ, width=100%,mandatory,silent,removeAndRefreshForm=handler|handlerInEffectOn');
        $this->FLD('lang', 'varchar(2)', 'caption=Език,notNull,defValue=bg,value=bg,mandatory,width=2em');
        $this->FLD('content', 'html(tinyEditor=no)', 'caption=Текст->Широк,column=none, width=100%,mandatory');
        $this->FLD('narrowContent', 'html(tinyEditor=no)', 'caption=Текст->Мобилен,column=none, width=100%');
        $this->FLD('path', 'varchar', 'caption=Файл, width=100%,input=none');
        $this->FLD('originId', 'key(mvc=doc_TplManager)', 'input=hidden,silent');
        $this->FLD('hash', 'varchar', 'input=none');
        $this->FLD('hashNarrow', 'varchar', 'input=none');
        $this->FLD('printCount', 'int(min=0)', 'caption=Допълнително->Брой копия при печат,placeholder=По подразбиране');
        
        // Полета които ще се показват в съответния мениджър и неговите детайли
        $this->FLD('toggleFields', 'blob(serialize,compress)', 'caption=Допълнително->Полета за скриване,input=none');
        $this->FLD('handler', 'class(interface=doc_TplScriptIntf,select=title,allowEmpty)', 'caption=Допълнително->Обработвач,input=none,placeholder=Автоматично');
        $this->FLD('handlerInEffectOn', 'datetime(format=smartTime)', 'caption=Допълнително->Обработвач (в сила от),input=none');

        $this->setDbUnique('name');
        $this->setDbIndex('docClassId');
        $this->setDbIndex('lang,docClassId');
        $this->setDbIndex('docClassId,state');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if($docClassId = Request::get('docClassId', 'int')) {
            bgerp_Notifications::clear(array('doc_TplManager', 'list', 'docClassId' => $docClassId), '*');
        }

        $data->listFilter->setOptions('docClassId', static::getClassesWithTemplates());
        $data->listFilter->setField('docClassId', "placeholder=Всички документи,silent");
        $data->listFilter->showFields = 'docClassId, search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();

        if($data->listFilter->isSubmitted()){
            if(!empty($data->listFilter->rec->docClassId)){
                $data->query->where("#docClassId = {$data->listFilter->rec->docClassId}");
            }
        }

        $data->query->orderBy('modifiedOn', 'DESC');
    }
    
    
    /**
     * След потготовка на формата за добавяне / редактиране
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        // Ако шаблона е клонинг
        if ($originId = $rec->originId) {
            
            // Копират се нужните данни от ориджина
            expect($origin = static::fetch($originId));
            $form->setDefault('docClassId', $origin->docClassId);
            $form->setDefault('lang', $origin->lang);
            $form->setDefault('content', $origin->content);
            $form->setDefault('narrowContent', $origin->narrowContent);
            $form->setDefault('toggleFields', $origin->toggleFields);
            $form->setDefault('printCount', $origin->printCount);
        }
        
        // При смяна на документа се рефрешва формата
        if (empty($rec->id)) {
            $form->setField('docClassId', array('removeAndRefreshForm' => 'lang|content|toggleFields|path'));
        }
        
        // Ако има избран документ, се подготвят допълнителните полета
        if ($rec->docClassId) {
            $DocClass = cls::get($rec->docClassId);
            $mvc->prepareToggleFields($DocClass, $form);
        }

        // Ако шаблона е системен, може да се променя само броя му копия
        if($rec->createdBy == core_Users::SYSTEM_USER){
            $form->setReadOnly('name');
            $fields = array_keys($form->selectFields("#input != 'hidden' AND #name != 'name' AND #name != 'printCount'"));
            foreach ($fields as $fld){
                $form->setField($fld, 'input=none');
            }
        }

        $handlers = core_Classes::getOptionsByInterface('doc_TplScriptIntf', 'title');
        foreach ($handlers as $handlerKey => $handlerVal){
            if(!cls::get($handlerKey)->canAddToClass($rec->docClassId)){
                unset($handlers[$handlerKey]);
            }
        }

        if(countR($handlers)){
            $form->setField('handler', 'input');
            $form->setField('handlerInEffectOn', 'input');
            $form->setOptions('handler', array('' => '') + $handlers);
        }
    }
    
    
    /**
     * За мастър документа и всеки негов детайл се генерира поле за избор кои от
     * незадължителните му полета да се показват
     *
     * @param core_Mvc  $DocClass - класа на който е прикачен плъгина
     * @param core_Form $form     - формата
     */
    private function prepareToggleFields(core_Mvc $DocClass, core_Form &$form)
    {
        // Слагане на поле за избор на полета от мастъра
        $this->setTempField($DocClass, $form);
        
        // За вски детайл (ако има) се създава поле
        $details = arr::make($DocClass->details);
        if ($details) {
            foreach ($details as $d) {
                $Dclass = cls::get($d);
                $this->setTempField($Dclass, $form);
            }
        }
    }
    
    
    /**
     * Ф-я създаваща FNC поле към формата за избор на кои от незадължителните му полета
     * да се показват. Използва 'toggleFields' от документа, за генериране на полетата
     *
     * @param core_Mvc  $DocClass - класа за който се създава полето
     * @param core_Form $form     - формата
     */
    private function setTempField(core_Mvc $DocClass, core_Form &$form)
    {
        // Ако са посочени незадължителни полета
        if ($DocClass->toggleFields) {
            
            // Създаване на FNC поле със стойности идващи от 'toggleFields'
            $fldName = ($DocClass instanceof core_Master) ? 'masterFld' : $DocClass->className;
            $fields = array_keys(arr::make($DocClass->toggleFields));
            $form->FNC($fldName, "set({$DocClass->toggleFields})", 'caption=Полета за показване->Колони,input,columns=3,tempFld,silent');
            
            // Стойност по подразбиране
            if (isset($form->rec->{$fldName})) {
                $default = $form->rec->{$fldName};
            } elseif (isset($form->rec->toggleFields) && array_key_exists($fldName, $form->rec->toggleFields)) {
                $default = $form->rec->toggleFields[$fldName];
            } else {
                $default = implode(',', $fields);
            }
            
            $form->setDefault($fldName, $default);
        }
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            // Проверка дали избрания клас поддържа 'doc_plg_TplManager'
            $plugins = cls::get($rec->docClassId)->getPlugins();
            if (empty($plugins['doc_plg_TplManager'])) {
                $form->setError('docClassId', "Избрания документ не поддържа 'doc_plg_TplManager'!");
            }
            
            // Ако шаблона е клонинг
            if ($originId = $rec->originId) {
                $origin = static::fetch($originId);
                $new = preg_replace("/\s+/", '', $form->rec->content);
                $old = preg_replace("/\s+/", '', $origin->content);
                
                // Ако клонинга е за същия документ като ориджина, и няма промяна
                // в съдържанието се слага предупреждение
                if (empty($rec->id) && $origin->docClassId == $rec->docClassId && $new == $old) {
                    $form->setWarning('content', 'Клонирания шаблон е със същото съдържание като оригинала!');
                }
            }
            
            // Ако има временни полета, то данните се обработват
            $tempFlds = $form->selectFields('#tempFld');
            if (countR($tempFlds)) {
                $mvc->prepareDataFld($form, $tempFlds);
            }

            if(empty($rec->handler)){
                $rec->handlerInEffectOn = null;
            } else {
                if(empty($rec->handlerInEffectOn)){
                    $rec->handlerInEffectOn = dt::now();
                }
            }
        }
    }
    
    
    /**
     * Всяко едно допълнително поле се обработва и информацията
     * от него се записва в блоб полето
     *
     * @param core_Form $form   - формата
     * @param array     $fields - FNC полетата
     */
    private function prepareDataFld(core_Form &$form, $fields)
    {
        $rec = &$form->rec;
        
        // За всяко едно от опционалните полета
        $toggleFields = array();
        foreach ($fields as $name => $fld) {
            $toggleFields[$name] = $rec->$name;
        }
        
        // Подготвяне на масива за сериализиране
        $rec->toggleFields = $toggleFields;
    }
    
    
    /**
     * Връща подадения шаблон
     *
     * @param int $id - ид на шаблон
     *
     * @return core_ET $tpl - шаблона
     */
    public static function getTemplate($id)
    {
        $rec = static::fetch($id, 'content,narrowContent');
        
        // Ако сме в режим тесен
        if (Mode::is('screenMode', 'narrow')) {
            
            // И има шаблон за мобилен изглед вземаме него
            if (!empty($rec->narrowContent)) {
                $content = $rec->narrowContent;
            }
        }
        
        // Взимаме обикновения шаблон ако няма мобилен шаблон
        if (empty($content)) {
            $content = $rec->content;
        }
        
        $content = core_ET::loadFilesRecursivelyFromString($content);
        
        return new ET(tr('|*' . $content));
    }
    
    
    /**
     * Връща първия шаблон за документа на езика на ориджинина му, ако има
     *
     * @param mixed $class    - класа
     * @param int   $originId - ориджина на записа
     *
     * @return FALSE|int - намерения шаблон
     */
    public static function getTplByOriginLang($class, $originId)
    {
        if (isset($originId)) {
            $origin = doc_Containers::getDocument($originId);
            if ($origin->getInstance()->hasPlugin('doc_plg_TplManager')) {
                $templateLang = doc_TplManager::fetchField($origin->fetchField('template'), 'lang');
                $templates = doc_TplManager::getTemplates($class, $templateLang);
                
                return key($templates);
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща всички активни шаблони за посочения мениджър
     *
     * @param int $classId - ид на клас
     * @param string|null $lang - език, null за текущия
     * @return array $options - опции за шаблоните на документа
     */
    public static function getTemplates($classId, $lang = null)
    {
        $options = array();
        $classId = cls::get($classId)->getClassId();
        expect(core_Classes::fetch($classId));
        
        // Извличане на всички активни шаблони за документа
        $query = static::getQuery();
        $query->where("#docClassId = {$classId}");
        $query->where("#state = 'active'");
        if (isset($lang)) {
            $query->where("#lang = '{$lang}'");
        }
        
        while ($rec = $query->fetch()) {
            $options[$rec->id] = $rec->name;
        }
        
        ksort($options);
        
        return $options;
    }


    /**
     * Добавя шаблони от масив
     *
     * @param core_Mvc $mvc
     * @param array $tplArr
     * @param int $added
     * @param int $updated
     * @param int $skipped
     * @return string $res
     * @throws core_exception_Expect
     */
    public static function addOnce($mvc, $tplArr, &$added = 0, &$updated = 0, &$skipped = 0)
    {
        $skipped = $added = $updated = 0;
        $mvc = cls::get($mvc);

        $notificationArr = array();
        foreach ($tplArr as $object) {
            $object['docClassId'] = $mvc->getClassId();
            $object = (object) $object;

            // Ако има старо име на шаблона
            if ($object->oldName) {
                // Извличане на записа на стария шаблон
                $exRec = static::fetch("#name = '{$object->oldName}'");
            } else {
                $exRec = null;
            }
            
            // Ако няма старо име проверка имали шаблон с текущото име
            if (!$exRec) {
                $exRec = static::fetch("#name = '{$object->name}'");
            }
            
            if ($exRec) {
                $object->id = $exRec->id;
                $object->state = $exRec->state;
            }
            
            // Ако файла на шаблона не е променян, то записа не се обновява
            expect($object->hash = md5_file(getFullPath($object->content)));
            
            if ($object->narrowContent) {
                expect($object->hashNarrow = md5_file(getFullPath($object->narrowContent)));
            }

            if ($exRec && ($exRec->name == $object->name) && ($exRec->hashNarrow == $object->hashNarrow) && ($exRec->hash == $object->hash) && ($exRec->lang == $object->lang) && (serialize($exRec->toggleFields) == serialize($object->toggleFields)) && ($exRec->path == $object->content)) {
                $skipped++;
                continue;
            }

            // Ако е имало полета за модифициране, а вече няма да се занулят
            if(isset($exRec->toggleFields) && empty($object->toggleFields)){
                $object->toggleFields = null;
            }

            $object->path = $object->content;
            $object->content = getFileContent($object->content);
            if ($object->narrowContent) {
                $object->narrowContent = getFileContent($object->narrowContent);
            }
            
            // Ако е съществуващ запис, затворен, не се активира повторно
            $newState = 'active';
            if(isset($object->id)  && $object->state == 'closed'){
                $newState = 'closed';
            }
            
            $object->createdBy = core_Users::SYSTEM_USER;
            $object->state = $newState;

            // Ако ще се обновява съществуващ системен шаблон
            if($object->id){
                $clQuery = static::getQuery();
                $clQuery->where("#originId = {$object->id}");
                $admins = core_Users::getByRole('admin');

                // и той вече е клониран в други шаблони
                while($clRec = $clQuery->fetch()){
                    $url = array('doc_TplManager', 'list', 'docClassId' => $clRec->docClassId);

                    // Ще се нотифицират всички админи, че е имало промяна
                    foreach ($admins as $adminId){
                        $notificationArr[$adminId][$object->id] = (object)array('url' => $url, 'msg' => "Променен е шаблон|* '{$object->name}', |моля редактирайте шаблоните, които са клонирани от него|*!");
                    }
                }
            }

            static::save($object);
            
            ($object->id) ? $updated++ : $added++;
        }

        // Нотифициране на потребителите, клонирали променен вече шаблон
        if(countR($notificationArr)){
            foreach ($notificationArr as $userId => $messages){
                foreach ($messages as $msgArr){
                    bgerp_Notifications::add($msgArr->msg, $msgArr->url, $userId);
                }
            }
        }

        $class = ($added > 0 || $updated > 0) ? ' class="green"' : '';
        
        $res = "<li{$class}>Добавени са {$added} шаблона за " . mb_strtolower($mvc->title) . ", обновени са {$updated}, пропуснати са {$skipped}</li>";
        
        return $res;
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $data->toolbar->addBtn('Всички', array('doc_TplManager', 'list'), 'caption=Всички шаблони,ef_icon=img/16/view.png');
        
        // Добавяне на бутон за клониране
        if ($mvc->haveRightFor('add')) {
            $data->toolbar->addBtn('Клониране', array('doc_TplManager', 'add', 'originId' => $data->rec->id), 'ef_icon=img/16/copy16.png,title=Клониране на шаблона');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($res == 'no_one') {
            
            return;
        }
        
        if ($action == 'delete' && isset($rec)) {
            // Ако шаблона е използван в някой документ, не може да се трие
            if (cls::get($rec->docClassId)->fetch("#template = {$rec->id}")) {
                $res = 'no_one';
            }
        }
        
        // Ако шаблона е избран като дефолтен в някоя уеб константа, той не може да се изключва
        if($action == 'changestate' && isset($rec)){
            $in = self::getIdsInSetupConstants();
            if(in_array($rec->id, $in)){
                $res = 'no_one';
            } else {
                $availableTplCount = $mvc->count("#docClassId = {$rec->docClassId} AND #state = 'active' AND #id != {$rec->id}");
                if(!$availableTplCount){
                    $res = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Връща скриптовия клас на шаблона (ако има)
     *
     * @param int $templateId - ид на шаблона
     * @param date|null $date - от коя дата
     * @return doc_TplScript|false $Script/false - заредения клас, или false ако не може да се зареди
     */
    public static function getTplScriptClass($templateId, $date = null)
    {
        // Ако няма шаблон
        if (!$templateId) return false;
        
        // Ако има кеширан в хита резултат за скрипта, връща се той
        if (isset(static::$cacheScripts[$templateId])) return static::$cacheScripts[$templateId];
        
        // Намираме пътя на файла генерирал шаблона
        $templateRec = doc_TplManager::fetch($templateId);
        $date = isset($date) ? $date : dt::now();

        // Ако има ръчно избран обработвач - зарежда се той
        if(isset($templateRec->handler)){
            if (cls::load($templateRec->handler, true)){
                if($date >= $templateRec->handlerInEffectOn){
                    $Script = cls::get($templateRec->handler);

                    return $Script;
                }
            }
        }

        // Ако шаблона има съответстващ файл в директорията
        if (!$templateRec->path) return false;

        // Ако в директорията има файл със същото име но с разширение .class.php се зарежда той
        $filePath = str_replace('.shtml', '.class.php', $templateRec->path);
        if (getFullPath($filePath)) {
            $supposedClassname = str_replace('/', '_', $filePath);
            $supposedClassname = str_replace('.class.php', '', $supposedClassname);

            // Прави се опит да се зареди и е от подадения интерфейс
            if (cls::load($supposedClassname, true)) {
                
                // Зарежда се
               $Script = cls::get($supposedClassname);
               if($Script instanceof doc_TplScript){

                   return $Script;
               }
            }
        }
        
        // Ако не е открит такъв файл - нищо
        return false;
    }
    
    
    /**
     * Намира избраните шаблони като уеб константи
     * 
     * @return array
     */
    private static function getIdsInSetupConstants()
    {
        if(empty(self::$cacheConstants)){
            self::$cacheConstants = array();
            foreach(self::$templateSetupConstants as $packSetupClass => $constants){
                foreach ($constants as $constantName){
                    $templateId = $packSetupClass::get($constantName, true);
                    self::$cacheConstants[$templateId] = $templateId;
                }
            }
        }
        
        return self::$cacheConstants;
    }
    
    
    /**
     * Масив със класовете, които имат шаблони в модела
     * 
     * @return array $res
     */
    public static function getClassesWithTemplates()
    {
        $res = array();
        $query = self::getQuery();
        $classIds = arr::extractValuesFromArray($query->fetchAll(), 'docClassId');
        foreach ($classIds as $classId){
            $res[$classId] = tr(core_Classes::fetchField($classId, 'title'));
        }
        
        return $res;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->originId)){
            $row->originId = static::getHyperlink($rec->originId, true);
        }

        if(doc_TplManager::haveRightFor('list')){
            $row->docClassId = ht::createLink($row->docClassId, array('doc_TplManager', 'list', 'docClassId' => $rec->docClassId));
        }

        if(isset($rec->handler)){
            $row->handler .= " ({$row->handlerInEffectOn})";
        }
    }
}
