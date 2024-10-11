<?php


/**
 * Дравей за работа с TEXT DAVINCI 003
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     TEXT DAVINCI 003
 */
class openai_TextDavinci003 extends openai_Api
{


    /**
     * Праща заявка и връща резултата чрез text-davinci-003
     *
     * @param null|string $prompt - стойността, която се подава на `messages` => `content` с `role` => `user`
     * @param array $pArr
     * ['messages'] - въпорсът, който задаваме
     * ['messages'][['role' => 'user', 'content' => 'Hello']]
     * ['messages'][['role' => 'system', 'content' => 'You are a helpful assistant.']]
     * ['messages'][['role' => 'assistant', 'content' => 'Prev answer']]
     * ['__convertRes']
     * ['response_format']
     * @param boolean|string $useCache
     * @param interger $index
     * @param null|string $cKey
     *
     * @trows openai_Exception
     *
     * @return string|false
     */
    public function getRes($prompt = null, $pArr = array(), $useCache = true, $index = 0, &$cKey = null, $timeout = 12)
    {
        self::setDefaultParams($pArr);
        setIfNot($pArr['__endpoint'], 'completions');
        setIfNot($pArr['model'], 'text-davinci-003');

        if (isset($prompt)) {
            $pArr['prompt'] = $prompt;
        }

        expect($pArr['prompt'], $pArr);

        $resObj = self::execCurl($pArr, $useCache, $cKey, $timeout);

        return $resObj->choices[$index]->text ? $resObj->choices[$index]->text : false;
    }
}
