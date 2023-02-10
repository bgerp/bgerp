<?php


/**
 * Плъгин за превеждане на думите в core_Lg
 *
 * @category  bgerp
 * @package   google
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deepl_plugins_LgTranslate extends core_Plugin
{


    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        if ($form->rec->lg != 'bg') {
            if ($form->rec->translated && $form->rec->lg) {
                if (core_Lg::prepareKey($form->rec->translated) == $form->rec->kstring) {
                    $tr = deepl_Api::translate($form->rec->translated, $form->rec->lg, 'bg');
                    $form->info .= tr("Оригинал") . ': ' . $form->rec->translated;
                    $form->info .= "<br>";
                    $form->info .= tr("Превод") . ': ' . $tr;
//                    $form->setSuggestions('translated', $tr);
                    if (!$form->isSubmitted()) {
                        $form->setDefault('translated', $tr);
                        $form->rec->translated = $tr;
                    }
                }
            }
        }
    }
}
