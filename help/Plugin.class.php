<?php



/**
 * Клас 'help_Plugin'
 *
 * Прихваща събитията на plg_ProtoWrapper и добавя, ако е има помощна информация в help_Info, като hint
 *
 *
 * @category  bgerp
 * @package   help
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class help_Plugin extends core_Plugin
{
    function on_afterSetCurrentTab($wrapper, $name, $url, &$hint, &$hintBtn, &$tabsTpl)
    {
        setIfNot($ctr, $url['Ctr'], $url[0]);

        if($rec = help_Info::fetch(array("#class = '[#1#]'", $ctr))) {

            // Трябва ли да бъде първоначално отворен хинта?

            if(help_Log::haveToSee($rec->id)) {
                $mustSeeClass = 'show-tooltip';
            }

            $imageUrl = sbf("img/mark.png","");
            $img = ht::createElement("img", array('src' => $imageUrl));
            $hintBtn = new ET("<a class='tooltip-button'>[#1#]</a>", $img);
            $convertText = cls::get('type_Richtext');
            $hintText = $convertText->toVerbal($rec->text);
            $hint = new ET("<div class='tooltip-text {$mustSeeClass}'><div class='tooltip-arrow'></div>[#1#]</div>", $hintText);

            jquery_Jquery::enable($tabsTpl);
         
            $tabsTpl->push('css/tooltip.css', 'CSS');
            $tabsTpl->push('js/tooltipCustom.js', 'JS');
            
            jquery_Jquery::run($tabsTpl, "tooltipCustom();", TRUE);
        }
    }

}