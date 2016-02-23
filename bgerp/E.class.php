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
        
        $form->title = "Експортиране на документ";
        
        $retUrl = getRetUrl();
        
        if (!$retUrl) {
            $retUrl = array($inst, 'single', $docId);
        }
        
        // Възможните формати за експортване
        $exportFormats = $inst->getExportFormats();
        $form->setOptions('format', $exportFormats);
        
        $form->input();
        
        $form->setDefault('validity', 86400);
        
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
            
            $inst->logWrite('Експортиране', $dRec->id);
            
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
            $form->info = "<b>" . tr('URL за сваляне|*: ') . "</b>" . $downloadUrl;
            
            $form->setField('format, validity', 'input=none');
			
            $form->toolbar->addBtn('Към документа', $retUrl, 'ef_icon = img/16/back16.png, title=Връщане към документа');
        } else {
            $form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png, title = Избор');
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
        }
        
        $tpl = $inst->renderWrapping($form->renderHtml());
        
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
        
        // Маркираме в документа, като виждане
        $logRec = doclog_Documents::fetchByMid($rec->mid);
        doclog_Documents::opened($logRec->containerId, $rec->mid);
        
        $res = Request::forward(array('fileman_Download', 'download', 'fh' => $rec->fileHnd, 'forceDownload' => TRUE));
    }
    
    
    /**
     * Връща линк за показване на документа във външната част
     * 
     * @param integer $cid
     * @param string $mid
     * 
     * @return string
     */
    public static function getUrlForDownload($key)
    {
        $url = toUrl(array('E', 'D', $key), 'absolute');
        
        return $url;
    }
}
