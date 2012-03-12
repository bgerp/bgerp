<?php



/**
 * Портален изглед на състоянието на системата
 *
 * Има възможност за костюмиране за всеки потребител
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Portal extends core_Manager
{
    
    
    /**
     * Неща за зареждане в началото
     */
    var $loadList = 'plg_Created, plg_RowTools, bgerp_Wrapper';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Елементи на портала';
    
    // Права
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('column', 'enum(1,2,3,4)', 'caption=Колона, mandatory');
        $this->FLD('blockSource', 'class(interface=bgerp_BlockSource)', 'caption=Контролер, mandatory');
        $this->FLD('params', 'text', 'caption=Настройки,input=none');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('mobile', 'enum(no=Не,yes=Да)', 'caption=Мобилен');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Show()
    {
        requireRole('user');
        
        if(Mode::is('screenMode', 'narrow')) {
            $tpl = new ET("
                <div>[#NOTIFICATIONS#]</div>
                <div style='margin-top:25px;'>[#RIGHT_COLUMN#]</div>
                <div style='margin-top:25px;'>[#LEFT_COLUMN#]</div>
            ");
        } else {
            $tpl = new ET("
            <table width=100% class='top-table' cellspacing=10 >
            <tr>
                <td width=30%>[#LEFT_COLUMN#]</td>
                <td width=40% >[#NOTIFICATIONS#]</td>
                <td width=30% >[#RIGHT_COLUMN#]</td>
            </tr>
            </table>
            ");
        }
        
        // Добавяме "Наскоро" -и папки с които е работено наскоро
        $tpl->append(bgerp_Recently::render(), 'LEFT_COLUMN');
        
        $tpl->replace(bgerp_Notifications::render(), 'NOTIFICATIONS');
        
        $tpl->replace('<div class="clearfix21 portal" style="background-color:#f8fff8">
            <div class="legend" style="background-color:#efe;">Календар и задачи</div>
             
            </div>', 'RIGHT_COLUMN');
        
        return $tpl;
    }
}