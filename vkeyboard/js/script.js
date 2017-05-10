function keyboardAction() {
	$('.textField').keyboard({
		layout: 'bulgarian-qwerty',
		usePreview: false,
		autoAccept : true,
		display: {
			'meta1'  : '\u2328:EN <> Bulgarian Phonetic',
			'alt'  : 'Alt:EN <> БДС',
			'bksp'   :  "\u2190",
		},

	});

	$('.numberField').keyboard({
		display: {
			'bksp'   :  "\u2190",
			'accept' : 'return'
		},
		layout: 'custom',
		customLayout: {
			'normal' : [
				'1 2 3 4 5 6 7 8 9 0 {bksp} {a}',
			]
		},
		usePreview: false,
		autoAccept : true
	});
}
