<?php


/**
 * Клас 'borsa_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'borsa'
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('borsa_Lots', 'Лотове', 'borsa, ceo');
        $this->TAB('borsa_Periods', 'Периоди', 'borsa, ceo');
        $this->TAB('borsa_Companies', 'Фирми', 'borsa, ceo');
        $this->TAB('borsa_Bids', 'Оферти', 'borsa, ceo');
    }
}
