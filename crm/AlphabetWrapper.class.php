<?php


/**
 * Клас 'crm_Wrapper'
 *
 * Опаковка на визитника
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class crm_AlphabetWrapper extends core_Plugin
{
    /**
     * Добавяне на табове
     *
     * @param core_Et $tpl
     *
     * @return core_et $tpl
     */
    public function on_AfterRenderWrapping($mvc, &$tpl, $content, $data = null)
    {
        if ($data->action != 'list') {
            
            return;
        }
        
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet', 'maxTabsNarrow' => 1000, 'htmlId' => 'alphabet'));
        $tabs2 = cls::get('core_Tabs', array('htmlClass' => 'alphabet', 'maxTabsNarrow' => 1000, 'htmlId' => 'alphabet'));

        $alpha = Request::get('alpha');
        
        $selected = 'none';
        
        $letters = arr::make('А,Б,В,Г,Д,Е,Ж,З,И,Й,К,Л,М,Н,О,П,Р,С,Т,У,Ф,Х,Ц,Ч,Ш,Щ,Ъ,Ю,Я', true);
        $lettersEN = arr::make('A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z, 0 - 9', true);

        foreach ($letters as $a => $set) {
            $tabs->TAB($a, '|*' .  $a, array($mvc, 'list', 'alpha' => $set));
            if ($alpha == $set) {
                $selected = $a;
            }
        }

        foreach ($lettersEN as $a => $set) {
            $tabs2->TAB($a, '|*' .  $a, array($mvc, 'list', 'alpha' => $set));

            if ($alpha == $set) {
                $selected = $a;
            }
        }

        $tpl->append($tabs->renderHtml(null, $selected), 'ListTitle');
        $tpl->append($tabs2->renderHtml(null, $selected), 'ListTitle');
    }
}
