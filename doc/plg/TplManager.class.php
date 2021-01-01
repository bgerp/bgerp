<?php


/**
 * Клас 'doc_plg_TplManager'
 *
 * Плъгин за  който позволява на даден мениджър да си избира шаблон
 * за единичния изглед качен в doc_TplManager. Ако има избран шаблон
 * от формата то този изглед се избира по подразбиране а не единичния
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_TplManager extends core_Plugin
{
    /**
     * След инициализирането на модела
     *
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
        
        // Добавя поле за избор на шаблон, ако няма
        if (empty($mvc->fields['template'])) {
            $mvc->FLD('template', 'key(mvc=doc_TplManager,select=name)', 'caption=Допълнително->Изглед,notChangeableByContractor');
        }
        
        setIfNot($mvc->canAsclient, 'no_one');
        setIfNot($mvc->createView, true);
    }
    
    
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_AfterAttachDetails(core_Mvc $mvc, &$res, $details)
    {
        if ($mvc->details) {
            $details = arr::make($mvc->details);
            
            // На всеки детайл от модела му се прикача 'doc_plg_TplManagerDetail' (ако го няма)
            foreach ($details as $Detail) {
                if ($mvc->$Detail instanceof $Detail) {
                    $plugins = $mvc->$Detail->getPlugins();
                    if (empty($plugins['doc_plg_TplManagerDetail'])) {
                        $mvc->$Detail->load('doc_plg_TplManagerDetail');
                    }
                }
            }
        }
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     *
     * @param core_Mvc $mvc
     *
     * @return bool
     */
    protected static function checkApplicability(core_Mvc $mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            
            return false;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);
        
        if (isset($plugins['doc_DocumentPlg'])) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Метод връщащ темплейта на документа, ако го няма връща ид-то на първия възможен
     * темплейт за този тип документи
     */
    public static function on_AfterGetTemplate(core_Mvc $mvc, &$res, $id)
    {
        $rec = is_object($id) ? $id : $mvc->fetch($id);
        expect($rec);
        
        if (empty($rec->template)) {
            $templates = doc_TplManager::getTemplates($mvc->getClassId());
            $res = key($templates);
            
            $rec->template = $res;
            $mvc->save_($rec, 'template');
        } else {
            $res = $rec->template;
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        $templates = doc_TplManager::getTemplates($mvc->getClassId());
        
        if (count($templates) >= 1) {
            $data->form->setOptions('template', $templates);
        } else {
            $data->form->setError('template', 'Няма активен шаблон за документа');
            $data->form->setField('template', 'input=hidden');
        }
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec)
    {
        if (is_object($rec)) {
            if ($rec->id) {
                
                // Ако няма шаблон, за шаблон се приема първия такъв за модела
                $rec->template = $mvc->getTemplate($rec);
                $rec->tplLang = $mvc->pushTemplateLg($rec->template);
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_BeforeRenderSingleLayout(core_Mvc $mvc, &$res, $data)
    {
        // За текущ език се избира този на шаблона
        $mvc->pushTemplateLg($data->rec->template);
        
        // Ако ще се замества целия сингъл, подменяме го елегантно
        if (!$mvc->templateFld) {
            if (Request::get('asClient')) {
                $data->singleLayout = getTplFromFile($mvc->printAsClientLayaoutFile);
            } else {
                $data->singleLayout = doc_TplManager::getTemplate($data->rec->template);
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_BeforeRenderSingleToolbar(core_Mvc $mvc, &$res, $data)
    {
        // Маха се пушнатия език, за да може да се рендира тулбара нормално
        core_Lg::pop();
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleToolbar(core_Mvc $mvc, &$res, $data)
    {
        // След рендиране на тулбара отново се пушва езика на шаблона
        $mvc->pushTemplateLg($data->rec->template);
    }
    
    
    /**
     *
     * @param core_Mvc    $mvc
     * @param NULL|string $res
     * @param int         $templateId
     */
    public static function on_AfterPushTemplateLg($mvc, &$res, $templateId)
    {
        // Ако езика на шаблона е зададен в мода
        if ($modeLg = Mode::get('tplManagerLg')) {
            $res = $modeLg;
        } else {
            if (Request::get('asClient')) {
                $res = 'en';
            } else {
                $res = doc_TplManager::fetchField($templateId, 'lang');
            }
        }
        
        core_Lg::push($res);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout(core_Mvc $mvc, &$tpl, $data)
    {
        if ($data->_selectTplForm) {
            $tpl->append($data->_selectTplForm, 'noPrint');
        }
        
        // Ако има посочен плейсхолдър където да отива шаблона, то той се използва
        if ($mvc->templateFld) {
            if (Request::get('asClient')) {
                $content = getTplFromFile($mvc->printAsClientLayaoutFile);
            } else {
                $content = doc_TplManager::getTemplate($data->rec->template);
            }
            
            $tpl->replace($content, $mvc->templateFld);
        }
        
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            $tpl->removeBlock('shareLog');
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
        // След като документа е рендиран, се възстановява нормалния език
        core_Lg::pop();
        
        if (Request::get('asClient')) {
            $tpl->removeBlock('blank');
            $tpl->removeBlock('ExtState');
        }
    }
    
    
    /**
     *
     *
     * @param core_Mvc      $mvc
     * @param NULL|stdClass $res
     * @param int           $id
     * @param string        $mode
     * @param NULL|stdClass $options
     */
    public function on_BeforeGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = null)
    {
        if ($options && $options->tplManagerId) {
            if (!$options->rec && $id) {
                $options->rec = $mvc->fetchRec($id);
                $options->rec->template = $options->tplManagerId;
            }
        }
    }
    
    
    /**
     * Преди подготовка на на единичния изглед
     */
    public static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, &$data)
    {
        // Показваме форма за избор на шаблон в екрана за отпечатване
        if (Mode::is('printing') && Request::get('Printing') && haveRole('powerUser') && !Mode::is('preventChangeTemplateOnPrint') && !Request::get('asClient')) {
            $form = cls::get('core_Form');
            
            $form->class .= ' simpleForm';
            $form->FNC('tplId', 'key(mvc=doc_TplManager, select=name)', 'caption=Изглед, silent, input');
            $form->addAttr('tplId', array('onchange' => 'this.form.submit();'));
            
            $tplArr = doc_TplManager::getTemplates($mvc->getClassId());
            
            expect($tplArr);
            
            $form->setOptions('tplId', $tplArr);
            $form->setDefault('tplId', $data->rec->template);
            $form->input();
            
            if ($form->isSubmitted()) {
                if ($data->rec->template != $form->rec->tplId) {
                    $data->rec->template = $form->rec->tplId;
                    
                    $lg = doc_TplManager::fetchField($form->rec->tplId, 'lang');
                    
                    Mode::set('tplManagerLg', $lg);
                    
                    // В зависимост от подредбата на плъгините, може и да има вече генериран екшън
                    if ($data->__MID__) {
                        $logRec = doclog_Documents::fetchByMid($data->__MID__);
                        if (!isset($logRec->data)) {
                            $logRec->data = new stdClass();
                        }
                        $logRec->data->tplManagerId = $data->rec->template;
                        
                        doclog_Documents::save($logRec, 'dataBlob');
                    } else {
                        $doclogActionDataArr = Mode::get('doclogActionData') ? Mode::get('doclogActionData') : array();
                        $doclogActionDataArr['tplManagerId'] = $data->rec->template;
                        Mode::set('doclogActionData', $doclogActionDataArr);
                    }
                }
            }
            
            Mode::push('forcePrinting', true);
            $data->_selectTplForm = $form->renderHtml();
            Mode::pop('forcePrinting');
            
            if ($data->_selectTplForm) {
                // Това е необходимо за инпутва на формата
                // Когат няма 'addSbBtn'
                $data->_selectTplForm->appendOnce(
                    '<input type="hidden" name="Cmd[default]" value=1>',
                                'FORM_HIDDEN'
                );
            }
        }
        
        // Ако има скриптов клас за шаблона, подаваме му данните
        if ($Script = doc_TplManager::getTplScriptClass($data->rec->template)) {
            $Script->beforePrepareMasterData($mvc, $data);
        }
    }
    
    
    /**
     * След подготовка на на единичния изглед
     */
    public static function on_AfterPrepareSingle(core_Mvc $mvc, &$res, &$data)
    {
        // Ако има избран шаблон
        if ($data->rec->template) {
            $toggleFields = doc_TplManager::fetchField($data->rec->template, 'toggleFields');
            
            // Ако има данни, за кои полета да се показват от мастъра
            if (countR($toggleFields) && $toggleFields['masterFld'] !== null) {
                
                // Полетата които трябва да се показват
                $fields = arr::make($toggleFields['masterFld']);
                
                // Всички полета, които могат да се скриват/показват
                $toggleFields = arr::make($mvc->toggleFields);
                
                // Намират се засичането на двата масива с полета
                $intersect = array_keys(array_intersect_key((array) $data->row, $toggleFields));
                
                foreach ($intersect as $k) {
                    
                    // За всяко от опционалните полета: ако не е избран да се показва, се маха
                    if (!in_array($k, $fields)) {
                        unset($data->row->$k);
                    }
                }
            }
            
            // Ако има скриптов клас за шаблона, подаваме му данните
            if ($Script = doc_TplManager::getTplScriptClass($data->rec->template)) {
                $Script->modifyMasterData($mvc, $data);
            }
        }
        
        // Добавяме бланките
        if (Request::get('asClient')) {
            $companyName = $data->row->inlineContragentName;
            
            if ($companyName) {
                $params = array(
                    'pixelPerPoint' => 6,
                    'outFileName' => null,
                    'quality' => 'L',
                    'outerFrame' => 0,
                    'absolute' => true,
                );
                
                try {
                    $data->row->blankQrClient = barcode_Generator::getLink('qr', $companyName, array('width' => 87, 'height' => 87), $params);
                } catch (Exception $e) {
                    reportException($e);
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->tplLang) {
            core_Lg::pop();
            
            // Заместваме вербалното състояние и име с тези според езика на текущата сесия
            if ($mvc->getFieldType('state', false)) {
                $row->state = $mvc->getVerbal($rec, 'state');
            }
            
            if ($mvc->getFieldType('activatedOn', false)) {
                $row->activatedOn = $mvc->getVerbal($rec, 'activatedOn');
            }
            
            if ($mvc->getFieldType('createdOn', false)) {
                $row->createdOn = $mvc->getVerbal($rec, 'createdOn');
            }
            
            if ($mvc->getFieldType('modifiedOn', false)) {
                $row->modifiedOn = $mvc->getVerbal($rec, 'modifiedOn');
            }
            
            $row->singleTitle = tr($mvc->singleTitle);
        }
    }
    
    
    /**
     * Метод по подразбиране за намиране на дефолт шаблона
     */
    public static function on_AfterGetDefaultTemplate($mvc, &$res, $rec)
    {
        if (!$res) {
            $cData = doc_Folders::getContragentData($rec->folderId);
            $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
            $languages = array();
            
            if (empty($cData->countryId) || $bgId === $cData->countryId) {
                $languages['bg'] = 'bg';
            } else {
                $cLanguages = drdata_Countries::fetchField($cData->countryId, 'languages');
                $languages = array_merge(arr::make($cLanguages, true), $languages);
            }
            $languages['en'] = 'en';
            
            // Намираме първия шаблон на езика който се говори в държавата
            foreach ($languages as $lang) {
                $tplId = doc_TplManager::fetchField("#lang = '{$lang}' AND #docClassId = '{$mvc->getClassId()}'", 'id');
                if ($tplId) {
                    break;
                }
            }
            
            $res = $tplId;
        }
    }
    
    
    /**
     * Какъв да е дефолтния език от записа при генериране на имейл
     */
    public static function on_AfterGetLangFromRec($mvc, &$res, $id)
    {
        if (!$id) {
            
            return;
        }
        
        $rec = $mvc->fetch($id);
        
        if (!$rec->template) {
            
            return;
        }
        
        $lang = doc_TplManager::fetchField($rec->template, 'lang');
        
        $res = $lang;
    }
    
    
    /**
     * Връща опциите за избор на шаблон на даден документ на английски език
     */
    public static function on_AfterGetTemplateBgOptions(core_Mvc $mvc, &$res)
    {
        if (!$res) {
            $res = cls::get('doc_TplManager')->makeArray4Select('name', "#docClassId = '{$mvc->getClassId()}' AND #lang = 'bg'");
            ksort($res);
        }
    }
    
    
    /**
     * Връща опциите за избор на шаблон на даден документ на английски език
     */
    public static function on_AfterGetTemplateEnOptions(core_Mvc $mvc, &$res)
    {
        if (!$res) {
            $res = cls::get('doc_TplManager')->makeArray4Select('name', "#docClassId = '{$mvc->getClassId()}' AND #lang = 'en'");
            ksort($res);
        }
    }
    
    
    /**
     *
     *
     * @param core_Mvc     $mvc
     * @param core_Et|NULL $res
     * @param stdClass     $rec
     * @param stdClass     $row
     */
    public static function on_BeforeGetLetterHead($mvc, &$res, $rec, $row)
    {
        if (Request::get('asClient')) {
            
            return false;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'asclient') {
            if (!$mvc->printAsClientLayaoutFile || $rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'single') {
            if (Request::get('asClient')) {
                $requiredRoles = $mvc->getRequiredRoles('asclient', $rec, $userId);
            }
        }
    }
    
    
    /**
     *
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if ($mvc->haveRightFor('asClient', $data->rec)) {
            $data->toolbar->addBtn('П Клиент', array($mvc, 'single', $data->rec->id, 'Printing' => 'yes', 'asClient' => true), "id=btnClientPrint{$data->rec->containerId},target=_blank,row=2", 'ef_icon = img/16/print_go.png,title=Печатане с данните на клиента');
        }
    }
    
    
    /**
     *
     * @param core_Mvc $mvc
     * @param core_ET  $container
     * @param int      $cnt
     * @param stdClass $rec
     */
    public static function on_AfterRenderPrintCopy($mvc, &$container, $cnt, $rec)
    {
        if ($cnt > 1) {
            $container->removeBlock('FORM_FIELDS');
        }
    }
    
    
    /**
     * Колко копия да се отпечатат от документа при принтиране
     */
    public static function on_AfterGetCopiesOnPrint($mvc, &$res, $id)
    {
        // Ръчно зададения брой копия е с приоритет
        $rec = $mvc->fetchRec($id);
        if($templatePrintCount = doc_TplManager::fetchField($rec->template, 'printCount')){
            $res = $templatePrintCount;
        }
    }
}
