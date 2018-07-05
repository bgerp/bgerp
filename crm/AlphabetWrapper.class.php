<?php



/**
 * Клас 'crm_Wrapper'
 *
 * Опаковка на визитника
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class crm_AlphabetWrapper extends core_Plugin
{
    /**
     * Добавяне на табове
     *
     * @param  core_Et $tpl
     * @return core_et $tpl
     */
    public function on_AfterRenderWrapping($mvc, &$tpl, $content, $data = null)
    {
        if ($data->action != 'list') {
            return;
        }

        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet', 'maxTabsNarrow' => 1000));
         
        $alpha = Request::get('alpha');
        
        $selected = 'none';
        
        $letters = arr::make('0-9,А-A,Б-B,В-V=В-V-W,Г-G,Д-D,Е-E,Ж-J,З-Z,И-I,Й-J,К-Q=К-K-Q-C,' .
            'Л-L,М-M,Н-N,О-O,П-P,Р-R,С-S,Т-T,У-U,Ф-F,Х-H=Х-X-H,Ц-C,Ч-Ч,Ш-Щ,Ю-Я', true);
        
        foreach ($letters as $a => $set) {
            $tabs->TAB($a, '|*' . str_replace('-', '<br>', $a), array($mvc, 'list', 'alpha' => $set));
            
            if ($alpha == $set) {
                $selected = $a;
            }
        }

        if (Mode::is('screenMode', 'narrow')) {
            $tabs->headerBreak = 13;
        }
        
        $tpl = $tabs->renderHtml('', $selected);
        
        $tpl->append($content);

        //$tpl->prepend('<br>');
    }
}
