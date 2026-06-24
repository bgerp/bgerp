<?php


/**
 * Плъгин позволяващ експорт на документи в текстов вид за ЛЛМ
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author   Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_LlmExportable extends core_Plugin
{

    /**
     * Дефолтна имплементация на функцията `getLlmContent`
     *
     * @param core_Mvc $mvc
     * @param null|string $text
     * @param int $id
     * @param array $params
     * @param bool $forLlm
     * @return string|void
     */
    public static function on_AfterGetLlmContent($mvc, &$text, $id, $params = array(), $forLlm = false)
    {
        if($forLlm){
            $params = array('addAttachedTextFilesAsRichText' => true);
        }
        $Impl = cls::getInterface('export_TxtExportIntf', $mvc);
        $text = $Impl->getTxtContent($id, $params);
    }
}