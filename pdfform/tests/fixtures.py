"""Synthetic PDF fixtures for pdfform/tests/forms.php.

Usage: fixtures.py fields|crop OUT.pdf
  fields - one text field repeated on two pages, hidden (/F 2) and NoView (/F 32) widgets
           with values and appearances, and a widget with only the Invisible flag (/F 1)
  crop   - MediaBox [0 0 600 800] with CropBox [100 100 500 700]; the second page is also
           rotated by 90 degrees; each page has text and a field at the text position
"""

import sys

from pypdf import PdfWriter
from pypdf.generic import (ArrayObject, DecodedStreamObject, DictionaryObject, FloatObject,
                           NameObject, NumberObject, RectangleObject, TextStringObject)


def font(w):
    return w._add_object(DictionaryObject({
        NameObject('/Type'): NameObject('/Font'), NameObject('/Subtype'): NameObject('/Type1'),
        NameObject('/BaseFont'): NameObject('/Helvetica'), NameObject('/Encoding'): NameObject('/WinAnsiEncoding')}))


def stream(w, data, extra=None):
    s = DecodedStreamObject()
    s.set_data(data.encode('latin-1'))
    if extra:
        s.update(extra)
    return w._add_object(s)


def text_page(w, width, height, items, cropbox=None, rotate=0):
    p = w.add_blank_page(width, height)
    p[NameObject('/Resources')] = DictionaryObject({NameObject('/Font'): DictionaryObject({NameObject('/F1'): font(w)})})
    p[NameObject('/Contents')] = stream(w, ''.join('BT /F1 12 Tf %g %g Td (%s) Tj ET\n' % i for i in items))
    if cropbox:
        p.cropbox = RectangleObject(cropbox)
    if rotate:
        p.rotate(rotate)
    return p


def widget(w, page, rect, extra):
    d = DictionaryObject({NameObject('/Type'): NameObject('/Annot'), NameObject('/Subtype'): NameObject('/Widget'),
                          NameObject('/Rect'): ArrayObject([FloatObject(c) for c in rect]),
                          NameObject('/P'): page.indirect_reference, NameObject('/F'): NumberObject(4)})
    d.update(extra)
    ref = w._add_object(d)
    annots = page.get('/Annots') or ArrayObject()
    annots.append(ref)
    page[NameObject('/Annots')] = annots
    return ref


def text_field(name, value, flags=None):
    d = {NameObject('/FT'): NameObject('/Tx'), NameObject('/T'): TextStringObject(name),
         NameObject('/V'): TextStringObject(value), NameObject('/DA'): TextStringObject('/Helv 10 Tf 0 g')}
    if flags is not None:
        d[NameObject('/F')] = NumberObject(flags)
    return d


def appearance(w, helv, text):
    return DictionaryObject({NameObject('/N'): stream(w, 'BT /Helv 10 Tf 2 5 Td (%s) Tj ET' % text, {
        NameObject('/Type'): NameObject('/XObject'), NameObject('/Subtype'): NameObject('/Form'),
        NameObject('/BBox'): ArrayObject([FloatObject(c) for c in (0, 0, 200, 20)]),
        NameObject('/Resources'): DictionaryObject({NameObject('/Font'): DictionaryObject({NameObject('/Helv'): helv})})})})


def acroform(w, fields, helv):
    w._root_object[NameObject('/AcroForm')] = w._add_object(DictionaryObject({
        NameObject('/Fields'): ArrayObject(fields), NameObject('/DA'): TextStringObject('/Helv 0 Tf 0 g'),
        NameObject('/DR'): DictionaryObject({NameObject('/Font'): DictionaryObject({NameObject('/Helv'): helv})})}))


def main(kind, out):
    w = PdfWriter()
    helv = font(w)
    if kind == 'crop':
        fields = []
        for name, rotate in (('cropfield', 0), ('croprot', 90)):
            p = text_page(w, 600, 800, [(150, 650, name.upper())], [100, 100, 500, 700], rotate)
            fields.append(widget(w, p, [150, 645, 230, 662], text_field(name, '')))
        acroform(w, fields, helv)
    else:
        p1 = text_page(w, 595, 842, [(50, 780, 'Page one')])
        p2 = text_page(w, 595, 842, [(50, 780, 'Page two')])
        parent = DictionaryObject(text_field('shared', ''))
        pref = w._add_object(parent)
        kids = [widget(w, p1, [100, 700, 300, 720], {NameObject('/Parent'): pref}),
                widget(w, p2, [200, 500, 400, 520], {NameObject('/Parent'): pref})]
        parent[NameObject('/Kids')] = ArrayObject(kids)
        hidden = text_field('hidden', 'HIDDEN_VALUE', 2)
        hidden[NameObject('/AP')] = appearance(w, helv, 'HIDDEN_AP')
        noview = text_field('noview', 'NOVIEW_VALUE', 32)
        noview[NameObject('/AP')] = appearance(w, helv, 'NOVIEW_AP')
        others = [widget(w, p1, [100, 600, 300, 620], hidden),
                  widget(w, p1, [100, 560, 300, 580], noview),
                  widget(w, p1, [100, 520, 300, 540], text_field('invisible', 'INVISIBLE_VALUE', 1 | 4))]
        acroform(w, [pref] + others, helv)
    w.write(out)


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
