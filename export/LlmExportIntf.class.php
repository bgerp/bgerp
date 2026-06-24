<?php


/**
 * Интерфейс за документи, които могат да се експортират в текстов формат за ИИ
 *
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Mustafa Mustafov<mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за документи, които могат да се експортират в текстов формат за ИИ
 */
class export_LlmExportIntf
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
    public function getLlmContent($id, $params = array(), $forLlm = true)
    {
        return $this->class->getLlmContent($id, $params, $forLlm);
    }
}