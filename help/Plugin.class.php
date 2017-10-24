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
        if(core_Users::haveRole('partner')) return;

        $ctr = Request::get('Ctr');
        
        $act = Request::get('Act');
        
        $act = strtolower($act);
        // какъв е метода на показваната страница?
        if ($act == 'edit' || $act == 'add') {
        	$act = 'edit';
        } elseif ($act == " " || $act == 'default' || $act == NULL) {
        	$act = 'list';
        }
       
        // Текущия език на интерфейса
        $lg = core_Lg::getCurrent();

        if(($rec = help_Info::fetch(array("#class = '[#1#]' AND #action = '[#2#]' AND #lg = '[#3#]'", $ctr, $act, $lg))) || haveRole('help')) {
            
            if(!$rec) {
                $rec = new stdClass();
                $rec->class = $ctr;
                $rec->action = $act;
                $rec->lg = $lg;
            } else {
                // Трябва ли да бъде първоначално отворен хинта и дали въобще да го показваме?
                switch(help_Log::getDisplayMode($rec->id)) {
                    case 'open':
                        $mustSeeClass = 'show-tooltip';
                        break;
                    case 'close':
                        break;
                    case 'none':
                    default:
                        if(!haveRole('help')) {
                            return;
                        }
                }
            }

            $imageUrl = sbf("img/mark.png","");
            $img = ht::createElement("img", array('src' => $imageUrl, 'alt' => 'help'));
            $hintBtn = new ET("<a class='tooltip-button'>[#1#]</a>", $img);
            $convertText = cls::get('type_Richtext');
            $hintText = $convertText->toVerbal($rec->text . '');
            if(haveRole('help')){
            	$imgEdit = ht::createElement("img", array('src' => sbf("img/16/edit-icon.png",""), 'alt' => 'edit'));
                if(!$rec->id) {
                    $urlAE = array("help_Info", "add", 'class' => $ctr, 'action' => $act, 'lg' => $lg, 'ret_url' => TRUE);
                } else {
                    $urlAE = array("help_Info", "edit" , $rec->id, 'ret_url' => TRUE);
                }
            	$hintText .= ht::createLink($imgEdit, $urlAE, NULL, array('class' => 'edit-tooltip'));
            }  
            
            $hint = new ET("<div class='tooltip-text {$mustSeeClass}'><div class='tooltip-arrow'></div><a class='close-tooltip'></a>[#1#]</div>", $hintText);
            $url = toUrl(array('help_Log', 'CloseInfo', $rec->id));
            
            $tabsTpl->push('css/tooltip.css', 'CSS');
            $tabsTpl->push('js/tooltipCustom.js', 'JS');
            
            jquery_Jquery::run($tabsTpl, "\n tooltipCustom('{$url}');", TRUE);
        }
    }

}