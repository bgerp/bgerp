#!/usr/bin/env python3
"""Reads and fills PDF form fields with pypdf for the bgERP pdfform package.

Usage:
  pdfform.py info IN.pdf OUT.json
  pdfform.py fill IN.pdf SPEC.json OUT.pdf OUT.json

SPEC.json: {"fields": {name: value}, "overlay": path or null, "flatten": bool}
Coordinates are percents of the displayed (rotated, cropped) page, origin top-left.
"""

import json
import sys

from pypdf import PdfReader, PdfWriter, Transformation
from pypdf.generic import (ArrayObject, BooleanObject, DecodedStreamObject,
                           DictionaryObject, NameObject, TextStringObject)

FF_READONLY = 1
FF_REQUIRED = 1 << 1
FF_MULTILINE = 1 << 12
FF_RADIO = 1 << 15
FF_PUSHBUTTON = 1 << 16
FF_COMBO = 1 << 17
FF_EDIT = 1 << 18
FF_MULTISELECT = 1 << 21
FF_COMB = 1 << 24

# Annotation flags that hide a widget; Invisible (1) applies only to unknown annotation types
ANNOT_HIDDEN = 1 << 1
ANNOT_NOVIEW = 1 << 5


class FormError(Exception):
    pass


def obj(value):
    return value.get_object() if hasattr(value, 'get_object') else value


def inherited(field, key):
    """Value of a field attribute, inherited through /Parent."""
    seen = 0
    while field is not None and seen < 50:
        if key in field:
            return obj(field[key])
        field = obj(field.get('/Parent'))
        seen += 1
    return None


def terminal_field(widget):
    """The field dictionary that holds /T for a widget (the widget itself when merged)."""
    node = widget
    while node is not None and '/T' not in node:
        node = obj(node.get('/Parent'))
    return node


def full_name(field):
    parts = []
    node = field
    seen = 0
    while node is not None and seen < 50:
        if '/T' in node:
            parts.append(str(obj(node['/T'])))
        node = obj(node.get('/Parent'))
        seen += 1
    return '.'.join(reversed(parts))


def page_geometry(page):
    box = page.cropbox
    rotate = int(page.rotation or 0) % 360
    if rotate not in (0, 90, 180, 270):
        rotate = 0
    x0, y0, x1, y1 = float(box.left), float(box.bottom), float(box.right), float(box.top)
    w, h = x1 - x0, y1 - y0
    dw, dh = (h, w) if rotate in (90, 270) else (w, h)

    return {'x0': x0, 'y0': y0, 'x1': x1, 'y1': y1, 'w': w, 'h': h,
            'rotate': rotate, 'dw': dw, 'dh': dh}


def to_display(geo, x, y):
    """User-space point to displayed top-left coordinates in points."""
    u, v = x - geo['x0'], geo['y1'] - y
    r = geo['rotate']
    if r == 90:
        return geo['h'] - v, u
    if r == 180:
        return geo['w'] - u, geo['h'] - v
    if r == 270:
        return v, geo['w'] - u

    return u, v


def rect_percent(geo, rect):
    try:
        x1, y1, x2, y2 = [float(obj(c)) for c in rect]
    except (TypeError, ValueError):
        return None
    ax, ay = to_display(geo, x1, y1)
    bx, by = to_display(geo, x2, y2)
    left, top = min(ax, bx), min(ay, by)
    width, height = abs(bx - ax), abs(by - ay)

    return [round(left * 100 / geo['dw'], 2), round(top * 100 / geo['dh'], 2),
            round(width * 100 / geo['dw'], 2), round(height * 100 / geo['dh'], 2)]


def overlay_ctm(geo):
    """Maps an overlay page of the displayed size onto the user space of the page."""
    r = geo['rotate']
    if r == 90:
        return (0, 1, -1, 0, geo['x1'], geo['y0'])
    if r == 180:
        return (-1, 0, 0, -1, geo['x1'], geo['y1'])
    if r == 270:
        return (0, -1, 1, 0, geo['x0'], geo['y1'])

    return (1, 0, 0, 1, geo['x0'], geo['y0'])


def on_states(widget):
    ap = obj(widget.get('/AP'))
    normal = obj(ap.get('/N')) if ap is not None else None
    states = {}
    if hasattr(normal, 'keys'):
        for key in normal.keys():
            if key != '/Off':
                states[str(key)[1:]] = key

    return states


def to_text(value):
    if value is None:
        return ''
    value = obj(value)
    if isinstance(value, NameObject):
        return str(value)[1:]
    if isinstance(value, (list, ArrayObject)):
        return [to_text(v) for v in value]
    # Some generators write UTF-8 bytes without a BOM; PDFDocEncoding then shows mojibake
    raw = getattr(value, 'original_bytes', None)
    if isinstance(value, TextStringObject) and raw and not raw.startswith((b'\xfe\xff', b'\xff\xfe', b'\xef\xbb\xbf')) \
            and any(b >= 0x80 for b in raw):
        try:
            return raw.decode('utf-8')
        except UnicodeDecodeError:
            pass

    return str(value)


def field_type(field):
    ft = inherited(field, '/FT')
    flags = int(inherited(field, '/Ff') or 0)
    if ft == '/Tx':
        return 'text'
    if ft == '/Btn':
        if flags & FF_PUSHBUTTON:
            return 'button'
        return 'radio' if flags & FF_RADIO else 'checkbox'
    if ft == '/Ch':
        return 'dropdown' if flags & FF_COMBO else 'listbox'
    if ft == '/Sig':
        return 'signature'

    return 'unknown'


def parse_da(da):
    """Font size (0 = auto) from a /DA string such as '/Helv 10 Tf 0 g'."""
    parts = str(da or '').split()
    for i, part in enumerate(parts):
        if part == 'Tf' and i >= 1:
            try:
                return float(parts[i - 1])
            except ValueError:
                return 0.0

    return 0.0


def widget_hidden(widget):
    try:
        flags = int(obj(widget.get('/F')) or 0)
    except (TypeError, ValueError):
        flags = 0

    return bool(flags & (ANNOT_HIDDEN | ANNOT_NOVIEW))


def text_widget(field, w):
    """Page, rect and the text settings of one widget of a text or choice field."""
    widget = w['widget']
    res = {'page': w['page'], 'rect': rect_percent(w['geo'], widget.get('/Rect')),
           'fontSize': parse_da(inherited(widget, '/DA') or inherited(field, '/DA'))}
    quadding = inherited(widget, '/Q')
    res['align'] = {1: 'center', 2: 'right'}.get(int(quadding or 0), 'left')
    # Text direction against the displayed page: page rotation minus the widget's /MK /R
    mk = obj(widget.get('/MK'))
    own = int(obj(mk.get('/R')) or 0) if hasattr(mk, 'get') else 0
    turn = (w['geo']['rotate'] - own) % 360
    if turn in (90, 180, 270):
        res['rotation'] = turn
    if widget_hidden(widget):
        res['hidden'] = True

    return res


def collect(pdf_pages):
    """Fields by full name with their widgets, in page order."""
    fields = {}
    order = []
    for page_no, page in enumerate(pdf_pages, start=1):
        geo = page_geometry(page)
        for annot in (obj(page.get('/Annots')) or []):
            widget = obj(annot)
            if not hasattr(widget, 'get') or widget.get('/Subtype') != '/Widget':
                continue
            field = terminal_field(widget)
            if field is None:
                continue
            name = full_name(field)
            if name not in fields:
                fields[name] = {'field': field, 'widgets': []}
                order.append(name)
            fields[name]['widgets'].append({'widget': widget, 'page': page_no, 'geo': geo})

    return fields, order


def describe(name, entry):
    field = entry['field']
    ftype = field_type(field)
    flags = int(inherited(field, '/Ff') or 0)
    first = entry['widgets'][0]
    res = {'name': name, 'type': ftype, 'value': to_text(inherited(field, '/V')),
           'page': first['page'], 'rect': rect_percent(first['geo'], first['widget'].get('/Rect'))}
    label = inherited(field, '/TU')
    if label:
        res['label'] = to_text(label)
    if flags & FF_READONLY:
        res['readonly'] = True
    if flags & FF_REQUIRED:
        res['required'] = True
    if ftype == 'text':
        if flags & FF_MULTILINE:
            res['multiline'] = True
        max_len = inherited(field, '/MaxLen')
        if max_len:
            res['maxLength'] = int(max_len)
            if flags & FF_COMB:
                res['comb'] = True
    if ftype in ('dropdown', 'listbox'):
        options = []
        for opt in (inherited(field, '/Opt') or []):
            opt = obj(opt)
            if isinstance(opt, (list, ArrayObject)) and len(opt) >= 2:
                value, label = to_text(opt[0]), to_text(opt[1])
                options.append(value if value == label else {'value': value, 'label': label})
            else:
                options.append(to_text(opt))
        res['options'] = options
        if flags & FF_EDIT:
            res['editable'] = True
        if flags & FF_MULTISELECT:
            res['multiple'] = True
    if ftype == 'checkbox':
        states = on_states(first['widget'])
        res['onState'] = next(iter(states), 'Yes')
    if ftype == 'radio':
        res['options'] = []
        res['widgets'] = []
        for w in entry['widgets']:
            for state in on_states(w['widget']):
                if state not in res['options']:
                    res['options'].append(state)
                res['widgets'].append({'value': state, 'page': w['page'],
                                       'rect': rect_percent(w['geo'], w['widget'].get('/Rect'))})
    if ftype in ('text', 'dropdown', 'listbox'):
        # Every widget repeats the value; the first one also fills the top-level keys as before
        res['widgets'] = [text_widget(field, w) for w in entry['widgets']]
        for key in ('fontSize', 'align', 'rotation'):
            if key in res['widgets'][0]:
                res[key] = res['widgets'][0][key]

    return res


def open_reader(path):
    reader = PdfReader(path)
    if reader.is_encrypted:
        try:
            if not reader.decrypt(''):
                raise FormError('The PDF is password-protected.')
        except FormError:
            raise
        except Exception:
            raise FormError('The PDF is encrypted and cannot be opened.')

    return reader


def xfa_kind(reader):
    root = reader.trailer['/Root']
    acro = obj(root.get('/AcroForm'))
    if acro is None or '/XFA' not in acro:
        return None
    has_widgets = any(obj(p.get('/Annots')) for p in reader.pages)

    return 'hybrid' if has_widgets else 'dynamic'


def cmd_info(src, out):
    reader = open_reader(src)
    pages = []
    for page in reader.pages:
        geo = page_geometry(page)
        info = {'width': round(geo['dw'], 2), 'height': round(geo['dh'], 2), 'rotate': geo['rotate']}
        media = page.mediabox
        if [round(float(v), 2) for v in (media.left, media.bottom, media.right, media.top)] != \
                [round(geo[k], 2) for k in ('x0', 'y0', 'x1', 'y1')]:
            info['cropped'] = True
        pages.append(info)
    fields, order = collect(reader.pages)
    res = {'pages': pages,
           'fields': [describe(n, fields[n]) for n in order if field_type(fields[n]['field']) != 'button'],
           'xfa': xfa_kind(reader)}
    with open(out, 'w', encoding='utf-8') as fh:
        json.dump(res, fh, ensure_ascii=False)


def set_value(entry, value):
    field = entry['field']
    ftype = field_type(field)
    widgets = [w['widget'] for w in entry['widgets']]
    if ftype in ('checkbox', 'radio'):
        chosen = None
        for w in widgets:
            states = on_states(w)
            if value in states:
                chosen = states[value]
                w[NameObject('/AS')] = chosen
            else:
                w[NameObject('/AS')] = NameObject('/Off')
        field[NameObject('/V')] = chosen if chosen is not None else NameObject('/Off')
        return
    if isinstance(value, list):
        field[NameObject('/V')] = ArrayObject([TextStringObject(str(v)) for v in value])
    else:
        field[NameObject('/V')] = TextStringObject(str(value))
    # Viewers regenerate the appearance from /V, because /NeedAppearances is set
    for w in widgets:
        if '/AP' in w:
            del w['/AP']


def normal_appearance(widget):
    """The normal appearance stream of a widget, chosen by /AS for buttons."""
    ap = obj(widget.get('/AP'))
    normal = obj(ap.get('/N')) if ap is not None else None
    if normal is None:
        return None
    if hasattr(normal, 'get_data'):
        return normal
    state = widget.get('/AS')
    stream = obj(normal.get(state)) if state is not None and state in normal else None

    return stream if hasattr(stream, 'get_data') else None


def stamp_widgets(writer, page, skip):
    """Draws the current widget appearances into the page content, except fields in skip."""
    commands = []
    xobjects = None
    for idx, annot in enumerate(obj(page.get('/Annots')) or []):
        widget = obj(annot)
        if widget.get('/Subtype') != '/Widget' or widget_hidden(widget):
            continue
        field = terminal_field(widget)
        if field is None or full_name(field) in skip:
            continue
        stream = normal_appearance(widget)
        if stream is None:
            continue
        try:
            rx1, ry1, rx2, ry2 = [float(obj(c)) for c in widget['/Rect']]
            bx1, by1, bx2, by2 = [float(obj(c)) for c in stream['/BBox']]
        except (KeyError, TypeError, ValueError):
            continue
        matrix = [float(obj(c)) for c in (stream.get('/Matrix') or [1, 0, 0, 1, 0, 0])]
        corners = [(x * matrix[0] + y * matrix[2] + matrix[4], x * matrix[1] + y * matrix[3] + matrix[5])
                   for x, y in ((bx1, by1), (bx2, by1), (bx1, by2), (bx2, by2))]
        tx1, ty1 = min(c[0] for c in corners), min(c[1] for c in corners)
        tw, th = max(c[0] for c in corners) - tx1, max(c[1] for c in corners) - ty1
        if tw <= 0 or th <= 0:
            continue
        sx, sy = (abs(rx2 - rx1)) / tw, (abs(ry2 - ry1)) / th
        if xobjects is None:
            res = obj(page.get('/Resources'))
            if res is None:
                res = DictionaryObject()
                page[NameObject('/Resources')] = res
            if '/XObject' not in res:
                res[NameObject('/XObject')] = DictionaryObject()
            xobjects = obj(res['/XObject'])
        name = NameObject('/BgFm%d' % idx)
        xobjects[name] = writer._add_object(stream)
        commands.append('q %.4f 0 0 %.4f %.4f %.4f cm %s Do Q' % (
            sx, sy, min(rx1, rx2) - tx1 * sx, min(ry1, ry2) - ty1 * sy, name))
    if not commands:
        return
    # The original content is isolated so its graphics state cannot shift the stamps
    before, after = DecodedStreamObject(), DecodedStreamObject()
    before.set_data(b'q\n')
    after.set_data(('\nQ\n' + '\n'.join(commands) + '\n').encode('latin-1'))
    contents = obj(page.get('/Contents'))
    parts = list(contents) if isinstance(contents, (list, ArrayObject)) else ([page['/Contents']] if contents is not None else [])
    page[NameObject('/Contents')] = ArrayObject([writer._add_object(before)] + parts + [writer._add_object(after)])


def cmd_fill(src, spec_path, dst, out):
    with open(spec_path, encoding='utf-8') as fh:
        spec = json.load(fh)
    reader = open_reader(src)
    writer = PdfWriter(clone_from=reader)
    root = writer._root_object
    acro = obj(root.get('/AcroForm'))

    fields, _ = collect(writer.pages)
    values = spec.get('fields') or {}
    missing = [n for n in values if n not in fields]
    if missing:
        raise FormError('Unknown fields: ' + ', '.join(missing))
    for name, value in values.items():
        set_value(fields[name], value)

    if acro is not None:
        if '/XFA' in acro:
            del acro['/XFA']
        if spec.get('flatten'):
            del root['/AcroForm']
        else:
            acro[NameObject('/NeedAppearances')] = BooleanObject(True)

    if spec.get('flatten'):
        skip = set(spec.get('drawn') or [])
        for page in writer.pages:
            annots = obj(page.get('/Annots'))
            if not annots:
                continue
            stamp_widgets(writer, page, skip)
            keep = ArrayObject([a for a in annots if obj(a).get('/Subtype') != '/Widget'])
            if keep:
                page[NameObject('/Annots')] = keep
            else:
                del page['/Annots']

    if spec.get('overlay'):
        overlay = PdfReader(spec['overlay'])
        for i, page in enumerate(writer.pages):
            if i >= len(overlay.pages):
                break
            layer = overlay.pages[i]
            # ContentStream is falsy even with content, so the stream data is checked
            contents = layer.get_contents()
            if contents is None or not contents.get_data().strip():
                continue
            page.merge_transformed_page(layer, Transformation(overlay_ctm(page_geometry(page))), over=True, expand=False)

    with open(dst, 'wb') as fh:
        writer.write(fh)
    with open(out, 'w', encoding='utf-8') as fh:
        json.dump({'ok': True, 'pages': len(writer.pages)}, fh)


def main(argv):
    out = argv[-1] if len(argv) >= 4 else None
    try:
        if len(argv) == 4 and argv[1] == 'info':
            cmd_info(argv[2], argv[3])
        elif len(argv) == 6 and argv[1] == 'fill':
            cmd_fill(argv[2], argv[3], argv[4], argv[5])
        else:
            raise FormError('Invalid arguments.')
    except FormError as e:
        err = {'error': str(e)}
    except Exception as e:  # pypdf parse errors of damaged files
        err = {'error': 'The PDF could not be processed.', 'detail': '%s: %s' % (type(e).__name__, str(e)[:300])}
    else:
        return 0
    if out:
        with open(out, 'w', encoding='utf-8') as fh:
            json.dump(err, fh, ensure_ascii=False)

    return 2


if __name__ == '__main__':
    sys.exit(main(sys.argv))
