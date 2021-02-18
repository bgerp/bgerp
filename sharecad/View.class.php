<?php


/**
 * Разглеждане на DWG файлове
 *
 * @category  bgerp
 * @package   sharecad
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sharecad_View
{


    /**
     * Връща iframe елемтн
     *
     * @param $fileHnd
     * @param array $paramsArr
     *
     * @return false|string
     */
    public static function getFrame($fileHnd, $paramsArr = array())
    {
        if (!trim($fileHnd)) {

            return false;
        }

        $defPArr = array();
        $defPArr['frameBorder'] = '0';
        $defPArr['ALLOWTRANSPARENCY'] = 'true';
        $defPArr['class'] = 'cadframe';
        $defPArr['id'] = 'cadframe';
        $defPArr['scrolling'] = 'no';
        $defPArr['style'] = 'width: 100%; height: 100%;';

        foreach ($defPArr as $n => $v) {
            setIfNot($paramsArr[$n], $v);
        }

        $link = self::getLink($fileHnd);

        if (!$link) {

            return false;
        }

        return "<iframe src='//sharecad.org/cadframe/load?url={$link}' scrolling='{$paramsArr["scrolling"]}' frameBorder='{$paramsArr["frameBorder"]}' ALLOWTRANSPARENCY='{$paramsArr["ALLOWTRANSPARENCY"]}' class='{$paramsArr["class"]}' id='{$paramsArr["id"]}' style='{$paramsArr["style"]}'></iframe>";

    }


    /**
     * Връща линк
     *
     * @param $fileHnd
     * @return bool|string
     */
    protected static function getLink($fileHnd)
    {
        if (!trim($fileHnd)) {

            return false;
        }

        $fRec = fileman::fetchByFh($fileHnd);

        if (!$fRec) {

            return false;
        }

        return bgerp_F::getShortLink($fRec->fileHnd, $fRec->name);
    }

}
