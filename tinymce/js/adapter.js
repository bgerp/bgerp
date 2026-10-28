/** TinyMCE 8 integration. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
(function () {
    'use strict';
    var sourceSequence = 0;

    /** Keeps source editing in the editor's own undo and dialog lifecycle. */
    tinymce.PluginManager.add('codemirror', function (editor) {
        /** Opens an isolated source buffer; only Save changes the document. */
        function openSource() {
            var id = 'bgerp-tiny-source-' + (++sourceSequence);
            var bookmark = editor.selection.getBookmark(2, true);
            var original = editor.getContent({source_view: true});
            var cm;
            var dialog = editor.windowManager.open({
                title: 'Source code',
                size: 'large',
                body: {type: 'panel', items: [{type: 'htmlpanel', html: '<div id="' + id + '"></div>'}]},
                buttons: [
                    {type: 'cancel', text: 'Cancel'},
                    {type: 'submit', text: 'Save', primary: true}
                ],
                onSubmit: function (api) {
                    var value = cm.getValue();
                    if (value !== original) {
                        editor.undoManager.transact(function () { editor.setContent(value); });
                        editor.nodeChanged();
                    }
                    api.close();
                },
                onClose: function () {
                    editor.focus();
                    editor.selection.moveToBookmark(bookmark);
                }
            });
            cm = CodeMirror(document.getElementById(id), {
                value: original, mode: 'text/html', lineNumbers: true, lineWrapping: false,
                indentUnit: 2, tabSize: 2, matchBrackets: true, styleActiveLine: true,
                readOnly: editor.mode.isReadOnly()
            });
            cm.setSize('100%', '60vh');
            cm.focus();
            return dialog;
        }
        editor.addCommand('mceCodeEditor', openSource);
        editor.ui.registry.addButton('code', {icon: 'sourcecode', tooltip: 'Source code', onAction: openSource});
        editor.ui.registry.addMenuItem('code', {icon: 'sourcecode', text: 'Source code', onAction: openSource});
    });

    /** Maps existing bgERP field options to the current TinyMCE API. */
    window.bgerpTinyMceRun = function (config) {
        var target = document.getElementById(config.id);
        if (!target || tinymce.get(config.id)) return;
        var extra = config.plugins || config.toolbar || '';
        var plugins = ('advlist autolink link image lists charmap codemirror ' + extra).split(/[\s,]+/);
        // These former plugins are part of TinyMCE 8 itself.
        var integrated = ['textcolor', 'colorpicker', 'print', 'paste', 'hr', 'contextmenu', 'noneditable'];
        plugins = plugins.filter(function (name, index) {
            return name && integrated.indexOf(name) === -1 && plugins.indexOf(name) === index;
        });
        if (config.fullscreen && plugins.indexOf('fullscreen') === -1) plugins.push('fullscreen');
        if (config.saveCallback && plugins.indexOf('save') === -1) plugins.push('save');
        return tinymce.init({
            target: target,
            base_url: config.base_url,
            suffix: '.min',
            license_key: 'gpl',
            language: config.language,
            language_url: config.language_url,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
            branding: false,
            promotion: false,
            readonly: target.readOnly || target.disabled,
            plugins: plugins,
            toolbar: (config.toolbar ? config.toolbar + ' | ' : '') + 'redo undo | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | code',
            removed_menuitems: 'newdocument',
            setup: function (editor) {
                if (config.fullscreen) editor.on('init', function () { editor.execCommand('mceFullScreen'); });
            },
            save_onsavecallback: config.saveCallback ? function (editor) {
                getEfae().process({url: config.saveCallback}, {data: editor.getContent()});
            } : undefined
        });
    };
}());
