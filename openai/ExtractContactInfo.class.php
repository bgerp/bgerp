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
     *
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailData($id)
    {
        $rec = email_Incomings::fetchRec($id);

        expect($rec && $rec->emlFile);

        return self::extractEmailDataFromEml($rec->emlFile, $rec->lg);
    }


    /**
     * Връща контактните данни от eml файла
     *
     * @param $emlFile
     * @param $lg
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailDataFromEml($emlFile, $lg = null)
    {
        $fRec = fileman::fetch($emlFile);

        expect($fRec);

        $source = fileman_Files::getContent($fRec->fileHnd);

        return self::extractEmailDataFromEmlFile($source, $lg);
    }


    /**
     * Връща контактните данни от eml сорса
     *
     * @param $emlFile
     * @param $lg
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailDataFromEmlFile($emlSource, $lg = null)
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

        $placeArr = array();
        $placeArr['subject'] = $mime->getSubject();
        $placeArr['from'] = $mime->getFromName();
        $placeArr['fromEmail'] = $mime->getFromEmail();
        $placeArr['email'] = $textPart;

        return self::extractEmailDataFromText($placeArr, $lg);
    }


    /**
     * Връща контактните данни от текстовата част
     *
     * @param $placeArr $placeArr
     * @param null|string $lg
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailDataFromText($placeArr, $lg = null)
    {
        if (!isset($lg)) {
            $lg = core_Lg::getCurrent();
        }

        if ($lg == 'bg') {
            $text = openai_Prompt::getPromptBySystemId(openai_Prompt::$extractContactDataBg);
        } else {
            $text = openai_Prompt::getPromptBySystemId(openai_Prompt::$extractContactDataEn);
        }

        expect($text);

        $text = new ET($text);
        $text->placeArray($placeArr);

        return openai_Api::getRes($text->getContent());
    }
}
