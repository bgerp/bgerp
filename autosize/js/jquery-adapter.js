/** bgERP jQuery bridge for Autosize 6. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
(function ($, autosize) {
    'use strict';

    /** Keeps existing forms, hidden groups and dynamic rows on the same API. */
    $.fn.autosize = function (options) {
        options = options || {};
        return this.each(function () {
            var field = this;
            if (field.nodeName !== 'TEXTAREA') return;
            var $field = $(field);
            if ($field.data('autosize')) {
                autosize.update(field);
                return;
            }
            var original = {minHeight: field.style.minHeight, maxHeight: field.style.maxHeight};
            var style = window.getComputedStyle(field);
            // The bundled bgERP versions grow without shrinking on deletion.
            field.style.minHeight = Math.max(parseFloat(style.minHeight) || 0, parseFloat(style.height) || 0) + 'px';
            if (typeof options.maxHeight === 'number' && options.maxHeight > 0) {
                var box = style.boxSizing === 'border-box' ? $field.outerHeight() - $field.height() : 0;
                field.style.maxHeight = (options.maxHeight + box) + 'px';
            }
            $field.data('autosize', true);
            $field.on('autosize:resized.bgerpAutosize', function () {
                field.style.minHeight = window.getComputedStyle(field).height;
                if (typeof options.callback === 'function') options.callback.call(field, field);
            });
            $field.on('autosize.resize.bgerpAutosize autosize.resizeIncludeStyle.bgerpAutosize', function () {
                autosize.update(field);
            });
            $field.on('autosize.destroy.bgerpAutosize', function () {
                autosize.destroy(field);
                $field.off('.bgerpAutosize').removeData('autosize').css(original);
            });
            autosize(field);
        });
    };
}(jQuery, window.autosize));
