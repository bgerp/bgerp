<?php


/**
 * Експортиране на документи като линкове
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
class export_Link extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като линк';
    
    
    public $interfaces = 'export_ExportTypeIntf';
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public function canUseExport($clsId, $objId)
    {
        return export_Export::canUseExport($clsId, $objId);
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return 'Интернет линк';
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
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
        
        $mid = doclog_Documents::saveAction(
                        array(
                            'action' => doclog_Documents::ACTION_LINK,
                            'containerId' => $cRec->containerId,
                            'threadId' => $cRec->threadId,
                            'data' => (object) array('lg' => core_Lg::getCurrent())
                        )
                );
        
        // Флъшваме екшъна за да се запише в модела
        doclog_Documents::flushActions();
        
        $externalLink = bgerp_plg_Blank::getUrlForShow($cRec->containerId, $mid);
        
        $externalLink = '<b>' . tr('Линк|*: ') . "</b><span onmouseUp='selectInnerText(this);'>" . $externalLink . '</span>';
        
        // Ако линка ще сочи към частна мрежа, показваме предупреждение
        if (core_App::checkCurrentHostIsPrivate()) {
            $host = defined('BGERP_ABSOLUTE_HTTP_HOST') ? BGERP_ABSOLUTE_HTTP_HOST : $_SERVER['HTTP_HOST'];
            
            $form->info = "<div class='formNotice'>" . tr("Внимание|*! |Понеже линкът сочи към локален адрес|* ({$host}), |той няма да е достъпен от други компютри в Интернет|*.") . '</div>';
        }
        
        $form->info .= $externalLink;
        
        $clsInst->logWrite('Генериране на линк за сваляне', $objId);
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
    }
}
