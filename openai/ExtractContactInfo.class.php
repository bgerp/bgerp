<?php


/**
 * Извличане на контактни данни от имейлите
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class openai_ExtractContactInfo
{


    /**
     * Връща контактните данни от имейла
     *
     * @param $id
     * @param null|stdClass $cData
     * @param boolean|string $useCache
     *
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailData($id, &$cData = null, $useCache = true)
    {
        $rec = email_Incomings::fetchRec($id);

        expect($rec && $rec->emlFile);

        return self::extractEmailDataFromEml($rec->emlFile, $rec->lg, $cData, $useCache);
    }


    /**
     * Връща контактните данни от eml файла
     *
     * @param $emlFile
     * @param $lg
     * @param null|stdClass $cData
     * @param boolean|string $useCache
     *
     *
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailDataFromEml($emlFile, $lg = null, &$cData = null, $useCache = true)
    {
        $fRec = fileman::fetch($emlFile);

        expect($fRec);

        $source = fileman_Files::getContent($fRec->fileHnd);

        return self::extractEmailDataFromEmlFile($source, $lg, $cData, $useCache);
    }


    /**
     * Връща контактните данни от eml сорса
     *
     * @param $emlFile
     * @param $lg
     * @param null|stdClass $cData
     * @param boolean|string $useCache
     * @param boolean|string $fixTextPart
     *
     *
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailDataFromEmlFile($emlSource, $lg = null, &$cData = null, $useCache = true, $fixTextPart = true)
    {
        expect($emlSource);

        $mime = cls::get('email_Mime');

        $mime->parseAll($emlSource);

        if (!isset($lg)) {
            $lg = $mime->getLg();
        }

        if ($mime->textPart) {
            Mode::push('text', 'plain');
            $rt = new type_Richtext();
            $textPart = $rt->toHtml($mime->textPart);
            Mode::pop('text');
        } else {
            $textPart = $mime->justTextPart;
        }

        if ($fixTextPart) {
            // От текстовата част премахваме редовете, които започват с >
            $textPart = preg_replace('/^\s*(?:>).*$/mu', '', $textPart);

            // Премахваме текста след Links:
            $textPart = preg_replace('/^\s*(?:Links:).*/muis', '', $textPart);

            // Премахваме повтарящите се празни редове
            $textPart = preg_replace('/\n+\s*\n+/ui', "\n", $textPart);

            // Ако са зададени думи за игнориране
            $ignoreWords = openai_Setup::get('EMAIL_IGNORE_WORDS');
            if (trim($ignoreWords)) {
                $ignoreWords = explode("\n", $ignoreWords);
                foreach ($ignoreWords as $w) {
                    $w = preg_replace("/\r/", '', $w);
                    $w = preg_quote($w, '/');
                    $w = mb_strtolower($w);
                    $textPart = preg_replace("/{$w}/ui", " ", $textPart);
                }
            }
        }

        $placeArr = array();
        $placeArr['subject'] = $mime->getSubject();
        $placeArr['from'] = $mime->getFromName();
        $placeArr['fromEmail'] = $mime->getFromEmail();
        $placeArr['email'] = $textPart;

        return self::extractEmailDataFromText($placeArr, $lg, $cData, $useCache);
    }


    /**
     * Връща контактните данни от текстовата част
     *
     * @param $placeArr $placeArr
     * @param null|string $lg
     * @param null|stdClass $cData
     * @param boolean|string $useCache
     *
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailDataFromText($placeArr, $lg = null, &$cData = null, $useCache = true)
    {
        if (!is_object($cData)) {
            $cData = new stdClass();
        }

        if (!isset($lg)) {
            $lg = core_Lg::getCurrent();
        }

        $cDataKey = openai_Prompt::$extractContactDataEn;
        if ($lg == 'bg') {
            $cDataKey = openai_Prompt::$extractContactDataBg;
        }
        $text = openai_Prompt::getPromptBySystemId($cDataKey);

        expect($text);

        $ignoreStr = openai_Prompt::fetchField(array("#systemId = '[#1#]'", $cDataKey), 'ignoreWords');
        foreach (explode("\n", $ignoreStr) as $iStr) {
            $iStr = trim($iStr);
            $iStr = mb_strtolower($iStr);
            $ignoreArr[$iStr] = $iStr;
        }

        $mapArr = array();

        $textArr = explode("\n", $text);
        foreach ($textArr as $key => $tStr) {
            $tStr = trim($tStr);
            $mArr = explode('->', $tStr);

            $mArr[0] = trim($mArr[0]);
            $mArr[1] = trim($mArr[1]);

            if ($mArr[1]) {
                $mapArr[$mArr[0]] = $mArr[1];
                $textArr[$key] = $mArr[0];
            }
        }

        $text = implode("\n", $textArr);

        $text = new ET($text);
        $text->placeArray($placeArr);

        $aiModel = openai_Setup::get('API_MODEL_VERSION');

        if ($aiModel == 'GPT 3.5 TURBO') {
            $oRes =  openai_Api::getChatRes($text->getContent(), array(), $useCache);
        } elseif ($aiModel == 'TEXT DAVINCI 003') {
            $oRes =  openai_Api::getRes($text->getContent(), array(), $useCache);
        } else {
            expect(false, $aiModel);
        }

        if ($oRes === false) {

            return false;
        }

        $oResArr = explode("\n", $oRes);
        $newResArr = array();
        foreach ($oResArr as $oStr) {
            $oStr = trim($oStr);
            if (!strlen($oStr)) {

                continue;
            }

            $oStrArr = explode(":", $oStr, 2);

            $prompt = $oStrArr[0];
            $r = $oStrArr[1];

            $r = trim($r);

            if (!strlen($r)) {

                continue;
            }

            $rCompare = mb_strtolower($r);

            if (isset($ignoreArr[$rCompare])) {

                continue;
            }

            if ($mp = $mapArr[$prompt]) {
                $cData->{$mp} = $r;
            } else {
                $promptLower = mb_strtolower($prompt);
                $cData->{$promptLower} = $r;
            }

            $newResArr[] = $prompt . ': ' . $r;
        }

        if ($lg == 'bg') {
            if (!isset($cData->country)) {
                $cData->country = 'България';
            }
        }

        if ($cData->country == 'CN') {
            $cData->country = 'China';
        }

        if ($cData->country == 'China CN') {
            $cData->country = 'China';
        }

        return implode("\n", $newResArr);
    }
}
