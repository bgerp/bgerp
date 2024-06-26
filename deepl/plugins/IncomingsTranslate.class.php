<?php


/**
 * Плъгин за превеждане на входящите имейли
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
class deepl_plugins_IncomingsTranslate extends core_Plugin
{


    /**
     *
     *
     * @param $mvc
     * @param $row
     * @param $rec
     * @param $fields
     * @return void
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        $translateLg = email_Setup::get('INCOMINGS_TRANSLATE_LG');
        $translateLgArr = type_Keylist::toArray($translateLg);
        
        $translateLgCodeArr = array();
        foreach ($translateLgArr as $lgId) {
            $lgCode = drdata_Languages::fetchField($lgId, 'code');
            $lgCode = strtolower($lgCode);
            $translateLgCodeArr[$lgCode] = $lgCode;
        }

        $rLg = strtolower($rec->lg);

        $isGoodToTranslate = (boolean)($rLg != deepl_Setup::get('LANG'));

        if (empty($translateLgCodeArr) || $translateLgCodeArr[$rLg]) {
            if (!(Mode::is('text', 'xhtml') && !Mode::is('printing')) && !Mode::is('text', 'plain')
                && $fields['-single'] && trim($row->textPart) && $isGoodToTranslate) {

                if ($mvc->haveRightFor('single', $rec->id)) {
                    $tr = Request::get('tr');

                    $cTextSubject = null;
                    $cText = null;

                    if (!isset($tr)) {
                        $handle = $mvc->className . '|' . $rec->id . '|' . core_Users::getCurrent();

                        $cText = core_Cache::get('deepltranslate', $handle);
                        $cTextSubject = core_Cache::get('deepltranslateSubject', $handle);
                    }

                    if (!is_object($row->textPart)) {
                        $row->textPart = new ET($row->textPart);
                    }
                    if (!$cTextSubject) {
                        $cTextSubject = $row->subject;
                    }

                    $row->subject = new ET($cTextSubject);

                    if (!$cText) {
                        if (!isset($tr) || ($tr == '0')) {
                            self::addTranslateHeader($mvc, $row->textPart, $rec->id);
                        }
                    } else {
                        $row->textPart = new ET($cText);
                    }

                    if (!Request::get('ajax_mode')) {

                        $row->textPart->prepend("<div id='deepltranslate{$rec->id}'>");
                        $row->textPart->append("</div>");

                        $row->subject->prepend("<span id='deepltranslateSubject{$rec->id}'>");
                        $row->subject->append("</span>");
                    }
                }
            }
        }
    }


    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == 'deepltranslate') {
            expect(Request::get('ajax_mode'));

            $id = Request::get('id', 'int');

            expect($id);

            $rec = $mvc->fetch($id);

            expect($rec);

            $mvc->requireRightFor('single', $id);

            $row = $mvc->recToVerbal($rec, array('textPart', 'subject', '-single'));

            $textPart = $row->textPart;
            $subject = $row->subject;

            if (is_object($textPart)) {
                $textPart = $textPart->getContent();
            }

            if (is_object($subject)) {
                $subject = $subject->getContent();
            }

            $handle = $mvc->className . '|' . $rec->id . '|' . core_Users::getCurrent();

            if (Request::get('tr')) {
                if ($cText = core_Cache::get('deepltranslate', $handle)) {
                    $textPart = $cText;

                    $subjectC = core_Cache::get('deepltranslateSubject', $handle);
                    if ($subjectC) {
                        $subject = $subjectC;
                    }
                } else {
                    $textPart = deepl_Api::translate($textPart);
                    $subject = deepl_Api::translate($subject);

                    $textPart = new ET($textPart);
                    self::addTranslateHeader($mvc, $textPart, $rec->id);
                    $textPart = $textPart->getContent();

                    core_Cache::set('deepltranslate', $handle, $textPart, 100);
                    core_Cache::set('deepltranslateSubject', $handle, $subject, 100);
                }
            } else {
                core_Cache::remove('deepltranslate', $handle);
                core_Cache::remove('deepltranslateSubject', $handle);
            }

            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'deepltranslate' . $rec->id, 'html' => $textPart, 'replace' => true);

            $resObjSubject = new stdClass();
            $resObjSubject->func = 'html';
            $resObjSubject->arg = array('id' => 'deepltranslateSubject' . $rec->id, 'html' => $subject, 'replace' => true);

            $res = array($resObj, $resObjSubject);

            return false;
        }
    }


    /**
     * Помощна фунцикция за добавяне на линка за превеждане
     *
     * @param core_Mvc $mvc
     * @param core_ET $textPart
     * @param integer $id
     */
    protected static function addTranslateHeader($mvc, &$textPart, $id)
    {
        $tr = Request::get('tr');

        $url = array($mvc, 'deepltranslate', $id, 'tr' => !$tr ? 1 : 0);

        $text = $tr ? 'Оригинал' : 'Превод';

        $link = ht::createLink(tr($text), $url, false,
            array("style" => 'position: relative; float: right;', 'onclick' => 'return startUrlFromDataAttr(this, true);', 'data-url' => toUrl($url, 'local')));

        $textPart->prepend($link);
    }
}
