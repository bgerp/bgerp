<?php


/**
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_JSON extends type_Text
{


    /**
     * Връща вербално представяне на стойността на двоичното поле
     */
    public function toVerbal($value)
    {
        if (empty($value)) {

            return;
        }

        if (!$this->params['noDecode']) {
            $value = @json_decode($value);

            $value = ht::wrapMixedToHtml(ht::mixedToHtml(
                $value,
                isset($this->params['hideLevel']) ? $this->params['hideLevel'] : 3,
                isset($this->params['maxLevel']) ? $this->params['maxLevel'] : 6
            ));
        }

        return $value;
    }
}
