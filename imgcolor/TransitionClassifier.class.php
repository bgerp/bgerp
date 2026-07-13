<?php


/**
 * Тристепенна пространствена класификация на пикселите за отделяне на
 * преливки (CMYK) от плътни цветове. Виж
 * docs/superpowers/specs/2026-07-13-imgcolor-cmyk-separation-design.md.
 *
 * Без bgERP зависимост - тества се директно с
 * `php imgcolor/tests/cli_separation.php`.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_TransitionClassifier
{
    /**
     * Класове пиксели - байтови стойности в маската (ред по редове, W*H байта)
     */
    const CLS_BG = "\x00";
    const CLS_SOLID = "\x01";
    const CLS_AA = "\x02";
    const CLS_TRANS = "\x03";
}
