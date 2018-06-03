<?php


/**
 * Експортиране и редактиране на HTML
 * 
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class export_HtmlEditor extends core_Mvc
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Редактиране на HTML документ";
    
    
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
        if (!core_Packs::isInstalled('tinymce')) return FALSE;
        
        if (haveRole('partner')) {
            if (!cls::load($clsId, TRUE)) return FALSE;
            
            $clsInst = cls::get($clsId);
            
            if (!($clsInst instanceof sales_Quotations)) return FALSE;
        }
        
        $hInst = cls::get('export_Html');
        
        return $hInst->canUseExport($clsId, $objId);
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
        
        return 'HTML редактор';
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
        $hInst = cls::get('export_Html');
        
        Mode::push('noBlank', TRUE);
        
        $fHnd = $hInst->makeExport($form, $clsId, $objId);
        
        Mode::pop('noBlank');
        
        if ($fHnd) {
            
            return new Redirect(array('fileman_webdrv_Webpage', 'editHtml', 'fileHnd' => $fHnd, 'ret_url' => TRUE));
        }
        
        return $fHnd;
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
        
        return NULL;
    }
}
