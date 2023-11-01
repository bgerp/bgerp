<?php


/**
 * Кустом клас за рендиране на хранителни алергени в етикети
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_TemplateRendererIntf
 *
 */
class cond_iface_AllergensTemplateRendered extends core_BaseClass
{
    /**
     * Инстанция на класа
     */
    public $class;


    /**
     * Поддържани интерфейси
     *
     * var string|array
     */
    public $interfaces = 'label_TemplateRendererIntf';


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
        if($labelDataArr['АЛЕРГЕНИ']){
            $tpl = new core_ET("");
            $allergenNums = explode(',', $labelDataArr['АЛЕРГЕНИ']);
            foreach ($allergenNums as $num){
                $numTrimmed = trim($num);
                $iconImg = ht::createImg(array('class' => 'icons','src' => sbf("cond/img/{$numTrimmed}.png", '')));
                $tpl->append($iconImg);
            }

            $labelDataArr['ALLERGENS_IMG'] = $tpl->getContent();
        }
    }
}