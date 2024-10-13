<?php


/**
 * Интерфейс за работа с моделите на GPT
 * 
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class openai_GPTIntf
{


    /**
     * Праща заявка и връща резултата чрез openai
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

        return $this->class->getRes($prompt, $pArr, $useCache, $index, $cKey, $timeout);
    }
}
