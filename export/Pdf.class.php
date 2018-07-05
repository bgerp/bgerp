<?php


/**
 * Експортиране на документи като PDF
 *
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class export_Pdf extends core_Mvc
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като PDF';
    
    
    
    public $interfaces = 'export_ExportTypeIntf';
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param integer $clsId
     * @param integer $objId
     *
     * @return boolean
     */
    public function canUseExport($clsId, $objId)
    {
        static $canConvert;
        if (!isset($canConvert)) {
            $canConvert = doc_PdfCreator::canConvert();
            if (!$canConvert) {
                return false;
            }
        }
        
        return export_Export::canUseExport($clsId, $objId);
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param integer $clsId
     * @param integer $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return 'PDF файл';
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param core_Form        $form
     * @param integer          $clsId
     * @param integer|stdClass $objId
     *
     * @return NULL|string
     */
    public function makeExport($form, $clsId, $objId)
    {
        $clsInst = cls::get($clsId);
        $cRec = $clsInst->fetchRec($objId);
        
        $opt = new stdClass();
        $opt->rec = new stdClass();
        
        if ($cRec->__mid) {
            $opt->rec->__mid = $cRec->__mid;
        } else {
            $opt->rec->__mid = doclog_Documents::saveAction(
                            array(
                                    'action' => doclog_Documents::ACTION_PDF,
                                    'containerId' => $cRec->containerId,
                                    'threadId' => $cRec->threadId,
                            )
                    );
            // Флъшваме екшъна за да се запише в модела
            doclog_Documents::flushActions();
        }
        
        Mode::push('pdf', true);
        
        $html = $clsInst->getDocumentBody($cRec->id, 'xhtml', $opt);
        
        Mode::pop('pdf');
        
        $fileName = $clsInst->getHandle($cRec->id) . '_Export.pdf';
        
        //Манипулатора на новосъздадения pdf файл
        $fileHnd = doc_PdfCreator::convert($html, $fileName);
        
        $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/pdf.png, title=Сваляне на документа');
        
        $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
        
        $clsInst->logWrite('Генериране на PDF', $objId);
        
        return $fileHnd;
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param integer $clsId
     * @param integer $objId
     * @param string  $mid
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        
        $link = ht::createLink('PDF', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/pdf.png'));
        
        return $link;
    }
}
