<?php defined('BLUDIT') or die('Bludit CMS.');

//This function converts url links to embed codes
function url2embed( $post ) 
{
	
	$gl_video_width = 604;
	$gl_video_height = 505;
			
	preg_match_all('/<a href=\"(.*)\">(.*)<\/a>/', $post, $out);
	
	$links = $out['1'];
		
	foreach ($links as $link) 
	{
		$start = 0;
		$end = 0;
				
		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[\w\-?&!#=,;]+/[\w\-?&!#=/,;]+/|(?:v|e(?:mbed)?)/|[\w\-?&!#=,;]*[?&]v=)|youtu\.be/)([\w-]{11})(?:[^\w-]|\Z)%i', $link, $output)) 
		{
				
			$yt_ID = $output['1'];
			$yt_URL = 'https://' . $output['0'];
				
			if (preg_match("/start=([0-9]+)/", $link, $un))
				$start = $un['1'];
				
			if (preg_match("/end=([0-9]+)/", $link, $un))
				$end = $un['1'];
				
			$wto = '<iframe class="youtube-player" src="https://www.youtube.com/embed/' . $yt_ID . '?version=3&amp;rel=1&amp;fs=1&amp;autohide=2&amp;showsearch=0&amp;showinfo=1&amp;iv_load_policy=1';
					
			if (!empty($start))
				$wto .= '&amp;start=' . $start;
				
			if (!empty($end))
				$wto .= '&amp;end=' . $end;
				
			$wto .= '&amp;wmode=transparent" allowfullscreen="true" allow="autoplay; encrypted-media" width="100%" height="404" style="border: 0px none; display: block; margin: 0px;"></iframe>';
				
			$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $wto, $post);
		}
			
		if (preg_match("/dailymotion/", $link)) 
		{
				
			preg_match("/\/video\/(.+)/",$link, $matches);
			preg_match("/video\/([^_]+)/", $link, $matche);
				
			$yt_id = (!empty($matche[1])) ? $matche[1] : $matches[1];
				
			$embed = '<div class="flex-video"><iframe frameborder="0" width="100%" height="400" src="//www.dailymotion.com/embed/video/' . $yt_id . '" allowfullscreen></iframe></div>';
				
			$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $embed, $post);
		}
			
		if ( preg_match('#https?://(player\.)?vimeo\.com(/video)?/(\d+)#i', $link, $matches) ) 
		{
							
			$yt_id = trim($matches[3]);
							
			$embed = '<div class="flex-video"><iframe src="https://player.vimeo.com/video/' . $yt_id . '" width="100%" height="400" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>';
				
			$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $embed, $post);
		}
			
		if (preg_match("/facebook.com\/(.*?)/", $link) || (preg_match('/facebook\.com/i', $link))) 
		{
			$embed = '<div class="flex-video"><iframe src="https://www.facebook.com/plugins/video.php?href=' . urlencode( $link ) . '&show_text=0&width=367" width="367" height="476" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe></div>';
				
			$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $embed, $post);
		}
			
		if ( preg_match('#^https?://twitter\.com/(?:\#!/)?(\w+)/status(es)?/(\d+)$#i', $link, $matches ) ) 
		{
								
			$status_id = $matches[3];
								
			$uri = 'https://api.twitter.com/1/statuses/oembed.json?id=' . $status_id . '&omit_script=true';
				
			$source = @file_get_contents($uri, true); //getting the file content
				
			if ( ( $source !== false ) && ( !empty( $source ) ) ) {
				
				$decode = json_decode($source, true); //getting the file content as array
				
				$embed = $decode['html'] . '<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
				
				unset( $source, $decode );
				
				$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $embed, $post);
			}
				
		}
			
	}
		
		
	//Lazy Load for YouTube Only
	$lazyIframe2 = '<iframe
					  width="100%"
					  height="' . $gl_video_height . '"
					  src="$3"					  srcdoc="<style>*{padding:0;margin:0;overflow:hidden}html,body{height:100%}img,span{position:absolute;width:100%;top:0;bottom:0;margin:auto}span{height:1.5em;text-align:center;font:48px/1.5 sans-serif;color:white;text-shadow:0 0 0.5em black}</style><a href=\'$3\'><img src=\'$2\' alt=\'Youtube Video\'><span>▶</span></a>"
					  frameborder="0"
					  allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
					  allowfullscreen
					  title="Youtube Video"
					></iframe>';
		
	//Maybe the post doesn't has any URL links. For now, search only for youtube URLs
	$post = preg_replace(
			"/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
			"$lazyIframe",
			$post );
			
	//Convert youtube embed code from Blogger
	$pat = '/(<div class=\"separator\" style=\"clear: both; text-align: center;\">)?<iframe.+data-thumbnail-src=[\'"]([^\'"]+)[\'"].+src=[\'"]([^\'"]+)[\'"].*><\/iframe>(<\/div>)?/';
		
	$post = preg_replace($pat, $lazyIframe2, $post );
		
	return $post;
}

//
//
//The functions below taken from WordPress
//
//
//
	if ( !function_exists( '_autop_newline_preservation_helper' ) )
	{
		function _autop_newline_preservation_helper( $matches ) {
			return str_replace( "\n", "<WPPreserveNewline />", $matches[0] );
		}
	}
	
if ( !function_exists( 'get_html_split_regex' ) )
{
	function get_html_split_regex() 
	{
		static $regex;
	 
		if ( ! isset( $regex ) ) {
			$comments =
				  '!'           // Start of comment, after the <.
				. '(?:'         // Unroll the loop: Consume everything until --> is found.
				.     '-(?!->)' // Dash not followed by end of comment.
				.     '[^\-]*+' // Consume non-dashes.
				. ')*+'         // Loop possessively.
				. '(?:-->)?';   // End of comment. If not found, match all input.
	 
			$cdata =
				  '!\[CDATA\['  // Start of comment, after the <.
				. '[^\]]*+'     // Consume non-].
				. '(?:'         // Unroll the loop: Consume everything until ]]> is found.
				.     '](?!]>)' // One ] not followed by end of comment.
				.     '[^\]]*+' // Consume non-].
				. ')*+'         // Loop possessively.
				. '(?:]]>)?';   // End of comment. If not found, match all input.
	 
			$escaped =
				  '(?='           // Is the element escaped?
				.    '!--'
				. '|'
				.    '!\[CDATA\['
				. ')'
				. '(?(?=!-)'      // If yes, which type?
				.     $comments
				. '|'
				.     $cdata
				. ')';
	 
			$regex =
				  '/('              // Capture the entire match.
				.     '<'           // Find start of element.
				.     '(?'          // Conditional expression follows.
				.         $escaped  // Find end of escaped element.
				.     '|'           // ... else ...
				.         '[^>]*>?' // Find end of normal element.
				.     ')'
				. ')/';
		}
	 
		return $regex;
	}
}

if ( !function_exists( 'wp_html_split' ) )
{
	function wp_html_split( $input ) {
		return preg_split( get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
	}
}
if ( !function_exists( 'wp_replace_in_html_tags' ) )
{
	function wp_replace_in_html_tags( $haystack, $replace_pairs ) {
		// Find all elements.
		$textarr = wp_html_split( $haystack );
		$changed = false;
	 
		// Optimize when searching for one item.
		if ( 1 === count( $replace_pairs ) ) {
			// Extract $needle and $replace.
			foreach ( $replace_pairs as $needle => $replace );
	 
			// Loop through delimiters (elements) only.
			for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
				if ( false !== strpos( $textarr[$i], $needle ) ) {
					$textarr[$i] = str_replace( $needle, $replace, $textarr[$i] );
					$changed = true;
				}
			}
		} else {
			// Extract all $needles.
			$needles = array_keys( $replace_pairs );
	 
			// Loop through delimiters (elements) only.
			for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
				foreach ( $needles as $needle ) {
					if ( false !== strpos( $textarr[$i], $needle ) ) {
						$textarr[$i] = strtr( $textarr[$i], $replace_pairs );
						$changed = true;
						// After one strtr() break out of the foreach loop and look at next element.
						break;
					}
				}
			}
		}
	 
		if ( $changed ) {
			$haystack = implode( $textarr );
		}
	 
		return $haystack;
	}
}

if ( !function_exists( 'wpautop' ) )
{
	function wpautop( $pee, $br = true ) {
		$pre_tags = array();

		if ( trim($pee) === '' )
			return '';

		// Just to make things a little easier, pad the end.
		$pee = $pee . "\n";

		/*
		 * Pre tags shouldn't be touched by autop.
		 * Replace pre tags with placeholders and bring them back after autop.
		 */
		if ( strpos($pee, '<pre') !== false ) {
			$pee_parts = explode( '</pre>', $pee );
			$last_pee = array_pop($pee_parts);
			$pee = '';
			$i = 0;

			foreach ( $pee_parts as $pee_part ) {
				$start = strpos($pee_part, '<pre');

				// Malformed html?
				if ( $start === false ) {
					$pee .= $pee_part;
					continue;
				}

				$name = "<pre wp-pre-tag-$i></pre>";
				$pre_tags[$name] = substr( $pee_part, $start ) . '</pre>';

				$pee .= substr( $pee_part, 0, $start ) . $name;
				$i++;
			}

			$pee .= $last_pee;
		}
		// Change multiple <br>s into two line breaks, which will turn into paragraphs.
		$pee = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee);

		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

		// Add a double line break above block-level opening tags.
		$pee = preg_replace('!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee);

		// Add a double line break below block-level closing tags.
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);

		// Standardize newline characters to "\n".
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee);

		// Find newlines in all elements and add placeholders.
		$pee = wp_replace_in_html_tags( $pee, array( "\n" => " <!-- wpnl --> " ) );

		// Collapse line breaks before and after <option> elements so they don't get autop'd.
		if ( strpos( $pee, '<option' ) !== false ) {
			$pee = preg_replace( '|\s*<option|', '<option', $pee );
			$pee = preg_replace( '|</option>\s*|', '</option>', $pee );
		}

		/*
		 * Collapse line breaks inside <object> elements, before <param> and <embed> elements
		 * so they don't get autop'd.
		 */
		if ( strpos( $pee, '</object>' ) !== false ) {
			$pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
			$pee = preg_replace( '|\s*</object>|', '</object>', $pee );
			$pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
		}

		/*
		 * Collapse line breaks inside <audio> and <video> elements,
		 * before and after <source> and <track> elements.
		 */
		if ( strpos( $pee, '<source' ) !== false || strpos( $pee, '<track' ) !== false ) {
			$pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
			$pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
			$pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
		}

		// Collapse line breaks before and after <figcaption> elements.
		if ( strpos( $pee, '<figcaption' ) !== false ) {
			$pee = preg_replace( '|\s*(<figcaption[^>]*>)|', '$1', $pee );
			$pee = preg_replace( '|</figcaption>\s*|', '</figcaption>', $pee );
		}

		// Remove more than two contiguous line breaks.
		$pee = preg_replace("/\n\n+/", "\n\n", $pee);

		// Split up the contents into an array of strings, separated by double line breaks.
		$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);

		// Reset $pee prior to rebuilding.
		$pee = '';

		// Rebuild the content as a string, wrapping every bit with a <p>.
		foreach ( $pees as $tinkle ) {
			$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
		}

		// Under certain strange conditions it could create a P of entirely whitespace.
		$pee = preg_replace('|<p>\s*</p>|', '', $pee);

		// Add a closing <p> inside <div>, <address>, or <form> tag if missing.
		$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);

		// If an opening or closing block element tag is wrapped in a <p>, unwrap it.
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

		// In some cases <li> may get wrapped in <p>, fix them.
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee);

		// If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);

		// If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);

		// If an opening or closing block element tag is followed by a closing <p> tag, remove it.
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

		// Optionally insert line breaks.
		if ( $br ) {
			// Replace newlines that shouldn't be touched with a placeholder.
			//$pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', '_autop_newline_preservation_helper', $pee);

			// Normalize <br>
			$pee = str_replace( array( '<br>', '<br/>' ), '<br />', $pee );

			// Replace any new line characters that aren't preceded by a <br /> with a <br />.
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee);

			// Replace newline placeholders with newlines.
			$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
		}

		// If a <br /> tag is after an opening or closing block tag, remove it.
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);

		// If a <br /> tag is before a subset of opening or closing block tags, remove it.
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );

		// Replace placeholder <pre> tags with their original content.
		if ( !empty($pre_tags) )
			$pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);

		// Restore newlines in all elements.
		if ( false !== strpos( $pee, '<!-- wpnl -->' ) ) {
			$pee = str_replace( array( ' <!-- wpnl --> ', '<!-- wpnl -->' ), "\n", $pee );
		}

		return $pee;
	}
}
