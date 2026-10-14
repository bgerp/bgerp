<?php


/**
 * Добавя еднократно събраните JSON-LD възли в HEAD на външната страница
 *
 * Това е единствената финална точка за вмъкване на JSON-LD script.
 */
class jsonld_plg_External extends core_Plugin
{
    /**
     * Добавя един JSON-LD script при финализиране на страницата
     */
    public static function on_Output(&$invoker)
    {
        if (jsonld_Aggregator::isEmpty()) {
            return;
        }

        // Структурираните данни са второстепенни и не бива да чупят страницата
        try {
            $script = jsonld_Renderer::render(jsonld_Aggregator::getAll());
        } catch (Throwable $e) {
            reportException($e);

            return;
        }

        if ($script !== '') {
            $invoker->appendOnce($script, 'HEAD');
        }
    }
}
