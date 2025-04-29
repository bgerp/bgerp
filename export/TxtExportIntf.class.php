<?php


/**
 * Интерфейс за документи, които могат да се експортират в текстов формат
 *
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за документи, които могат да се експортират в текстов формат
 */
class export_TxtExportIntf
{
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;


    /**
     * Експортира документа в текстов формат
     *
     * @see export_Xml
     * @param mixed $id
     * @param array $params
     * @return string $tpl
     */
    public function getTxtContent($id, $params = array())
    {
        return $this->class->getTxtContent($id, $params);
    }
}