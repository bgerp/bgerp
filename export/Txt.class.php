<?php


/**
 * Експортиране на документи като текствов файл
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class export_Txt extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като текстов файл';


    /**
     * Поддържани интерфейси
     */
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
        $res = export_Export::canUseExport($clsId, $objId);
        if($res){
            if(!cls::haveInterface('export_TxtExportIntf', $clsId)) return false;
        }

        return $res;
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
        return 'Текстов файл';
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
        $Cls = cls::get($clsId);
        $Impl = cls::getInterface('export_TxtExportIntf', $Cls);
        $txtContent = $Impl->getTxtContent($objId);

        $fileHnd = null;
        if (!empty($txtContent)) {
            $fileName = $Cls->getHandle($objId) . '_Export.txt';
            $fileHnd = fileman::absorbStr($txtContent, 'exportFiles', $fileName);
        }

        if ($fileHnd) {
            $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/txt.png, title=Сваляне на документа');
            $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
        } else {
            $form->info .= "<div class='formNotice'>" . tr('Няма данни за експорт|*.') . '</div>';
        }

        $Cls->logWrite('Генериране на Txt', $objId);

        return $fileHnd;
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
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        $link = ht::createLink('TXT', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/xml.png'));

        return $link;
    }


    /**
     * Добавя параметри към експорта на формата
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function addParamFields($form, $clsId, $objId)
    {
    }
}
