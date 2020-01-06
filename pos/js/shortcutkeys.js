/**
 * ------------------------------------------------------------------------------------------------
 * Rogel Delgado Mesa, 2019
 * rogeldelgadomesa@gmail.com
 * ------------------------------------------------------------------------------------------------
 */
 
// KEYCODES //
var BACK_SPACE = 8; 		// BACKSPACE
var TAB = 9; 			// TAB
var RETURN = 13; 		// ENTER
var SHIFT = 16; 		// SHIFT
var CONTROL = 17; 		// CTRL
var ALT = 18; 			// ALT
var PAUSE = 19; 		// PAUSE/BREAK
var CAPS_LOCK = 20; 		// CAPS LOCK
var ESCAPE = 27; 		// ESCAPE
var PAGE_UP = 33; 		// PAGE UP
var PAGE_DOWN = 34; 		// PAGE DOWN
var END = 35; 			// END
var HOME = 36; 			// HOME
var LEFT = 37; 			// LEFT ARROW
var UP = 38; 			// UP ARROW
var RIGHT = 39; 		// RIGHT ARROW
var DOWN = 40; 			// DOWN ARROW
var INSERT = 45; 		// INSERT
var DELETE = 46; 		// DELETE
var N0 = 48; 			// 0
var N1 = 49; 			// 1
var N2 = 50; 			// 2
var N3 = 51; 			// 3
var N4 = 52; 			// 4
var N5 = 53; 			// 5
var N6 = 54; 			// 6
var N7 = 55; 			// 7
var N8 = 56; 			// 8
var N9 = 57; 			// 9
var A = 65; 			// A
var B = 66; 			// B
var C = 67; 			// C
var D = 68; 			// D
var E = 69; 			// E
var F = 70; 			// F
var G = 71; 			// G
var H = 72; 			// H
var I = 73; 			// I
var J = 74; 			// J
var K = 75; 			// K
var L = 76; 			// L
var M = 77; 			// M
var N = 78; 			// N
var O = 79; 			// O
var P = 80; 			// P
var Q = 81; 			// Q
var R = 82; 			// R
var S = 83; 			// S
var T = 84; 			// T
var U = 85; 			// U
var V = 86; 			// V
var W = 87; 			// W
var X = 88; 			// X
var Y = 89; 			// Y
var Z = 90; 			// Z
var WIN = 91; 			// LEFT WINDOW KEY
var WIN = 92; 			// RIGHT WINDOW KEY
var CONTEXT_MENU = 93; 		// SELECT KEY
var NUMPAD0 = 96; 		// NUMPAD 0
var NUMPAD1 = 97; 		// NUMPAD 1
var NUMPAD2 = 98; 		// NUMPAD 2
var NUMPAD3 = 99; 		// NUMPAD 3
var NUMPAD4 = 100; 		// NUMPAD 4
var NUMPAD5 = 101; 		// NUMPAD 5
var NUMPAD6 = 102; 		// NUMPAD 6
var NUMPAD7 = 103; 		// NUMPAD 7
var NUMPAD8 = 104; 		// NUMPAD 8
var NUMPAD9 = 105; 		// NUMPAD 9
var MULTIPLY = 106; 		// MULTIPLY
var ADD = 107; 			// ADD
var SUBTRACT = 109; 		// SUBTRACT
var DECIMAL = 110; 		// DECIMAL POINT
var DIVIDE = 111; 		// DIVIDE
var F1 = 112; 			// F1
var F2 = 113; 			// F2
var F3 = 114; 			// F3
var F4 = 115; 			// F4
var F5 = 116; 			// F5
var F6 = 117; 			// F6
var F7 = 118; 			// F7
var F8 = 119; 			// F8
var F9 = 120; 			// F9
var F10 = 121; 			// F10
var F11 = 122; 			// F11
var F12 = 123; 			// F12
var NUM_LOCK = 144; 		// NUM LOCK
var SCROLL_LOCK = 145; 		// SCROLL LOCK
var VOLUME_UP = 186; 		// SEMI-COLON
var VOLUME_UP = 187; 		// EQUAL SIGN
var COMMA = 188; 		// COMMA
var COMMA = 189; 		// DASH
var PERIOD = 190; 		// PERIOD
var SLASH = 191; 		// FORWARD SLASH
var BACK_QUOTE = 192; 		// GRAVE ACCENT
var OPEN_BRACKET = 219; 	// OPEN BRACKET
var BACK_SLASH = 220; 		// BACK SLASH
var CLOSE_BRACKET = 221; 	// CLOSE BRAKET
var QUOTE = 222; 		// SINGLE QUOTE

(function( $ ) {

	 // VARIABLES //
	var ShiftMod = false;
	var CtrlMod = false;
	var AltMod = false;

	$.fn.setShortcutKey = function(mod, key, func){
		try {

			$(this).keydown(function(e){

				 // Modifier down (SHIFT, CONTROL, ALT)
				if(e.keyCode == 16) {
					ShiftMod = true;
				}else if(e.keyCode == 17) {
					CtrlMod = true;
				}else if(e.keyCode == 18) {
					AltMod = true;
					hideHints();
					e.preventDefault();
				}

				 // Check key
				if (e.keyCode == key) {
					 // Verify assignment of modifier
					if (mod == null || mod == 0 || mod == key){

							func();
					}else{
						 // Verify modifier
						if (mod == 16){
							if (ShiftMod == true){
								func();
							}
						}else if (mod == 17){
							if (CtrlMod == true){
								func();
							}
						}else if (mod == 18){
							if (AltMod == true){
								func();
							}
						}
					}
				}

				 // Modifier up (SHIFT, CONTROL, ALT)
				$(this).keyup(function(e) {

					e.preventDefault();

					if(e.keyCode == 16) {
						ShiftMod = false;
					}else if(e.keyCode == 17) {
						CtrlMod = false;
					}else if(e.keyCode == 18) {
						AltMod = false;
					}

				});



			});


		}catch(e){

			console.log(e);

		}
	};

}( jQuery ));
