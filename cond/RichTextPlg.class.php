<?php


/**
 * Пасаж
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_RichTextPlg extends core_Plugin
{

    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'passage_RichTextPlg';



    /**
     * Добавя бутон за качване на документ
     *
     * @param core_Mvc              $mvc
     * @param core_ObjectCollection $toolbarArr
     * @param array                 $attr
     */
    public static function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // Ако има права за добавяне
        if (cond_Texts::haveRightFor('list') && $mvc->params['passage']) {
            // id
            ht::setUniqId($attr);
            $id = $attr['id'];

            // Име на функцията и на прозореца
            $callbackName = 'placePassage_' . $id;

            // Ако е мобилен/тесем режим
            if (Mode::is('screenMode', 'narrow')) {
                // Парамтери към отварянето на прозореца
                $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            } else {
                $args = 'width=600,height=600,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }

            Request::setProtected('groupName');

            // URL за добавяне на документи
            $url = toUrl(array('cond_Texts', 'Dialog', 'callback' => $callbackName, 'groupName' => $mvc->params['passage']));

            // JS фунцкията, която отваря прозореца
            $js = "openWindow('{$url}', '{$callbackName}', '{$args}'); return false;";

            // Бутон за отвяряне на прозореца
            $addPassage = new ET("<a class=rtbutton title='" . tr('Добавяне на пасаж ') . "' onclick=\"{$js}\">" . tr('Пасаж') . '</a>');

            // JS функцията
            $callback = "function {$callbackName}(passage) {
                var ta = get$('{$id}');
                rp(passage, ta, 1);
                return false;
            }";

            // Добавяме скрипта
            $addPassage->appendOnce($callback, 'SCRIPTS');

            // Добавяне в групата за добавяне на документ
            $toolbarArr->add($addPassage, 'filesAndDoc', 1000.056);
        }
    }
}
