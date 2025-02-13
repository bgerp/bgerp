<?php


/**
 * Клас 'doc_plg_Tabs' - Плъгин рендиращ табове на документите
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_Tabs extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        setIfNot($mvc->mainTabCaption, 'Статистика');
    }


    /**
     * Подготовка за рендиране на единичния изглед
     *
     * @param core_Master $mvc
     * @param object      $res
     * @param object      $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) return;

        $mvc->prepareDocumentTabs($data);

        $data->selectedTab = $data->tabs->getSelected();
        if (!$data->selectedTab) {
            $data->selectedTab = $data->tabs->getFirstTab();
        }

        // Ако е само един таба не показваме статистиката
        if ($data->tabs->count() == 1) {
            unset($data->tabs);
        }

        // Ако има селектиран таб викаме му метода за подготовка на данните
        if (isset($data->selectedTab) && $data->selectedTab != 'Statistic') {
            $method = "prepare{$data->selectedTab}";
            $mvc->$method($data);
        }
    }


    /**
     * След подготовка на табовете на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     * @return void
     * @throws core_exception_Break
     */
    public static function on_AfterPrepareDocumentTabs($mvc, &$res, $data)
    {

        $data->tabs = cls::get('core_Tabs', array('htmlClass' => 'deal-history-tab alphabet', 'urlParam' => "docTab{$data->rec->containerId}"));
        $url = getCurrentUrl();
        unset($url['export']);

        $url["docTab{$data->rec->containerId}"] = 'Statistic';
        $data->tabs->TAB('Statistic', $mvc->mainTabCaption, $url, null, 1);
    }


    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        // Ако има табове
        if (isset($data->tabs)) {
            if($mvc->hasPlugin('doc_plg_TplManager')){
                if (isset($data->rec->tplLang)) {
                    core_Lg::pop();
                }
            }

            $tabHtml = $data->tabs->renderHtml('', $data->selectedTab);
            $tpl->replace($tabHtml, 'TABS');

            // Ако има избран таб и това не е статистиката, рендираме го
            if (isset($data->{$data->selectedTab}) && $data->selectedTab != 'Statistic') {
                $method = "render{$data->selectedTab}";
                $mvc->$method($tpl, $data);
            }

            if($mvc->hasPlugin('doc_plg_TplManager')){
                if (isset($data->rec->tplLang)) {
                    core_Lg::push($data->rec->tplLang);
                }
            }
        }
    }
}