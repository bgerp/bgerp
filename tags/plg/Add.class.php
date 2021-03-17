<?php


/**
 * Плъгин за добавяне на тагове
 *
 * @category  bgerp
 * @package   tags
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tags_plg_Add extends core_Plugin
{



    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($fields['-list']) {
            core_RowToolbar::createIfNotExists($row->_rowTools);

            if (tags_Logs::haveRightFor('tag', $rec)) {
                $url = array('tags_Logs', 'Tag', 'id' => $rec->containerId, 'ret_url' => true);
                $row->_rowTools->addLink('Маркер', $url, array('ef_icon' => 'img/16/mark.png', 'title' => 'Добавяне на маркер'));
            }
        }

        $tagsArr = tags_Logs::getTagsFor($mvc->getClassId(), $rec->id);

        if (!empty($tagsArr)) {
            $tags = implode(', ', $tagsArr);
            $row->DocumentSettingsLeft = new ET($row->DocumentSettingsLeft);
            $row->DocumentSettingsLeft->prepend("<span class='documentTags'>{$tags}</span>");
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (tags_Logs::haveRightFor('tag', $data->rec)) {
            $url = array('tags_Logs', 'Tag', 'id' => $data->rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Маркер', $url, 'ef_icon=img/16/mark.png, title=Добавяне на маркер, row=2');
        }
    }
}
