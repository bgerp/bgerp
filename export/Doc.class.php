<?php


/**
 * Експортиране на детайлите на документив в doc формат
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class export_Doc extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като DOC';
    
    
    public $interfaces = 'export_ExportTypeIntf';
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public function canUseExport($clsId, $objId)
    {
        // @todo - remove
        if (!haveRole('debug')) {
            
            return ;
        }
        
        return export_Export::canUseExport($clsId, $objId);
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return 'DOC файл';
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
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
                                        'action' => doclog_Documents::ACTION_PRINT,
                                        'containerId' => $cRec->containerId,
                                        'threadId' => $cRec->threadId,
                                    )
                                );
            
            // Флъшваме екшъна за да се запише в модела
            doclog_Documents::flushActions();
        }
        
        $html = $clsInst->getDocumentBody($cRec->id, 'xhtml', $opt);
        
        $r = str::getRand();
        $html = '<div id="begin' . $r . '">' . $html . '<div id="end' . $r . '">';
        
        // Вкарваме CSS-а инлайн
        $css = doc_PdfCreator::getCssStr($html);
        $html = doc_PdfCreator::removeFormAttr($html);
        $html = "<div class='wide'><div class='external'>" . $html . '</div></div>';
        $CssToInlineInst = cls::get(csstoinline_Setup::get('CONVERTER_CLASS'));
        $html = $CssToInlineInst->convert($html, $css);
        
        $html = str::cut($html, '<div id="begin' . $r . '">', '<div id="end' . $r . '">');
        
        $fileName = $clsInst->getHandle($cRec->id) . '_Export.html';
        
        $fileHnd = docoffice_Office::htmlToDoc($html, $fileName, 'exportFiles');
        
        $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/doc.png, title=Сваляне на документа');
        
        $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
        
        $clsInst->logWrite('Генериране на DOC', $objId);
        
        return $fileHnd;
    }
    
    
    /**
     * Функция, която получава управлението след конвертирането на офис докуемнта към PDF
     *
     * @param object $script - Обект със стойности
     *
     * @return bool TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
     *              и записа от таблицата fconv_Process
     *
     * @access protected
     */
    public static function afterConvertToXls($script)
    {
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        $error = fileman_Indexes::haveErrors($script->outFilePath, $params);
        
        // Отключваме предишния процес
        core_Locks::release($params['lockId']);
        
        // Да не се изтрива директрояита, след като качим файла
        return false;
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param int    $clsId
     * @param int    $objId
     * @param string $mid
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        // @todo - remove
        if (!isDebug()) {
            
            return ;
        }
        
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        
        $link = ht::createLink('DOC', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/doc.png'));
        
        return $link;
    }
}
