<?php

class pluginBloggerToBludit extends Plugin {
	
	private $categories;		// Stored categories
	private $comments;			// Stored Comments
	private $posts;				// Stored posts
	private $page_pos;			// Page Position
	private $com_id;			// Coment ID
	private $site_url;			// Site URL
	private $doc;				// Disqus Comments

	public function init() 
	{
		require_once ( $this->phpPath() . 'php' . DS . 'urlify' . DS . 'URLify.php' );
		require ( $this->phpPath() . 'php' . DS . 'blogspot.parser.php' );
		
		//ignore_user_abort(true);
		set_time_limit (0);
		
		ini_set('memory_limit', '512M');
			
		define('DB_COMMENTS', PATH_DATABASES . 'comments' . DS);
			
		$this->dbFields = array(
			'disqus_id'=>'',
			'xmlfile'=>'',
			'url'=>'',
			'coverimage'=>'disable',
			'comments'=>'disable'
		);
		
		$this->categories = array();
		$this->comments = array();
		$this->posts = array();
		$this->page_pos = 0;
		$this->com_id = 0;
		$this->doc = '';				
		$this->site_url = $this->site_url(); // Site URL with trailing slash
	}	

	public function form()
	{
		global $L;
		
		if ( !empty( $this->getValue( 'xmlfile' ) ) && file_exists(PATH_UPLOADS . $this->getValue('xmlfile')))
			$disabled = 'disabled';
		else
			$disabled = '';
				
		$html = '<div>';
		$html .= '<label>'.$L->get('url').'</label>';
		$html .= '<input name="url" id="jsurl" type="text" value="'.$this->getValue('url').'" ' . $disabled . '>';
		$html .= '<span class="tip"><small>'.$L->get('url-info').'</small></span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('xml-name').'</label>';
		$html .= '<input name="xmlfile" id="jsxmlfile" type="text" value="'.$this->getValue('xmlfile').'" ' . $disabled . '>';
		$html .= '<span class="tip"><small>'.$L->get('xml-file').'</small></span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('copy-comments').'</label>';
		$html .= '<select name="comments" ' . $disabled . '>';
		$html .= '<option value="disable" '.($this->getValue('comments') == 'disable'?'selected':'').'>'.$L->get('disable-comments').'</option>';
		$html .= '<option value="internal" '.($this->getValue('comments') == 'internal'?'selected':'').'>'.$L->get('internal-comments').'</option>';
		$html .= '<option value="disqus" '.($this->getValue('comments') == 'disqus'?'selected':'').'>'.$L->get('disqus-comments').'</option>';
		$html .= '</select>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('set-cover-image').'</label>';
		$html .= '<select id="jscoverimage" name="coverimage" ' . $disabled . '>';
		$html .= '<option value="disable" '.($this->getValue('coverimage') == 'disable'?'selected':'').'>'.$L->get('disable-cover-image').'</option>';
		$html .= '<option value="url" '.($this->getValue('coverimage') == 'url'?'selected':'').'>'.$L->get('enable-url-cover-image').'</option>';
		$html .= '<option value="local" '.($this->getValue('coverimage') == 'local'?'selected':'').'>'.$L->get('enable-local-cover-image').'</option>';
		$html .= '</select>';
		$html .= '</div>';
				
		$html .= '<div>';
		$html .= '<label>'.$L->get('disqus-id').'</label>';
		$html .= '<input name="disqus_id" id="jsdisqusID" type="text" value="'.$this->getValue('disqus_id').'" ' . $disabled . '>';
		$html .= '<span class="tip"><small>'.$L->get('disqus-empty').'</small></span>';
		$html .= '</div>';
		
		
		if ( !empty( $this->getValue( 'xmlfile' ) ) && file_exists(PATH_UPLOADS . $this->getValue('xmlfile'))) {
		
			$html .= '<div class="unit-100">';
			$html .= '<button class="uk-button uk-button-primary" value="true" type="submit" name="convert"><i class="uk-icon-life-ring"></i> ' .$L->get("convert-xml"). '</button>';
			$html .= '</div>';
			$html .= '<br /><small>'.$L->get('locked').'</small>';
			$html .= '<style type="text/css" scoped>.uk-form-row button, .uk-form-row a {display:none};</style>';
			
		}
		
		return $html;
		
	}

	private function convertXML() 
	{
		
		global $L;

		$items = $this->loadPosts( $this->getValue( 'xmlfile' ) );
	
		//Load the data in arrays
		$this->loadDB();
		
		$page_pos = 0;

		foreach ($items as $content)
		{
			
			$title = (string) $content['title'];
			
			$published = strtotime( (string) $content['published'] );
			
			$updated = (!empty($content['updated']) ? strtotime( (string) $content['updated']) : '');
			
			$post = (string) $content['content'];
			
			$post = html_entity_decode ( $post );
			
			$post = str_replace( "<a name='more'></a>", '<!-- pagebreak -->', $post );
			
			$sef = $this->sef( $content );
			
			$cat_name_seo = $this->categoryArray( $content['category'], $sef );
						
			$post_status = ( ( $this->isDraft( $content ) ) ? 'draft' : 'published' );
			
			$uuid = ( !empty($sef) ? md5($sef) : md5(uniqid()) );
			
			if ( !$this->isPost( $content ) )
			{
				$this->comments( $content, $uuid );
				
			}
			
			else
			{

				$p_dir = PATH_PAGES . $sef . DS;

				//We can't continue if the folder can't be created...
				self::makeDir( $p_dir );
				
				$f_name = $p_dir . 'index.txt';
				
				$post = str_replace(array('<br>', '<br/>', '<br />' ), "\n", $post );//$this->autop( $post );

				//Create (a new) file	
				file_put_contents($f_name, $post);
						
				$checksum = md5_file($f_name);
				
				$descr = $this->getDescr( $post );
							
				$page_pos++;
				
				$img_dir = PATH_UPLOADS_PAGES . $uuid . DS;
				
				self::makeDir( $img_dir );
				
				$thumb = $this->thumb( $post, $img_dir );
				
				//Database
				$this->posts[$sef] = array
				(
					'title' => mb_convert_encoding($title, "UTF-8"),
					'description' => mb_convert_encoding($descr, "UTF-8"),
					'username' => "admin",
					'tags' => array(),
					'type' => $post_status,
					'date' => date(DB_DATE_FORMAT, $published),
					'dateModified' => ( !empty($updated) ? date(DB_DATE_FORMAT, $updated) : '' ),
					'allowComments' => true,
					'position' => $page_pos,
					'coverImage' => $thumb,
					'md5file' => $checksum,
					'category' => ( !empty($cat_name_seo) && ($post_status == 'published') ? $cat_name_seo : "" ),
					'uuid' => $uuid,
					'parent' => "",
					'template' => "",
					'noindex' => false,
					'nofollow' => false,
					'noarchive' => false
				);
			}
		}
				
		//Let's backup the data...
		$this->saveDB();
		
		//We're done...
		Alert::set($L->get("success"));
		sleep ( 3 );
		Redirect::page('plugins');
	}
	
	public function loadDB()
	{
		
		if ( !$this->getValue('merge') )
			return;
		
		if ( file_exists( DB_CATEGORIES ) )
			$this->categories = $this->loadFile( DB_CATEGORIES );
			
		if ( file_exists( DB_PAGES ) ) 
			$this->posts = $this->loadFile( DB_PAGES );
	}
	
	public function saveDB()
	{
		//Posts and pages
		if ( count( $this->posts ) > 0)
		{
			uasort( $this->posts, function($a, $b) { return $a['date'] < $b['date']; } );
			$posts = json_encode($this->posts, JSON_PRETTY_PRINT);
			$posts_dt = "<?php defined('BLUDIT') or die('Bludit CMS.'); ?>" . PHP_EOL . $posts;
			file_put_contents(DB_PAGES, $posts_dt, LOCK_EX);	
		}
		
		//Categories
		if ( count( $this->categories ) > 0)
		{
			$categories = json_encode($this->categories, JSON_PRETTY_PRINT);
			$categories_dt = "<?php defined('BLUDIT') or die('Bludit CMS.'); ?>" . PHP_EOL . $categories;
			file_put_contents(DB_CATEGORIES, $categories_dt, LOCK_EX);	
				
		}

		//Create the disqus file if we want it...
		if ( !empty ( $this->getValue( 'disqus_id' ) ) )
			file_put_contents(UPLOADS_ROOT . '/comments.xml', $this->doc->saveXML(), LOCK_EX);
	}
	
	public function comments( $content, $uuid ) 
	{

		$comm = array();
		
		if ($this->getValue('comments') == 'internal' )
		{
			self::makeDir( DB_COMMENTS );
			
			$comm_folder = DB_COMMENTS . $uuid . DS;
		
			self::makeDir( $comm_folder );

			$comm_file = $comm_folder . 'index.php';
			
		}
		
		foreach($content['category'] as $keys => $tag)
		{
			$term = (string) $content['category'][$keys]['@attributes']['term'];
	
			if ( ( strpos( $term, 'comment' ) === false ) || ( $term != 'http://schemas.google.com/blogger/2008/kind#comment' ) )
				continue;
		
			$this->com_id++;
							
			$com_author = (string) $content['author']['name'];
						
			$com_email = (string) $content['author']['email'];
							
			$com_url = '';
							
			$com_IP = '';
							
			$com_date = (string) $content['published'];
			$com_date = strtotime ( $com_date );
							
			$com_content = (string) $content['content'];
							
			$com_approved = true;
							
			if ( $this->getValue('comments') == 'internal' )
			{
				//Commnets Database
				$comm[] = array
				(
					'comment_author' => htmlspecialchars( $com_author, ENT_QUOTES ),
					'comment_author_email' => $com_email,
					'comment_author_url' => $com_url,
					"comment_author_IP" => $com_IP,
					'comment_date' => strtotime( $com_date ),
					'comment_content' => htmlspecialchars( $com_content, ENT_QUOTES ),
					'comment_approved' => $com_approved
				);
								
			}
			elseif ( !empty($this->getValue( 'disqus_id' ) ) )
			{
				$doc = $this->doc;
				
				$doc = new DOMDocument('1.0', 'UTF-8');

				// Friendly XML code
				$doc->formatOutput = true;
				//create "RSS" element
				$rss = $doc->createElement("rss");
				$rss_node = $doc->appendChild($rss); //add RSS element to XML node
				$rss_node->setAttribute("version","2.0"); //set RSS version

				//set attributes
				$rss_node->setAttribute("xmlns:content","http://purl.org/rss/1.0/modules/content/");
				$rss_node->setAttribute("xmlns:dsq","http://www.disqus.com/");
				$rss_node->setAttribute("xmlns:dc","http://purl.org/dc/elements/1.1/");
				$rss_node->setAttribute("xmlns:wp","http://wordpress.org/export/1.0/");
									
				//create "channel" element under "RSS" element
				$channel = $doc->createElement("channel");  
				$channel_node = $rss_node->appendChild($channel);
				
				$item_node = $channel_node->appendChild($doc->createElement("item")); //create a new node called "item"
				$title_node = $item_node->appendChild($doc->createElement( "title", htmlspecialchars($title, ENT_QUOTES) )); //Add Title under "item"
								
				$seo_node = $item_node->appendChild($doc->createElement("link", $site_url . $seo)); //Add link under "item"
								
				//create "description" node under "item"
				$description_node = $item_node->appendChild($doc->createElement("content:encoded"));  
								 
				//fill description node with CDATA content
				$description_contents = $doc->createCDATASection(htmlspecialchars($descr , ENT_QUOTES));  
				$description_node->appendChild($description_contents);
								  
				$dsq_node = $item_node->appendChild($doc->createElement("dsq:thread_identifier", $uuid)); //add dsq node under "item"
				$date_node = $item_node->appendChild($doc->createElement("wp:post_date_gmt", $date)); //add date node under "item"
				$comment_node = $item_node->appendChild($doc->createElement("wp:comment_status", $comment_status)); //add comment node under "item"
								 
				 //Comment node
				$commend_node = $item_node->appendChild($doc->createElement("wp:comment")); //create a new node called "comment"
								
				//$dsq_remote = $commend_node->appendChild($doc->createElement("dsq:remote", '0')); //Add dsq:remote under "comment"
								
				//$dsq_id = $dsq_remote->appendChild($doc->createElement("dsq:id", '0')); //Add dsq:id under "dsq:remote"
								
				//$dsq_avatar = $dsq_remote->appendChild($doc->createElement("dsq:avatar", '0')); //Add dsq:avatar under "dsq:remote"
								
				$comment_id = $commend_node->appendChild($doc->createElement("wp:comment_id", $com_id)); //Add comment_id under "comment"
								
				$comment_author = $commend_node->appendChild($doc->createElement("wp:comment_author", $com_author)); //Add Title under "item"
								
				$comment_author_email = $commend_node->appendChild($doc->createElement("wp:comment_author_email", $com_email)); //Add Title under "item"
								
				$comment_author_url = $commend_node->appendChild($doc->createElement("wp:comment_author_url", $com_url)); //Add Title under "item"
								
				$comment_author_IP = $commend_node->appendChild($doc->createElement("wp:comment_author_IP", $com_IP)); //Add Title under "item"
								
				$comment_date_gmt = $commend_node->appendChild($doc->createElement("wp:comment_date_gmt", $com_date)); //Add Title under "item"
								
				$comment_content = $commend_node->appendChild($doc->createElement("wp:comment_content", htmlspecialchars($com_content , ENT_QUOTES)) ); 
								
				$comment_approved = $commend_node->appendChild($doc->createElement("wp:comment_approved", $com_approved)); //Add Title under "item"
								
				$comment_parent = $commend_node->appendChild($doc->createElement("wp:comment_parent", '0')); //Add Title under "item"
			}
		}
		
		if ( $this->getValue('comments') == 'internal' )
		{
			uasort( $comm, function($a, $b) { return $a['comment_date'] < $b['comment_date']; } );
			$comm = json_encode($comm, JSON_PRETTY_PRINT);
			$comm_dt = "<?php defined('BLUDIT') or die('Bludit CMS.'); ?>" . PHP_EOL . $comm;
			file_put_contents($comm_file, $comm_dt, LOCK_EX);	
		}
	}
	
	private function getDescr($content)
	{
		
		if ( strpos( $content, '<!-- pagebreak -->' ) !== false )
		{
			$descr = explode ('<!-- pagebreak -->', $content);
			$descr = strip_tags($descr ['0']);
		}
		
		else
			$descr = $this->shorten( $content, 160 ) ;
		
		return $descr;
	}
	
	private function replaceURL($content)
	{
		
		//Replace the inpost URLs if any...
		if ( !empty( $this->getValue( 'url' ) ) )
		{
			$exURL = $this->getValue( 'url' );
						
			$last = $exURL[strlen($exURL)-1];
						
			if ($last != '/')
				$exURL = $exURL . '/';

			$exURL = str_replace (array("/", "."), array ("\/", "\."), $exURL ); //preg_quote doesn't work well here...
						
			$content = preg_replace('/' . $exURL . '([0-9]{4}\/)?([0-9]{2}\/)?([0-9]{2}\/)?(([^_]+)\/)?([^_]+)\//', $this->site_url . str_replace('.html', '', "$6"),  $content);

		}
		
		return $content;
	}
	
	public function post()
	{
		if ( isset( $_POST['convert'] ) ) {
			
			self::convertXML();
			
		} else {
	
			// Build the database
			$this->db['xmlfile'] = Sanitize::html($_POST['xmlfile']);
			$this->db['comments'] = Sanitize::html($_POST['comments']);
			$this->db['url'] = Sanitize::html($_POST['url']);
			$this->db['disqus_id'] = (!empty($_POST['disqus_id'])) ? Sanitize::html($_POST['disqus_id']) : '';

			// Save the database
			return $this->save();
		}
		return false;
	}
	
	public function categoryArray( $content, $sef )
	{
		
		$cat_name_sef = '';
		
		if ( !empty( $content ) )
		{
			$cat_pos = 0;
			
			foreach($content as $keys => $tag)
			{
				if (!isset($content[$keys]['@attributes']['term']))
					continue;
				
				$term = (string) $content[$keys]['@attributes']['term'];
							
				if ( strpos( $term, 'http' ) !== false )
					continue;
					
				if ($cat_pos == 1)
					break;
					
				$cat_name_sef = urldecode ( $term );
				$cat_name_sef = URLify::filter ( $cat_name_sef );
												
				if( !isset($this->categories[$cat_name_sef]) )
					$this->categories[$cat_name_sef] = array('name' => $term, 'list' => array( $sef ) );
				else
					array_push($this->categories[$cat_name_sef]['list'], $sef);
				
				$cat_pos++;
			}
		
		}
		
		return $cat_name_sef;
	}
	
	public function sef( $content )
	{
		$sef = URLify::filter( $content['title'] );
		
		if ( isset( $content['link']['4'] ) )
		{
			$sef = (string) $content['link']['4']['@attributes']['href'];
			$sef = explode('/', $sef);
			$sef = end($sef);
			$sef = str_replace('.html', '', $sef);
		}
		
		if ( empty( $sef ) || ( strpos( $sef, '-' ) === false ) || strlen($sef < 2 ) )
			$sef = URLify::filter( (string) $content['title'] );
		
		return $sef;
	}
	
	public function isDraft( $content )
	{
		$draft = true;
		
		if ( isset( $content['link']['4']['@attributes']['href'] ) )
			$draft = false;
		
		return $draft;
	}
	
	public function isPost( $content )
	{
		$post = true;
		
		foreach($content['category'] as $keys => $tag)
		{
			
			if (!isset($content[$keys]['@attributes']['term']))
				continue;

			$term = (string) $content['category'][$keys]['@attributes']['term'];
	
			if ( ( strpos( $term, 'comment' ) !== false ) || ( preg_match("/comment/", $term) ) )
				$post = false;
					
			if ( ( strpos( $term, 'http' ) !== false ) || ( preg_match("/http/", $term) ) )
				$post = false;

		}
		
		return $post;
		
	}
	
	public function loadFile( $file )
	{
		return json_decode(file_get_contents($file, NULL, NULL, 50), TRUE);
	}
	
	public function makeDir( $dir )
	{
		if (!is_dir($dir))
			mkdir( $dir, 0755, true ) or die ( 'Could not create folder ' . $dir );
	}
		
	public function loadPosts( $file = '' )
	{
		if ( empty( $file ) )
			return false;
		
		//If the convert button is pressed but the file is not there, don't continue...		
		if ( !empty( $file ) && !file_exists( PATH_UPLOADS . $file ) ) {
			Alert::set($L->get("no-file-found"));
			Redirect::page('configure-plugin/pluginBloggerToBludit');
		}
	
		//Load the XML data, before we delete everything
		$xml = $this->stripInvalidXml( file_get_contents(PATH_UPLOADS . $file ) );
		
		$parser = new Blogspotparser( $xml );
		
		if ( empty( $parser ) ) {
			Alert::set($L->get("no-posts-found"));
			Redirect::page('configure-plugin/pluginBloggerToBludit');
		}
		
		return $parser->fetch_entries();
	}
	
	public function site_url() 
	{
		global $site;
		
		$site_url = $site->url();
						
		$last = $site_url[strlen($site_url)-1];
						
		if ($last != '/')
			$site_url = $site_url . '/';
		
		return $site_url;
		
	}
	
	public function shorten($string, $length) {
		// By default, an ellipsis will be appended to the end of the text.
		$suffix = '...';

		// Strip the HTML tags, and convert all tabs and line-break characters to single spaces.
		$short_desc = trim(str_replace(array("\r", "\n", '"', "\t"), ' ', strip_tags($string)));

		// Cut the string to the requested length, and strip any extraneous spaces
		// from the beginning and end.
		$desc = trim(substr($short_desc, 0, $length));

		// Find out what the last displayed character is in the shortened string
		$lastchar = substr($desc, -1, 1);

		// If the last character is a period, an exclamation point, or a question
		// mark, clear out the appended text.
		if ($lastchar == '.' || $lastchar == '!' || $lastchar == '?')
			$suffix = '';

		// Append the text.
		$desc .= $suffix;
		//$desc .= '... // '.$item->get_date('j M Y | g:i a T');

		// Send the new description back to the page.
		return $desc;
	}
	
	// Returns the images from the content
	private function getImages($content)
	{
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/ii', $content, $matches);
			
		if (!empty($matches[1]))
			return $matches[1];

		return false;
	}
	
	private function thumb($content, $upload_path)
	{
		
		$img = $this->getImage($content);
		
		$name = '';
		
		if ( !empty( $img ) )
		{
			
			$info = pathinfo($img);

			$img_temp = URLify::filter( $info['filename'] ) . '.' . ( isset($info['extension']) ? $info['extension'] : '.jpg' );
			
			$name = $this->create_image($img, $upload_path, $img_temp);

		}
		
		return $name;
	}
	
	// Returns the first image from the page content
	private function getImage($content)
	{
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/ii', $content, $matches);
		
		if (!empty($matches[1][0]))
			return $matches[1][0];

		return false;
	}
			
	//This function copies the image
	public function create_image( $img_link, $upload_path, $name )
	{
		if ( is_file( $upload_path . $name ) ) 
		{
			echo '<strong>File</strong> "' . $name . '" already exists...<br />';
		}
		elseif ( @copy($img_link, $upload_path . $name) ) 
		{

			$name = $name;

		}
		else
			$name = '';
		
		return $name;
	}
	
	/**
	 * Removes invalid XML
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	public function stripInvalidXml($value)
	{
		$ret = "";
		$current;
		if (empty($value)) 
		{
			return $ret;
		}

		$length = strlen($value);
		for ($i=0; $i < $length; $i++)
		{
			$current = ord($value{$i});
			if (($current == 0x9) ||
				($current == 0xA) ||
				($current == 0xD) ||
				(($current >= 0x20) && ($current <= 0xD7FF)) ||
				(($current >= 0xE000) && ($current <= 0xFFFD)) ||
				(($current >= 0x10000) && ($current <= 0x10FFFF)))
			{
				$ret .= chr($current);
			}
			else
			{
				$ret .= " ";
			}
		}
		return $ret;
	}
	
	public function autop( $pee, $br = true ) {
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
		$pee = $this->wp_replace_in_html_tags( $pee, array( "\n" => " <!-- wpnl --> " ) );

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
	
	public function _autop_newline_preservation_helper( $matches ) {
		return str_replace( "\n", "<WPPreserveNewline />", $matches[0] );
	}

	public function get_html_split_regex() {
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

	public function wp_html_split( $input ) {
		return preg_split( $this->get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
	}

	public function wp_replace_in_html_tags( $haystack, $replace_pairs ) {
		// Find all elements.
		$textarr = $this->wp_html_split( $haystack );
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
