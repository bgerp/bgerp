<?php


/**
 * Дравей за работа с GPT 3.5 Turbo
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     GPT 3.5 Turbo
 */
class openai_GPT35Turbo extends openai_Api
{


    /**
     * Праща заявка и връща резултата чрез gpt-3.5-turbo
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
    public function getRes($prompt = null, $pArr = array(), $useCache = true, $index = 0, &$cKey = null)
    {
        self::setDefaultParams($pArr);
        setIfNot($pArr['__endpoint'], 'chat/completions');
        setIfNot($pArr['model'], 'gpt-3.5-turbo');

        if (isset($prompt)) {
            $pArr['messages'] = array(array('role' => 'user', 'content' => $prompt));
        }

        expect($pArr['messages'], $pArr);

        $resObj = self::execCurl($pArr, $useCache, $cKey);

        return $resObj->choices[$index]->message ? $resObj->choices[$index]->message->content : false;
    }
}
