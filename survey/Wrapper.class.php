<?php



/**
 * Клас 'survey_Wrapper'
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       $this->TAB('survey_Surveys', 'Анкети', 'admin, ceo, survey');
       $this->TAB('survey_Alternatives', 'Въпроси', 'debug');
       $this->TAB('survey_Options', 'Опции', 'debug');
       $this->TAB('survey_Votes', 'Гласуване', 'admin, ceo, survey');
       $this->title = 'Анкети';
    }
}