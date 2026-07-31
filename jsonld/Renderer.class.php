<?php


/**
 * Създава само HTML script обвивката за JSON-LD възлите
 *
 * Финалното еднократно добавяне в HEAD се извършва от външния plugin.
 */
class jsonld_Renderer
{
    /**
     * Рендерира подадените JSON-LD възли
     *
     * @param jsonld_Node[] $nodes
     *
     * @return string
     */
    public static function render($nodes)
    {
        if (empty($nodes)) {
            return '';
        }

        $json = jsonld_Adapter::toJson($nodes);

        if ($json === '') {
            return '';
        }

        return '<script type="application/ld+json">'
            . $json
            . '</script>';
    }
}
