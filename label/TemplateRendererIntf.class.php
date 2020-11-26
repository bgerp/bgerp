<?php


/**
 * Интерфейс за класове обработващи
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_TemplateRendererIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     *  Модифициране на рендирането на данните на етикета
     *
     * @param int $templateId
     * @param string $labelString
     * @param array $placeholderArr
     * @param array $labelDataArr
     *
     * @return void
     */
    public function modifyLabelData($templateId, &$labelString, &$placeholderArr, &$labelDataArr)
    {
        return $this->class->modifyLabelData($templateId, $labelString, $placeholderArr, $labelDataArr);
    }
}