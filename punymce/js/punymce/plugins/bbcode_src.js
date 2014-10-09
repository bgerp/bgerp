punymce.plugins.BBCode = function(ed) {
	// Convert XML into BBCode
	ed.onGetContent.add(function(ed, o) {
		if (o.format == 'bbcode' || o.save) {
			// example: <strong> to [b]
			punymce.each([
				[/<a href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[url=$1]$2[/url]"],
				[/<font.*?color=\"([^\"]+)\".*?>(.*?)<\/font>/gi,"[color=$1]$2[/color]"],
				[/<img.*?src=\"([^\"]+)\".*?\/>/gi,"[img]$1[/img]"],
				[/<(br\s*\/)>/gi, "\n"],
				[/<(\/?)(strong|b)[^>]*>/gi, "[$1b]"],
				[/<(\/?)(em|i)[^>]*>/gi, "[$1i]"],
				[/<(\/?)u[^>]*>/gi, "[$1u]"],
				[/<(\/?)(code|pre)[^>]*>/gi, "[$1code]"],
				[/<(\/?)(span.*?class=\"quote\")[^>]*>(.*?)<\/span>/gi, "[$1quote]$3[/quote]"],
				[/<p>/gi, ""],
				[/<\/p>/gi, "\n"],
				[/&quot;/gi, "\""],
				[/&lt;/gi, "<"],
				[/&gt;/gi, ">"],
				[/&amp;/gi, "&"],
				[/<[^>]+>/gi, ""]
			], function (v) {
				o.content = o.content.replace(v[0], v[1]);
			});
		}
	});

	ed.onSetContent.add(function(ed, o) {
		if (o.format == 'bbcode' || o.load) {
			// example: [b] to <strong>
			punymce.each([
				[/\n/gi,"<br />"],
				[/\[(\/?)b\]/gi,"<$1strong>"],
				[/\[(\/?)i\]/gi,"<$1em>"],
				[/\[(\/?)u\]/gi,"<$1u>"],
				[/\[(\/?)code\]/gi,"<$1pre>"],
				[/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>"],
				[/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>"],
				[/\[img\](.*?)\[\/img\]/gi,"<img src=\"$1\" />"],
				[/\[color=(.*?)\](.*?)\[\/color\]/gi,'<font color="$1">$2</font>'],
				[/\[quote.*?\](.*?)\[\/quote\]/gi,'<span class="quote">$1</span>']
			], function (v) {
				o.content = o.content.replace(v[0], v[1]);
			});
		}
	});
};
