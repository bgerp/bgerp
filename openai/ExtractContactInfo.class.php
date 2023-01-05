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
     * Въпросите за полетата на имейлите
     */
    static $extractQuestions = 'Името на фирмата, Името на лицето, Адреса за доставка, Имейл, Телефон, Фейсбук, Туитър, Данъчен номер';


    /**
     * Въпросите за полетата на имейлите EN
     */
    static $extractQuestionsEn = 'Person name, Person gender, Job position, Mobile, Company, Country, Postal code, Place,
                                  Street address, Company telephone, Web site, VAT number, Social media';


    /**
     * Въпроса за извличане
     */
    static $extractText = 'Извлечи следните данни от по-долния имейл';


    /**
     * Въпроса за извличане ЕН
     */
    static $extractTextEn = 'Please extract contact data from following email';


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

        $subject = $lg == 'bg' ? 'Относно' : 'Subject';
        $from = $lg == 'bg' ? 'От' : 'From';

        $textPart = $subject . ': ' . $mime->getSubject() . "\n" . $from . ': ' . $mime->getFromName() . "\n" .  $textPart;

        return self::extractEmailDataFromText($textPart, $lg);
    }


    /**
     * Връща контактните данни от текстовата част
     *
     * @param $emlFile
     * @param $lg
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function extractEmailDataFromText($text, $lg = null)
    {
        expect($text);

        if (!isset($lg)) {
            $lg = core_Lg::getCurrent();
        }

        if ($lg == 'bg') {
            $qArr = explode(',', self::$extractQuestions);
            $qStr = self::$extractText;
        } else {
            $qArr = explode(',', self::$extractQuestionsEn);
            $qStr = self::$extractTextEn;
        }

        $qStr = trim($qStr);
        $qStr .= ': ';
        foreach ($qArr as $q) {
            $q = trim($q);

            $qStr .= "\n";

            $qStr .= $q . ': ';
        }

        $qStr .= "\n\n";

        $qStr .= $text;

        return openai_Api::getRes($qStr);
    }
}
