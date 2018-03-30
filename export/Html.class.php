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
class export_Html extends core_Mvc
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Експортиране на документ като HTML";
    
    
    /**
     *  
     */
    public $interfaces = 'export_ExportTypeIntf';
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     * 
     * @param integer $clsId
     * @param integer $objId
     * 
     * @return boolean
     */
    function canUseExport($clsId, $objId)
    {
        
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
    function getExportTitle($clsId, $objId)
    {
        
        return 'HTML файл';
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     * 
     * @param core_Form $form
     * @param integer $clsId
     * @param integer|stdClass $objId
     *
     * @return NULL|string
     */
    function makeExport($form, $clsId, $objId)
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
                                    'action'      => doclog_Documents::ACTION_PRINT,
                                    'containerId' => $cRec->containerId,
                                    'threadId'    => $cRec->threadId,
                            )
                    );
            
            // Флъшваме екшъна за да се запише в модела
            doclog_Documents::flushActions();
        }
        
        $html = $clsInst->getDocumentBody($cRec->id, 'xhtml', $opt);
        
        $fileName = $clsInst->getHandle($cRec->id) . '_Export.html';
        
        // Вкарваме CSS-а инлайн
        $css = doc_PdfCreator::getCssStr($html);
        $html = doc_PdfCreator::removeFormAttr($html);
        $html = "<div class='wide'><div class='external'>" . $html . "</div></div>";
        $CssToInlineInst = cls::get(csstoinline_Setup::get('CONVERTER_CLASS'));
        $html = $CssToInlineInst->convert($html, $css);
        
        $fileHnd = fileman::absorbStr($html, 'exportCsv', $fileName);
        
        $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => TRUE), "ef_icon = fileman/icons/16/html.png, title=" . tr('Сваляне на документа'));
        
        // Ако линка ще сочи към частна мрежа, показваме предупреждение
        if (core_App::checkCurrentHostIsPrivate()) {
            
            $host = defined('BGERP_ABSOLUTE_HTTP_HOST') ? BGERP_ABSOLUTE_HTTP_HOST : $_SERVER['HTTP_HOST'];
            
            $form->info = "<div class='formNotice'>" . tr("Внимание|*! |Понеже линкът сочи към локален адрес|* ({$host}), |той няма да е достъпен от други компютри в Интернет|*.") . "</div>";
        }
        
        $form->info .= "<b>" . tr('Файл|*: ') . "</b>" . fileman::getLink($fileHnd);
        
        $clsInst->logWrite('Генериране на HTML', $objId);
        
        return $fileHnd;
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param integer $clsId
     * @param integer $objId
     * @param string $mid
     *
     * @return core_ET|NULL
     */
    function getExternalExportLink($clsId, $objId, $mid)
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        
        $link = ht::createLink('HTML', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => TRUE), NULL, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/html.png'));
        
        return $link;
    }
}
