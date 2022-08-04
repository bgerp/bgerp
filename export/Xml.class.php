<?php


/**
 * Експортиране на документи като XML
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class export_Xml extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като XML';


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
            if(!cls::haveInterface('export_XmlExportIntf', $clsId)){

                return false;
            }
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
        return 'XML файл';
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
        $Impl = cls::getInterface('export_XmlExportIntf', $Cls);
        $xmlBody = $Impl->exportAsXml($objId);

        $fileHnd = null;
        if (!empty($xmlBody)) {
            $fileName = $Cls->getHandle($clsId) . '_Export.xml';
            $fileHnd = fileman::absorbStr($xmlBody, 'exportFiles', $fileName);
        }

        if ($fileHnd) {
            $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/csv.png, title=Сваляне на документа');
            $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
        } else {
            $form->info .= "<div class='formNotice'>" . tr('Няма данни за експорт|*.') . '</div>';
        }

        $Cls->logWrite('Генериране на XML', $objId);

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
        $link = ht::createLink('XML', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/xml.png'));

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
