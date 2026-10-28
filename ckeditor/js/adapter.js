/**
 * bgERP integration for the locally bundled CKEditor 5.
 * Copyright (c) 2026 Experta EOOD. License: GPL-3.0-or-later.
 */
(function (window) {
    'use strict';

    var instances = {};

    /** Creates one editor per textarea and keeps its submitted value in sync. */
    window.bgerpCkeditorRun = function (id, options) {
        var source = document.getElementById(id);
        if (!source) {
            return Promise.resolve(null);
        }
        if (instances[id] && instances[id].source === source) {
            return instances[id].promise;
        }

        var C = window.CKEDITOR;
        var entry = { source: source };
        instances[id] = entry;
        entry.promise = C.ClassicEditor.create({
            attachTo: source,
            licenseKey: 'GPL',
            language: options.language,
            plugins: [
                C.Alignment, C.AutoImage, C.Autoformat, C.AutoLink,
                C.Base64UploadAdapter, C.BlockQuote, C.Bold, C.Code, C.CodeBlock,
                C.Essentials, C.FontBackgroundColor, C.FontColor, C.FontSize,
                C.Heading, C.Highlight, C.HorizontalLine, C.Image,
                C.ImageCaption, C.ImageResize, C.ImageStyle, C.ImageToolbar,
                C.ImageUpload, C.Indent, C.IndentBlock, C.Italic, C.Link,
                C.List, C.ListProperties, C.MediaEmbed, C.Mention, C.Paragraph,
                C.PasteFromOffice, C.Subscript, C.Table, C.TableCellProperties,
                C.TableProperties, C.TableToolbar, C.TextTransformation
            ],
            toolbar: {
                items: [
                    'heading', '|', 'bold', 'italic', 'link', 'bulletedList',
                    'numberedList', '|', 'indent', 'outdent', '|', 'uploadImage',
                    'blockQuote', 'insertTable', 'undo', 'redo', 'highlight',
                    'codeBlock', 'code', 'fontSize', 'fontColor',
                    'fontBackgroundColor', 'horizontalLine', 'alignment'
                ]
            },
            image: {
                toolbar: [
                    'imageTextAlternative', '|', 'imageStyle:alignBlockLeft',
                    'imageStyle:block', 'imageStyle:alignBlockRight'
                ],
                styles: [ 'block', 'alignBlockLeft', 'alignBlockRight' ]
            },
            table: { contentToolbar: [ 'tableColumn', 'tableRow', 'mergeTableCells' ] },
            list: { properties: { styles: true, startIndex: true, reversed: true } }
        }).then(function (editor) {
            if (options.readOnly || source.readOnly || source.disabled) {
                editor.enableReadOnlyMode('bgerp');
            }
            editor.model.document.on('change:data', function () {
                editor.updateSourceElement();
            });
            return editor;
        }).catch(function (error) {
            delete instances[id];
            console.error('CKEditor:', error);
            return null;
        });

        return entry.promise;
    };
}(window));
