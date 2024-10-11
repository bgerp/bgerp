<?php


/**
 * Дравей за работа с GPT 4
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     GPT 4 Turbo
 */
class openai_GPT4Turbo extends openai_GPT35Turbo
{


    /**
     * Праща заявка и връща резултата чрез gpt-4-turbo
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
        setIfNot($pArr['model'], 'gpt-4-1106-preview');
        setIfNot($pArr['__convertRes'], false);

        setIfNot($pArr['response_format'], (object)array('type' => 'json_object'));

        self::setDefaultParams($pArr);
        setIfNot($pArr['__endpoint'], 'chat/completions');

        if (isset($prompt)) {
            $pArr['messages'] = array(array('role' => 'user', 'content' => $prompt));
        }

        expect($pArr['messages'], $pArr);

        if ($pArr['response_format']->type == 'json_object') {
            $pArr['messages'][] = array('role' => 'system', 'content' => 'You are a helpful assistant designed to output JSON.');
        }

        $resObj = self::execCurl($pArr, $useCache, $cKey, $timeout);

        $jsonContent = $resObj->choices[$index]->message ? $resObj->choices[$index]->message->content : false;

        if ($jsonContent && $pArr['__convertRes']) {
            $jsonContent = json_decode($jsonContent);
            $jsonContent = $jsonContent->response ? $jsonContent->response : $jsonContent->text;
        }

        return $jsonContent;
    }
}
