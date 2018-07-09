<?php


/**
 * Клас 'uiext_plg_DetailLabels' добавящ тагове на редовете на детайл
 *
 *
 * @category  bgerp
 * @package   uiext
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class uiext_plg_DetailLabels extends core_Plugin
{
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $masterRec = $data->masterData->rec;
        
        $data->hideListFieldsIfEmpty['_tagField'] = '_tagField';
        uiext_Labels::showLabels($mvc, $masterRec->containerId, $data->recs, $data->rows, $data->listFields, $mvc->hashField, 'Таг', $tpl, $mvc);
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        uiext_Labels::enable($tpl);
    }
}
