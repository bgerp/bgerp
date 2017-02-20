<?php


/**
 * Експортиране на документи
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_E extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Експортиране на документ";
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin';
    
    
    /**
     * Кой може да оттелгя имейла
     */
    protected $canReject = 'admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'no_one';
    
    
    /**
     * Плъгините и враперите, които ще се използват
     */
    public $loadList = 'plg_Created';
    
    
    /**
     * Да не се кодират id-тата
     */
    var $protectId = FALSE;
    
    
    /**
     * 
     */
    function description()
    {
        $this->FLD('format', 'varchar(16, ci)', 'caption=Формат, mandatory');
        $this->FLD('validity', 'time(suggestions=1 ден|1 седмица|1 месец|1 година)', 'caption=Валидност, mandatory, notNull');
        $this->FLD('key', 'varchar(16)', 'caption=Ключ, input=none');
        $this->FLD('mid', 'varchar(16)', 'caption=MID, input=none');
        $this->FLD('fileHnd', 'varchar(16)', 'caption=Файл, input=none');
    }
    
    
    /**
     * Екшън за експортиране
     */
    function act_Export()
    {
        Request::setProtected(array('classId', 'docId'));
        
        $classId = Request::get('classId', 'class(interface=doc_DocumentIntf)');
        $docId = Request::get('docId', 'int');
        
        expect($classId && $docId);
        
        $inst = cls::get($classId);
        $dRec = $inst->fetch($docId);
        
        expect($dRec);
        
        $inst->requireRightFor('exportdoc', $dRec);
        
        $form = $this->getForm();
        
        $form->title = "Генериране на линк за сваляне";
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array($inst, 'single', $docId);
        }
        
        // Възможните формати за експортване
        $exportFormats = $inst->getExportFormats();
        
        // Ако не може да се конвертира към PDF, да няма такъв избор
        if ($exportFormats['pdf']) {
            if (!doc_PdfCreator::canConvert()) {
                unset($exportFormats['pdf']);
            }
        }
        
        $form->setOptions('format', $exportFormats);
        
        $form->input();
        
        $form->setDefault('validity', 86400);
        
        // Ако линка ще сочи към частна мрежа, показваме предупреждение
        if (core_App::checkCurrentHostIsPrivate()) {
            
            $host = defined('BGERP_ABSOLUTE_HTTP_HOST') ? BGERP_ABSOLUTE_HTTP_HOST : $_SERVER['HTTP_HOST'];
            
            $form->info = "<div class='formNotice'>" . tr("Внимание|*! |Понеже линкът сочи към локален адрес|* ({$host}), |той няма да е достъпен от други компютри в Интернет|*.") . "</div>";
        }
        
        if ($form->isSubmitted()) {
            
            $rec = $form->rec;
            $rec->key = str::getRand('*********');
            
            // Записваме екшъна в зависимост от формата
            $format = strtolower($form->rec->format);
            
            if ($format == 'pdf') {
                $action = doclog_Documents::ACTION_PDF;
            } elseif ($format == 'csv') {
                $action = doclog_Documents::ACTION_EXPORT;
            } else {
                $action = doclog_Documents::ACTION_PRINT;
            }
            
            $opt = new stdClass();
            $opt->rec = new stdClass();
            
            // Вземаме `mid` за документа
            $opt->rec->__mid = $rec->mid = doclog_Documents::saveAction(
                array(
                    'action' => $action,
                    'containerId' => $dRec->containerId,
                    'threadId' => $dRec->threadId
                )
            );
            
            // Флъшваме екшъна, за да не се генерира друг mid при генериране на документа
            // Стойността на mid се предава в $opt->rec->__mid
            doclog_Documents::flushActions();
            
            $inst->logWrite('Генериране на линк за сваляне', $dRec->id);
            
            if ($format == 'pdf') {
                Mode::push('pdf', TRUE);
            }
            
            if ($format == 'pdf' || $format == 'html') {
                $html = $inst->getDocumentBody($dRec->id, 'xhtml', $opt);
            } else {
                expect(FALSE, $format);
            }
            
            $fileName = $inst->getHandle($dRec->id) . 'Export.' . $format;
            
            if ($format == 'pdf') {
                Mode::pop('pdf');
                
                //Манипулатора на новосъздадения pdf файл
                $rec->fileHnd = doc_PdfCreator::convert($html, $fileName);
            } else {
                
                // Вкарваме CSS-а инлайн
                $css = doc_PdfCreator::getCssStr($html);
                $html = doc_PdfCreator::removeFormAttr($html);
                $html = "<div class='wide'><div class='external'>" . $html . "</div></div>";
                $CssToInlineInst = cls::get(csstoinline_Setup::get('CONVERTER_CLASS'));
                $html = $CssToInlineInst->convert($html, $css);
                
                $rec->fileHnd = fileman::absorbStr($html, 'exportCsv', $fileName);
            }
            
            self::save($rec);
            
            $downloadUrl = self::getUrlForDownload($rec->key);
            $form->info .= "<b>" . tr('Линк|*: ') . "</b><span onmouseUp='selectInnerText(this);'>" . $downloadUrl . '</span>';
            
            $form->setField('format, validity', 'input=none');
			
            $form->toolbar->addBtn('Сваляне', $downloadUrl, "ef_icon = fileman/icons/16/{$format}.png, title=" . tr('Сваляне на документа'));
            $form->toolbar->addBtn('Затваряне', $retUrl, 'ef_icon = img/16/close-red.png, title=' . tr('Връщане към документа') . ', class=fright');
            
            $form->title = "Линк за сваляне";
        } else {
            $form->toolbar->addSbBtn('Генериране', 'save', 'ef_icon = img/16/world_link.png, title = ' . tr('Генериране на линк за сваляне'));
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title= ' . tr('Прекратяване на действията'));
        }
        
        $tpl = $form->renderHtml();
        
        // Ако е колаборатор, рендираме неговия врапер
        $isContractor = FALSE;
        if (core_Packs::isInstalled('colab')){
    		if (core_Users::haveRole('partner')) {
    		    
    			$inst->currentTab = 'Нишка';
    			plg_ProtoWrapper::changeWrapper($inst, 'cms_ExternalWrapper');
    			$tpl = $inst->renderWrapping($tpl);
    			
    			$isContractor = TRUE;
    		}
    	}
    	
    	if (!$isContractor) {
    	    $tpl = $inst->renderWrapping($tpl);
    	}
        
        return $tpl;
    }
    
    
    /**
     * Екшън за сваляне на документа
     */
    public function act_D()
    {
        $key = Request::get('id');
        
        $rec = self::fetch(array("#key = '[#1#]'", $key));
        
        expect($rec);
        
        $expire = dt::subtractSecs($rec->validity);
        
        if ($expire > $rec->createdOn) {
            redirect(array('Index'), FALSE, '|Изтекла или липсваща връзка', 'error');
        }
        
        // Ако се сваля от потребител различен от създателя
        $cu = core_Users::getCurrent();
        if ($cu <= 0 || ($rec->createdBy != core_Users::getCurrent())) {
            // Маркираме в документа, като виждане
            $logRec = doclog_Documents::fetchByMid($rec->mid);
            doclog_Documents::opened($logRec->containerId, $rec->mid);
        }
        
        self::logRead('Сваляне на документа', $rec->id);
        
        $res = Request::forward(array('fileman_Download', 'download', 'fh' => $rec->fileHnd, 'forceDownload' => TRUE));
    }
    
    
    /**
     * Връща линк за показване на документа във външната част
     * 
     * @param string $key
     * 
     * @return string
     */
    protected static function getUrlForDownload($key)
    {
        $url = toUrl(array('E', 'D', $key), 'absolute');
        
        return $url;
    }
}
