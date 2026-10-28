<?php


/**
 * Четене и попълване на формуляри в PDF и сканирани изображения
 *
 * Координатите са в проценти от показаната страница (със завъртането), с начало горе вляво.
 * Полетата (AcroForm) се четат и попълват с pypdf, текстът и позициите му - с pdftotext,
 * а при сканове - с tesseract. Написаното върху страниците се рисува с TCPDF.
 *
 * @category  bgerp
 * @package   pdfform
 *
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pdfform_Api
{
    /**
     * Най-много редове текст за една страница в резултата от четенето
     */
    const MAX_LINES = 300;


    /**
     * Най-много полета в резултата от четенето
     */
    const MAX_FIELDS = 300;


    /**
     * Най-много страници с текстов анализ
     */
    const MAX_TEXT_PAGES = 50;


    /**
     * Най-много елементи за писане в едно попълване
     */
    const MAX_ITEMS = 500;


    /**
     * Разделителна способност на страницата за OCR
     */
    const OCR_RESOLUTION = 200;


    /**
     * Изображенията, които се приемат като сканиран формуляр от една страница
     */
    const IMAGE_EXTS = array('jpg', 'jpeg', 'png', 'gif');


    /**
     * Дали четенето и попълването на полета е налично на сървъра
     *
     * @return bool
     */
    public static function isAvailable()
    {
        return core_Packs::isInstalled('pdfform') && !cls::get('pdfform_Setup')->checkConfig();
    }


    /**
     * Чете формуляр: страници, полета и текстът с позициите му на избраната страница
     *
     * @param string $fh   - манипулатор на PDF или изображение
     * @param int    $page - страница за текста с позиции, от 1
     *
     * @return array - данните или ['error' => ..., 'reason' => ...]
     */
    public static function read($fh, $page = 1)
    {
        $work = self::prepare($fh, false);
        if (!empty($work['error'])) return $work;
        $dir = $work['dir'];

        try {
            $page = max(1, (int) $page);
            $info = $work['info'];
            $pageCount = countR($info['pages']);
            if ($page > $pageCount) {

                return array('error' => "Page {$page} does not exist. The file has {$pageCount} pages.", 'reason' => 'invalid_page', 'pageCount' => $pageCount);
            }

            // Текстът с позиции; за сканове и снимки - чрез OCR
            $text = $work['isImage'] ? array() : self::extractText($dir, $work['pdf'], array_slice($info['pages'], 0, self::MAX_TEXT_PAGES));
            $fieldPages = array();
            foreach ($info['fields'] as $f) {
                $fieldPages[(int) ($f['page'] ?? 0)] = true;
                foreach ((array) ($f['widgets'] ?? array()) as $w) {
                    $fieldPages[(int) ($w['page'] ?? 0)] = true;
                }
            }

            $pages = array();
            $cropped = false;
            foreach ($info['pages'] as $i => $p) {
                $no = $i + 1;
                if ($no > self::MAX_TEXT_PAGES) break;
                $words = $text[$no]['words'] ?? 0;
                $row = array('page' => $no, 'widthMm' => round($p['width'] * 25.4 / 72, 1), 'heightMm' => round($p['height'] * 25.4 / 72, 1),
                    'kind' => isset($fieldPages[$no]) ? 'fields' : ($words >= 3 ? 'text' : 'scan'));
                if (!empty($p['cropped'])) {
                    $row['cropped'] = true;
                    $cropped = true;
                }
                $pages[] = $row;
            }

            $lines = $text[$page]['lines'] ?? array();
            $linesSource = 'text';
            if (($text[$page]['words'] ?? 0) < 3) {
                $ocr = self::ocrPage($dir, $work, $page);
                if (isset($ocr['error'])) {
                    $lines = array();
                    $linesSource = null;
                    $ocrError = $ocr['error'];
                } else {
                    $lines = $ocr;
                    $linesSource = 'ocr';
                }
            }

            $res = array('pageCount' => $pageCount, 'pages' => $pages);
            if ($pageCount > self::MAX_TEXT_PAGES) {
                $res['pagesNote'] = 'Only the first ' . self::MAX_TEXT_PAGES . ' pages are listed.';
            }
            if ($cropped) {
                $res['croppedNote'] = 'Positions are relative to the visible (cropped) page. get_image may show the full uncropped sheet of pages marked cropped, so take positions there from fields and lines.';
            }
            if (!empty($info['xfa'])) {
                $res['xfa'] = $info['xfa'];
            }
            $res['fields'] = self::publicFields(array_slice($info['fields'], 0, self::MAX_FIELDS));
            if (countR($info['fields']) > self::MAX_FIELDS) {
                $res['fieldsNote'] = 'Only the first ' . self::MAX_FIELDS . ' of ' . countR($info['fields']) . ' fields are listed.';
            }
            $res['page'] = $page;
            $res['linesSource'] = $linesSource;
            $res['lines'] = array_slice($lines, 0, self::MAX_LINES);
            if (countR($lines) > self::MAX_LINES) {
                $res['linesNote'] = 'Only the first ' . self::MAX_LINES . ' lines of the page are listed.';
            }
            if (isset($ocrError)) {
                $res['linesError'] = $ocrError;
            }

            return $res;
        } finally {
            core_Os::deleteDir($dir);
        }
    }


    /**
     * Попълва формуляр и записва резултата като нов PDF файл
     *
     * @param string $fh       - манипулатор на PDF или изображение
     * @param array  $data     - 'fields' => [име => стойност], 'items' => [елементи за писане], 'flatten' => bool
     * @param string $fileName - име на новия файл
     * @param string $bucket   - кофа за новия файл
     *
     * @return array - ['fh' => ..., 'fieldsFilled' => ..., 'itemsDrawn' => ...] или ['error' => ...]
     */
    public static function fill($fh, $data, $fileName, $bucket = 'exportFiles')
    {
        $fields = $data['fields'] ?? array();
        $items = $data['items'] ?? array();
        $flatten = !array_key_exists('flatten', (array) $data) || !empty($data['flatten']);
        if (!is_array($fields) || !is_array($items)) {

            return array('error' => 'fields must be an object and items an array.', 'reason' => 'invalid_input');
        }
        if (!countR($fields) && !countR($items)) {

            return array('error' => 'Nothing to fill: pass fields and/or items.', 'reason' => 'invalid_input');
        }
        if (countR($items) > self::MAX_ITEMS) {

            return array('error' => 'Too many items; at most ' . self::MAX_ITEMS . ' per call.', 'reason' => 'invalid_input');
        }

        $work = self::prepare($fh, true);
        if (!empty($work['error'])) return $work;
        $dir = $work['dir'];

        try {
            $info = $work['info'];
            if (countR($fields) && ($info['xfa'] ?? null) === 'dynamic') {

                return array('error' => 'This is a dynamic XFA form: it has no fillable PDF fields. Only items can be drawn on its placeholder pages.', 'reason' => 'xfa');
            }

            $errors = array();
            $values = self::normalizeFields($fields, $info['fields'], $errors);
            $draw = self::normalizeItems($items, countR($info['pages']), $errors);
            if (countR($errors)) {

                return array('error' => 'Nothing was filled; fix these and repeat the call: ' . implode(' ', $errors), 'reason' => 'invalid_input', 'errors' => $errors);
            }

            $drawnFields = $flatten ? self::flattenFields($info['fields'], $values) : array();

            $overlay = null;
            if (countR($draw) || countR($drawnFields)) {
                $overlay = $dir . 'overlay.pdf';
                $res = pdfform_Overlay::create($overlay, $info['pages'], $draw, $drawnFields);
                if (!empty($res['error'])) return $res;
            }

            $spec = array('fields' => (object) $values, 'overlay' => $overlay, 'flatten' => $flatten,
                'drawn' => array_map(function ($d) { return $d['field']['name']; }, $drawnFields));
            file_put_contents($dir . 'spec.json', json_encode($spec, JSON_UNESCAPED_UNICODE));

            $res = self::runPython($dir, array('fill', $work['pdf'], $dir . 'spec.json', $dir . 'out.pdf', $dir . 'fill.json'), $dir . 'fill.json');
            if (!empty($res['error'])) return $res;

            $newFh = fileman_Files::absorb($dir . 'out.pdf', $bucket, $fileName);
            if (!$newFh) {

                return array('error' => 'The filled PDF could not be saved.', 'reason' => 'save_failed');
            }

            return array('fh' => $newFh, 'fieldsFilled' => countR($values), 'itemsDrawn' => countR($draw), 'flattened' => $flatten,
                'pageCount' => countR($info['pages']));
        } finally {
            core_Os::deleteDir($dir);
        }
    }


    /**
     * Полетата с видимия им текст, който при заключване се рисува с шрифт с кирилица
     *
     * @param array $defs   - полетата от четенето
     * @param array $values - новите стойности [име => стойност]
     *
     * @return array - [['field' => полето, 'value' => текст], ...]
     */
    protected static function flattenFields($defs, $values)
    {
        $res = array();
        foreach ($defs as $f) {
            if (!in_array($f['type'], array('text', 'dropdown', 'listbox'), true)) continue;

            // Само скрити повторения: стойността не се вижда и след заключване
            $visible = array_filter(self::fieldWidgets($f), function ($w) { return empty($w['hidden']); });
            if (!countR($visible)) continue;
            $value = array_key_exists($f['name'], $values) ? $values[$f['name']] : $f['value'];
            $value = self::displayValue($f, $value);
            if ($value === '') continue;
            $res[] = array('field' => $f, 'value' => $value);
        }

        return $res;
    }


    /**
     * Повторенията (widgets) на поле; за стари данни без тях - самото поле
     *
     * @param array $f
     *
     * @return array
     */
    public static function fieldWidgets($f)
    {
        return countR($f['widgets'] ?? null) ? $f['widgets'] : array($f);
    }


    /**
     * Полетата за резултата от четенето - във вида отпреди повторенията на текстовите полета
     *
     * @param array $fields
     *
     * @return array
     */
    protected static function publicFields($fields)
    {
        foreach ($fields as &$f) {
            if (!in_array($f['type'] ?? '', array('text', 'dropdown', 'listbox'), true) || !isset($f['widgets'])) continue;
            if (countR($f['widgets']) === 1) {
                if (!empty($f['widgets'][0]['hidden'])) {
                    $f['hidden'] = true;
                }
                unset($f['widgets']);
                continue;
            }
            foreach ($f['widgets'] as $i => $w) {
                $f['widgets'][$i] = array_intersect_key($w, array_flip(array('page', 'rect', 'hidden')));
            }
        }
        unset($f);

        return $fields;
    }


    /**
     * Копира файла във временна папка, при изображение прави PDF от него и чете структурата
     *
     * @param string $fh
     * @param bool   $needPdf - изображението да се превърне в PDF
     *
     * @return array - 'dir', 'pdf', 'image', 'isImage', 'info' или 'error'
     */
    protected static function prepare($fh, $needPdf)
    {
        $fRec = $fh ? fileman_Files::fetchByFh($fh) : null;
        $path = $fRec ? fileman_Files::fetchByFh($fh, 'path') : null;
        if (!$fRec || !$path || !is_file($path)) {

            return array('error' => 'File not found.', 'reason' => 'missing_file');
        }

        $ext = strtolower(fileman_Files::getExt($fRec->name ?? ''));
        $isImage = in_array($ext, self::IMAGE_EXTS, true);
        if (!$isImage && $ext !== 'pdf') {

            return array('error' => 'Only PDF files and images (' . implode(', ', self::IMAGE_EXTS) . ') can be read or filled as forms.', 'reason' => 'unsupported_format');
        }

        if (!$isImage && !self::isAvailable()) {

            return array('error' => 'PDF forms are unavailable: pypdf is not installed on the server. Ask an administrator to initialize the pdfform package.', 'reason' => 'unavailable');
        }

        $dir = rtrim(cls::get('fconv_Script')->tempDir, '/') . '/';
        core_Os::requireDir($dir);

        $res = array('dir' => $dir, 'isImage' => $isImage);
        if ($isImage) {
            $res['image'] = $dir . 'in.' . $ext;
            copy($path, $res['image']);
            $size = @getimagesize($res['image']);
            if (!$size || empty($size[0]) || empty($size[1])) {
                core_Os::deleteDir($dir);

                return array('error' => 'The image could not be read.', 'reason' => 'invalid_image');
            }
            $page = self::imagePageSize($size[0], $size[1]);
            $res['info'] = array('pages' => array($page), 'fields' => array(), 'xfa' => null);
            if ($needPdf) {
                $res['pdf'] = $dir . 'in.pdf';
                $made = pdfform_Overlay::imageToPdf($res['image'], $res['pdf'], $page);
                if (!empty($made['error'])) {
                    core_Os::deleteDir($dir);

                    return $made;
                }
            }

            return $res;
        }

        $res['pdf'] = $dir . 'in.pdf';
        copy($path, $res['pdf']);
        $info = self::runPython($dir, array('info', $res['pdf'], $dir . 'info.json'), $dir . 'info.json');
        if (!empty($info['error'])) {
            core_Os::deleteDir($dir);

            return $info;
        }
        if (!countR($info['pages'] ?? null)) {
            core_Os::deleteDir($dir);

            return array('error' => 'The PDF has no pages.', 'reason' => 'invalid_pdf');
        }
        $info['fields'] = (array) ($info['fields'] ?? array());
        $res['info'] = $info;

        return $res;
    }


    /**
     * Размер на страницата за сканирано изображение: ширина на A4 според ориентацията
     *
     * @param int $width  - пиксели
     * @param int $height - пиксели
     *
     * @return array - 'width', 'height' в пунктове
     */
    protected static function imagePageSize($width, $height)
    {
        $pageWidth = ($width > $height) ? 841.89 : 595.28;

        return array('width' => $pageWidth, 'height' => round($pageWidth * $height / $width, 2), 'rotate' => 0);
    }


    /**
     * Проверява и подготвя стойностите на полетата за pypdf
     *
     * @param array $fields - [име => стойност]
     * @param array $defs   - полетата от четенето
     * @param array $errors
     *
     * @return array - [име => стойност]
     */
    protected static function normalizeFields($fields, $defs, &$errors)
    {
        $byName = array();
        foreach ($defs as $def) {
            $byName[$def['name']] = $def;
        }

        $res = array();
        foreach ($fields as $name => $value) {
            $name = (string) $name;
            $def = $byName[$name] ?? null;
            if (!$def) {
                $errors[] = "Field \"{$name}\" does not exist; use the names returned by pdf_read_form.";
                continue;
            }
            if (!empty($def['readonly'])) {
                $errors[] = "Field \"{$name}\" is read-only.";
                continue;
            }

            switch ($def['type']) {
                case 'text':
                    if (!is_scalar($value) && $value !== null) {
                        $errors[] = "Field \"{$name}\" needs a text value.";
                        continue 2;
                    }
                    $value = is_bool($value) ? ($value ? 'Yes' : '') : (string) $value;
                    if (empty($def['multiline'])) {
                        $value = trim(preg_replace('/\s*[\r\n]+\s*/u', ' ', $value));
                    }
                    if (!empty($def['maxLength']) && mb_strlen($value) > $def['maxLength']) {
                        $errors[] = "Field \"{$name}\" allows at most {$def['maxLength']} characters.";
                        continue 2;
                    }
                    break;

                case 'checkbox':
                    $on = (string) ($def['onState'] ?? 'Yes');
                    if ($value === true || $value === 1 || (is_string($value) && in_array(strtolower($value), array(strtolower($on), 'yes', 'on', 'true', '1', 'x'), true))) {
                        $value = $on;
                    } elseif ($value === false || $value === 0 || $value === null || (is_string($value) && in_array(strtolower($value), array('off', 'no', 'false', '0', ''), true))) {
                        $value = 'Off';
                    } else {
                        $errors[] = "Field \"{$name}\" is a checkbox: use true or false.";
                        continue 2;
                    }
                    break;

                case 'radio':
                    $options = (array) ($def['options'] ?? array());
                    if ($value === null || $value === false || $value === '' || $value === 'Off') {
                        $value = 'Off';
                    } elseif (!is_scalar($value) || !in_array((string) $value, $options, true)) {
                        $errors[] = "Field \"{$name}\" is a radio group; use one of: " . implode(', ', $options) . '.';
                        continue 2;
                    } else {
                        $value = (string) $value;
                    }
                    break;

                case 'dropdown':
                case 'listbox':
                    $list = is_array($value) ? $value : array($value);
                    if (countR($list) > 1 && empty($def['multiple'])) {
                        $errors[] = "Field \"{$name}\" accepts one value.";
                        continue 2;
                    }
                    $out = array();
                    foreach ($list as $v) {
                        if (!is_scalar($v) && $v !== null) {
                            $errors[] = "Field \"{$name}\" needs text values.";
                            continue 3;
                        }
                        $v = (string) $v;
                        $match = self::matchOption($def['options'] ?? array(), $v);
                        if ($match === null && $v !== '' && empty($def['editable'])) {
                            $errors[] = "Field \"{$name}\" accepts only: " . implode(', ', self::optionLabels($def['options'] ?? array())) . '.';
                            continue 3;
                        }
                        $out[] = $match ?? $v;
                    }
                    $value = is_array($value) ? $out : (string) reset($out);
                    break;

                default:
                    $errors[] = "Field \"{$name}\" is a {$def['type']} field and cannot be filled.";
                    continue 2;
            }
            $res[$name] = $value;
        }

        return $res;
    }


    /**
     * Стойността на опцията, съвпадаща по стойност или по етикет
     *
     * @param array  $options
     * @param string $value
     *
     * @return string|null
     */
    protected static function matchOption($options, $value)
    {
        foreach ((array) $options as $opt) {
            $optValue = is_array($opt) ? (string) ($opt['value'] ?? '') : (string) $opt;
            $label = is_array($opt) ? (string) ($opt['label'] ?? '') : $optValue;
            if ($value === $optValue || $value === $label) return $optValue;
        }

        return null;
    }


    /**
     * Етикетите на опциите за съобщения и за рисуване
     *
     * @param array $options
     *
     * @return array
     */
    protected static function optionLabels($options)
    {
        $res = array();
        foreach ((array) $options as $opt) {
            $res[] = is_array($opt) ? (string) ($opt['label'] ?? $opt['value'] ?? '') : (string) $opt;
        }

        return $res;
    }


    /**
     * Текстът, който се вижда в полето - при списъците етикетът на избраната стойност
     *
     * @param array $def
     * @param mixed $value
     *
     * @return string
     */
    protected static function displayValue($def, $value)
    {
        $list = is_array($value) ? $value : array($value);
        $res = array();
        foreach ($list as $v) {
            $v = (string) $v;
            foreach ((array) ($def['options'] ?? array()) as $opt) {
                if (is_array($opt) && (string) ($opt['value'] ?? '') === $v) {
                    $v = (string) ($opt['label'] ?? $v);
                    break;
                }
            }
            if ($v !== '') $res[] = $v;
        }

        return implode(', ', $res);
    }


    /**
     * Проверява елементите за писане върху страниците
     *
     * @param array $items
     * @param int   $pageCount
     * @param array $errors
     *
     * @return array
     */
    protected static function normalizeItems($items, $pageCount, &$errors)
    {
        $res = array();
        foreach (array_values($items) as $i => $item) {
            $n = $i + 1;
            if (!is_array($item)) {
                $errors[] = "items[{$n}] must be an object.";
                continue;
            }
            $page = $item['page'] ?? 1;
            if (!is_numeric($page) || (int) $page < 1 || (int) $page > $pageCount) {
                $errors[] = "items[{$n}]: page must be from 1 to {$pageCount}.";
                continue;
            }
            $kinds = array_intersect(array('text', 'check', 'image'), array_keys(array_filter($item, function ($v) { return $v !== null && $v !== false; })));
            if (countR($kinds) !== 1) {
                $errors[] = "items[{$n}] needs exactly one of text, check or image.";
                continue;
            }
            $kind = reset($kinds);
            $box = array();
            foreach (array('x' => true, 'y' => true, 'width' => false, 'height' => false) as $key => $required) {
                $v = $item[$key] ?? null;
                if ($v === null) {
                    if ($required) $errors[] = "items[{$n}]: {$key} is required.";
                    continue;
                }
                $min = $required ? 0 : 0.01;
                if (!is_numeric($v) || $v < $min || $v > 100) {
                    $errors[] = "items[{$n}]: {$key} must be a percent from {$min} to 100.";
                    continue;
                }
                $box[$key] = (float) $v;
            }
            if (!isset($box['x'], $box['y'])) continue;

            $rec = array('kind' => $kind, 'page' => (int) $page) + $box;
            if ($kind === 'text') {
                if (!is_scalar($item['text'])) {
                    $errors[] = "items[{$n}]: text must be a string.";
                    continue;
                }
                $rec['text'] = (string) $item['text'];
                $size = $item['font_size'] ?? 10;
                if (!is_numeric($size) || $size < 4 || $size > 72) {
                    $errors[] = "items[{$n}]: font_size must be from 4 to 72.";
                    continue;
                }
                $rec['fontSize'] = (float) $size;
                $align = $item['align'] ?? 'left';
                if (!in_array($align, array('left', 'center', 'right'), true)) {
                    $errors[] = "items[{$n}]: align must be left, center or right.";
                    continue;
                }
                $rec['align'] = $align;
                if (isset($box['height']) && !isset($box['width'])) {
                    $errors[] = "items[{$n}]: a text box with height needs width too.";
                    continue;
                }
            } elseif ($kind === 'check') {
                if ($item['check'] !== true) {
                    $errors[] = "items[{$n}]: check must be true; leave the box out to keep it empty.";
                    continue;
                }
            } else {
                $imgRec = is_string($item['image']) ? fileman_Files::fetchByFh($item['image']) : null;
                $imgPath = $imgRec ? fileman_Files::fetchByFh($item['image'], 'path') : null;
                $imgExt = $imgRec ? strtolower(fileman_Files::getExt($imgRec->name ?? '')) : '';
                if (!$imgPath || !is_file($imgPath)) {
                    $errors[] = "items[{$n}]: image file not found.";
                    continue;
                }
                if (!in_array($imgExt, self::IMAGE_EXTS, true)) {
                    $errors[] = "items[{$n}]: image must be " . implode(', ', self::IMAGE_EXTS) . '.';
                    continue;
                }
                if (!isset($box['width'])) {
                    $errors[] = "items[{$n}]: an image needs width.";
                    continue;
                }
                $rec['path'] = $imgPath;
                $rec['ext'] = $imgExt;
            }
            $res[] = $rec;
        }

        return $res;
    }


    /**
     * Текстът на страниците с позиции на редовете (pdftotext -bbox-layout)
     *
     * @param string $dir
     * @param string $pdf
     * @param array  $pages - показаните размери на страниците от pdfform.py
     *
     * @return array - [страница => ['words' => брой, 'lines' => [...]]]
     */
    protected static function extractText($dir, $pdf, $pages)
    {
        // -cropbox: координатите са спрямо видимата страница, както при полетата и наслагването
        $Script = self::script($dir);
        $Script->setParam('IN', $pdf);
        $Script->setParam('OUT', $dir . 'text.html');
        $Script->lineExec('pdftotext -cropbox -bbox-layout -enc UTF-8 -f 1 -l ' . countR($pages) . ' [#IN#] [#OUT#]', array('errFilePath' => $dir . 'text.err'));
        $Script->setCheckProgramsArr('pdftotext');
        if ($Script->run(false) === false || !is_file($dir . 'text.html')) {

            return array();
        }

        return self::parseBboxLayout((string) file_get_contents($dir . 'text.html'), $pages);
    }


    /**
     * Редовете от изхода на pdftotext -bbox-layout
     *
     * pdftotext дава думите в посоката на показаната страница, но width/height на страницата
     * остават незавъртени, затова процентите се смятат по показания размер от pdfform.py.
     *
     * @param string $xml
     * @param array  $pages - [['width' => pt, 'height' => pt], ...]
     *
     * @return array - [страница => ['words' => брой, 'lines' => [...]]]
     */
    public static function parseBboxLayout($xml, $pages)
    {
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$loaded) {

            return array();
        }

        $res = array();
        foreach ($doc->getElementsByTagName('page') as $i => $pageEl) {
            $w = (float) ($pages[$i]['width'] ?? $pageEl->getAttribute('width'));
            $h = (float) ($pages[$i]['height'] ?? $pageEl->getAttribute('height'));
            $words = 0;
            $lines = array();
            foreach ($pageEl->getElementsByTagName('line') as $lineEl) {
                $parts = array();
                foreach ($lineEl->getElementsByTagName('word') as $wordEl) {
                    $parts[] = trim($wordEl->textContent);
                }
                $text = trim(implode(' ', $parts));
                if ($text === '' || $w <= 0 || $h <= 0) continue;
                $words += countR($parts);
                $lines[] = self::lineBox($text, (float) $lineEl->getAttribute('xMin'), (float) $lineEl->getAttribute('yMin'),
                    (float) $lineEl->getAttribute('xMax'), (float) $lineEl->getAttribute('yMax'), $w, $h);
            }
            $res[$i + 1] = array('words' => $words, 'lines' => $lines);
        }

        return $res;
    }


    /**
     * Разпознава текста на страница или изображение с позициите на редовете (tesseract tsv)
     *
     * @param string $dir
     * @param array  $work
     * @param int    $page
     *
     * @return array - редовете или ['error' => ...]
     */
    protected static function ocrPage($dir, $work, $page)
    {
        $Script = self::script($dir);
        $tesseract = core_Packs::isInstalled('tesseract') ? tesseract_Setup::get('PATH') : 'tesseract';
        $lang = core_Packs::isInstalled('tesseract') ? tesseract_Setup::get('LANGUAGES') : 'bul+eng';
        $Script->setProgram('tesseract', $tesseract);
        $Script->setParam('LANGUAGE', $lang);
        $Script->setParam('OCROUT', $dir . 'ocr');
        $check = array('tesseract');

        if ($work['isImage']) {
            $Script->setParam('IMG', $work['image']);
        } else {
            $pdftocairo = core_Packs::isInstalled('poppler') ? poppler_Setup::get('PDFTOCAIRO_PATH') : 'pdftocairo';
            $Script->setProgram('pdftocairo', $pdftocairo);
            $Script->setParam('IN', $work['pdf']);
            $Script->setParam('PAGEIMG', $dir . 'page');
            $Script->setParam('IMG', $dir . 'page.png');
            $Script->lineExec('pdftocairo -cropbox -png -r ' . self::OCR_RESOLUTION . ' -f ' . (int) $page . ' -l ' . (int) $page . ' -singlefile [#IN#] [#PAGEIMG#]', array('errFilePath' => $dir . 'render.err'));
            $check[] = 'pdftocairo';
        }
        $Script->lineExec('tesseract [#IMG#] [#OCROUT#] -l [#LANGUAGE#] tsv', array('LANG' => 'en_US.UTF-8', 'HOME' => $dir, 'errFilePath' => $dir . 'ocr.err'));
        $Script->setCheckProgramsArr($check);

        if ($Script->run(false) === false) {

            return array('error' => 'Text recognition (OCR) is unavailable on the server; view the page with get_image instead.');
        }
        if (!is_file($dir . 'ocr.tsv')) {
            log_System::add('pdfform_Api', 'Грешка при OCR: ' . (string) @file_get_contents($dir . 'ocr.err'), null, 'warning');

            return array('error' => 'Text recognition (OCR) failed for this page; view it with get_image instead.');
        }

        return self::parseTsv((string) file_get_contents($dir . 'ocr.tsv'));
    }


    /**
     * Редовете от tsv изхода на tesseract
     *
     * @param string $tsv
     *
     * @return array
     */
    public static function parseTsv($tsv)
    {
        $w = $h = 0;
        $groups = array();
        foreach (preg_split('/\r?\n/', $tsv) as $i => $row) {
            $c = explode("\t", $row);
            if ($i === 0 || countR($c) < 12) continue;
            if ($c[0] === '1') {
                $w = (float) $c[8];
                $h = (float) $c[9];
                continue;
            }
            $text = trim($c[11]);
            if ($c[0] !== '5' || $text === '' || (float) $c[10] < 0) continue;
            $key = $c[2] . '.' . $c[3] . '.' . $c[4];
            $x1 = (float) $c[6];
            $y1 = (float) $c[7];
            $x2 = $x1 + (float) $c[8];
            $y2 = $y1 + (float) $c[9];
            if (!isset($groups[$key])) {
                $groups[$key] = array('words' => array(), 'x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2);
            }
            $g = &$groups[$key];
            $g['words'][] = $text;
            $g['x1'] = min($g['x1'], $x1);
            $g['y1'] = min($g['y1'], $y1);
            $g['x2'] = max($g['x2'], $x2);
            $g['y2'] = max($g['y2'], $y2);
            unset($g);
        }
        if ($w <= 0 || $h <= 0) return array();

        $lines = array();
        foreach ($groups as $g) {
            $lines[] = self::lineBox(implode(' ', $g['words']), $g['x1'], $g['y1'], $g['x2'], $g['y2'], $w, $h);
        }

        return $lines;
    }


    /**
     * Ред текст с правоъгълника си в проценти
     */
    protected static function lineBox($text, $x1, $y1, $x2, $y2, $w, $h)
    {
        return array('text' => $text, 'x' => round($x1 * 100 / $w, 2), 'y' => round($y1 * 100 / $h, 2),
            'width' => round(($x2 - $x1) * 100 / $w, 2), 'height' => round(($y2 - $y1) * 100 / $h, 2));
    }


    /**
     * Изпълнява pdfform.py и връща JSON резултата му
     *
     * @param string $dir
     * @param array  $args
     * @param string $outJson
     *
     * @return array
     */
    protected static function runPython($dir, $args, $outJson)
    {
        $Script = self::script($dir);
        $Script->setProgram('pdfformpy', pdfform_Setup::get('PYTHON_PATH'));
        $Script->setParam('SCRIPT', getFullPath('pdfform/py/pdfform.py'));
        $line = 'pdfformpy -I [#SCRIPT#]';
        foreach (array_values($args) as $i => $arg) {
            $Script->setParam("ARG{$i}", $arg);
            $line .= " [#ARG{$i}#]";
        }
        $Script->lineExec($line, array('LANG' => 'en_US.UTF-8', 'errFilePath' => $dir . 'python.err'));
        $Script->setCheckProgramsArr('pdfformpy');

        if ($Script->run(false) === false) {

            return array('error' => 'PDF forms are unavailable: pypdf is not installed on the server. Ask an administrator to initialize the pdfform package.', 'reason' => 'unavailable');
        }

        $res = is_file($outJson) ? json_decode((string) file_get_contents($outJson), true) : null;
        if (!is_array($res)) {
            log_System::add('pdfform_Api', 'Грешка в pdfform.py: ' . (string) @file_get_contents($dir . 'python.err'), null, 'err');

            return array('error' => 'The PDF could not be processed; technical details are in the system log.', 'reason' => 'failed');
        }
        if (!empty($res['error'])) {
            if (!empty($res['detail'])) {
                log_System::add('pdfform_Api', 'pdfform.py: ' . $res['detail'], null, 'warning');
            }

            return array('error' => $res['error'], 'reason' => 'invalid_pdf');
        }

        return $res;
    }


    /**
     * Скрипт за външни програми в работната папка на заявката
     *
     * @param string $dir
     *
     * @return fconv_Script
     */
    protected static function script($dir)
    {
        $Script = cls::get('fconv_Script');
        $Script->tempDir = $dir;

        return $Script;
    }
}
