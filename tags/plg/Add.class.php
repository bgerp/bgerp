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
                $row->_rowTools->addLink('Маркер', $url, array('ef_icon' => 'img/16/mark.png', 'title' => 'Добавяне на маркер', 'order' => 19.999));
            }
        }
    }


    /**
     * Изпълнява се след подготовката на единичния изглед
     * Подготвя иконата за единичния изглед
     *
     * @param core_Mvc $mvc
     * @param object   $res
     * @param object   $data
     */
    public function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        $tagsArr = tags_Logs::getTagsFor($mvc->getClassId(), $data->rec->id);

        if (!empty($tagsArr)) {
            $tags = '';

            foreach ($tagsArr as $tagArr) {
                $tags .= $tagArr['span'];
            }

            $data->row->DocumentSettingsLeft = new ET($data->row->DocumentSettingsLeft);
            $data->row->DocumentSettingsLeft->prepend("<span class='documentTags'>{$tags}</span>");
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (tags_Logs::haveRightFor('tag', $data->rec)) {
            $url = array('tags_Logs', 'Tag', 'id' => $data->rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Маркер', $url, 'ef_icon=img/16/mark.png, title=Добавяне на маркер, row=2, order=19.999');
        }
    }


    /**
     * След вземане на документа
     *
     * @param $mvc
     * @param $rowObj
     * @param $id
     */
    public static function on_AfterGetDocumentRow($mvc, &$rowObj, $id)
    {
        setIfNot($mvc->addTagsToSubtitle, 'after');
        setIfNot($mvc->showTagsName, true);

        if ($mvc->addTagsToSubtitle === false) {

            return ;
        }

        $rec = $mvc->fetchRec($id);
        if (!isset($rowObj)) {
            $rowObj = new stdClass();
        }

        if ($rec->id) {
            $tagsArr = tags_Logs::getTagsFor($mvc->getClassId(), $id);

            $sTitleStr = '';
            if (!empty($tagsArr)) {

                foreach ($tagsArr as $tArr) {
                    if ($mvc->showTagsName) {
                        $sTitleStr .= $tArr['span'];
                    } else {
                        $sTitleStr .= $tArr['spanNoName'];
                    }
                }
            }

            if ($rowObj->subTitle) {
                $rowObj->subTitle = "<span class='otherSubtitleStr'>{$rowObj->subTitle}</span>";
            }

            if ($mvc->addTagsToSubtitle == 'after') {
                $rowObj->subTitle = $rowObj->subTitle . $sTitleStr;
            } else {
                $rowObj->subTitle = $sTitleStr . $rowObj->subTitle;
            }
        }
    }
}
