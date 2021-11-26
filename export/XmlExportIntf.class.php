<?php


/**
 * Интерфейс за документи, които могат да се експортират в xml формат
 *
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за документи, които могат да се експортират в xml формат
 */
class export_XmlExportIntf
{
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;


    /**
     * Експортира документа в xml формат
     *
     * @see export_Xml
     * @param mixed $id
     * @return core_ET $tpl
     */
    public function exportAsXml($id)
    {
        return $this->class->exportAsXml($id);
    }
}