<?php


/**
 * Добавя еднократно събраните JSON-LD възли и фирмата в HEAD на външната страница
 *
 * Това е единствената финална точка за вмъкване на JSON-LD script-овете.
 */
class jsonld_plg_External extends core_Plugin
{
    /**
     * Добавя JSON-LD script-овете при финализиране на страницата
     */
    public static function on_Output(&$invoker)
    {
        // Фирмата е в собствен script на всяка страница - графът сочи към нея по @id
        try {
            $organization = jsonld_Helper::getOrganization();
            $orgScript = isset($organization) ? jsonld_Renderer::render(array($organization)) : '';
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            reportException($e);
            $orgScript = '';
        }

        // Сайтът с търсенето му е на всяка страница - не всяка има начална
        try {
            $webSite = jsonld_Helper::getWebSite();

            if (isset($webSite)) {
                jsonld_Aggregator::add($webSite);
            }
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            reportException($e);
        }

        // Структурираните данни са второстепенни и не бива да чупят страницата
        try {
            $script = jsonld_Renderer::render(jsonld_Aggregator::getAll());
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            reportException($e);
            $script = '';
        }

        foreach (array($script, $orgScript) as $str) {
            if ($str !== '') {
                $invoker->appendOnce("\n" . $str, 'HEAD');
            }
        }
    }
}
