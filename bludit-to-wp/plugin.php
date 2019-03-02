<?php
class pluginBludit2WP extends Plugin {
	
	public function init()
	{
		//ignore_user_abort(true);
		set_time_limit (0);
		
		ini_set('memory_limit', '512M');
				
		// Fields and default values for the database of this plugin
		$this->dbFields = array(
			'hash'=>''
		);
		
		// Disable default form buttons
		$this->formButtons = false;

	}
	
	public function post()
	{
		if (isset($_POST['generateXML'])) {
			return $this->generate();
		}

		return false;
	}

	public function form()
	{
		global $L;

		$html  = '<div>';
		$html .= '<button name="generateXML" value="true" class="left small blue" type="submit"><i class="uk-icon-eraser"></i> '.$L->get('export-site').'</button>';
		$html .= '</div>';
		$html .= '<hr>';

		return $html;
	}
	
	public function generate()
	{
		global $site, $pages, $users, $tags, $categories;
		
		$admin = $users->getDB('admin');
		
		$cats = 0;
		$tagnum = 0;
		$postnum = 0;
		$attachments = 0;
		
		$p_array = array();
		
		$site_url = $site->url();
							
		$last = $site_url[strlen($site_url)-1];
							
		if ($last != '/')
			$site_url = $site_url . '/';
		
		//New XML File
		$doc = new DOMDocument('1.0', 'UTF-8');
							
		// Friendly XML code
		$doc->formatOutput = true;
							
		//create "RSS" element
		$rss = $doc->createElement("rss");
		$rss_node = $doc->appendChild($rss); //add RSS element to XML node
		$rss_node->setAttribute("version","2.0"); //set RSS version

		//set attributes
		$rss_node->setAttribute("xmlns:excerpt","http://wordpress.org/export/1.2/excerpt/");
		$rss_node->setAttribute("xmlns:content","http://purl.org/rss/1.0/modules/content/");
		$rss_node->setAttribute("xmlns:wfw","http://wellformedweb.org/CommentAPI/");
		$rss_node->setAttribute("xmlns:dc","http://purl.org/dc/elements/1.1/");
		$rss_node->setAttribute("xmlns:wp","http://wordpress.org/export/1.2/");
							
		//create "channel" element under "RSS" element
		$channel = $doc->createElement("channel");  
		$channel_node = $rss_node->appendChild($channel);
		
		$title_node = $channel_node->appendChild($doc->createElement("title", htmlspecialchars( $site->title(), ENT_QUOTES ) ));
		
		$link_node = $channel_node->appendChild($doc->createElement("link", $site_url ));
		
		$desc_node = $channel_node->appendChild($doc->createElement("description", htmlspecialchars($site->slogan(), ENT_QUOTES) ));
		
		$pub_node = $channel_node->appendChild($doc->createElement("pubDate", date ('r', time())));
		
		$lang_node = $channel_node->appendChild($doc->createElement("language", $site->language() ));
		
		$ver_node = $channel_node->appendChild($doc->createElement("wp:wxr_version", "1.2" ));
		
		$base_site_node = $channel_node->appendChild($doc->createElement("wp:base_site_url", $site_url ));
		
		$base_node = $channel_node->appendChild($doc->createElement("wp:base_blog_url", $site_url ));
		
		//Author
		$author_node = $channel_node->appendChild($doc->createElement("wp:author"));
		$author_id = $author_node->appendChild($doc->createElement( "wp:author_id", "1" ));
		$author_login = $author_node->appendChild($doc->createElement( "wp:author_login", "admin" ));
		$author_email = $author_node->appendChild($doc->createElement( "wp:author_email", ( !empty($admin['email']) ? $admin['email'] : '' ) ));
		
		$author_name = $author_node->appendChild($doc->createElement("wp:author_display_name"));  
		$author_name_dt = $doc->createCDATASection( ( !empty($admin['firstName']) ? htmlspecialchars($admin['firstName'] , ENT_QUOTES) : '' ) );
		$author_name->appendChild($author_name_dt);
		
		foreach ($categories->db as $key=>$fields) 
		{
			$count = count($fields['list']);
			
			if ($count>0) {
				$cats++;
				
				$cat_node = $channel_node->appendChild($doc->createElement("wp:category"));
				$cat_id = $cat_node->appendChild($doc->createElement( "wp:term_id", $cats ));
				$cat_nicename = $cat_node->appendChild($doc->createElement( "wp:category_nicename", $key ));
				$cat_parent = $cat_node->appendChild($doc->createElement( "wp:category_parent"));
				
				$cat_name = $cat_node->appendChild($doc->createElement("wp:cat_name"));
				$cat_name_dt = $doc->createCDATASection(htmlspecialchars($fields['name'] , ENT_QUOTES));
				$cat_name->appendChild($cat_name_dt);

			}
			
		}
		
		foreach ($tags->db as $key=>$fields) 
		{
			
			$count = count($fields['list']);
			
			if ($count>0) 
			{
				$tagnum++;
				
				$tag_node = $channel_node->appendChild($doc->createElement("wp:tag"));
				$tag_id = $tag_node->appendChild($doc->createElement( "wp:term_id", $tagnum ));
				$tag_nicename = $tag_node->appendChild($doc->createElement( "wp:tag_slug", $key ));
				
				$tag_name = $tag_node->appendChild($doc->createElement("wp:tag_name"));
				$tag_name_dt = $doc->createCDATASection(htmlspecialchars($fields['name'] , ENT_QUOTES));
				$tag_name->appendChild($tag_name_dt);
												
			}
			
		}
		
		$generator_node = $channel_node->appendChild($doc->createElement("generator", "https://en.homebrewgr.info/bludit-to-wordpress-converter" ));
		
		// Get Posts DB
		$list = $pages->getList(1, -1, true);
				
		foreach($list as $pageKey) 
		{
			try {
				$page = new Page($pageKey);
			} catch (Exception $e) {
				continue;
			}

			$postnum++;
			
			$postID = $postnum;
			
			$item_node = $channel_node->appendChild($doc->createElement("item"));
			$title_node = $item_node->appendChild($doc->createElement( "title", $page->title()));
							
			$seo_node = $item_node->appendChild($doc->createElement("link", $page->permalink() . '/'));
							
			$pubDate_node = $item_node->appendChild($doc->createElement("pubDate", date ('r', strtotime($page->dateRaw())) ));
			
			$creator_node = $item_node->appendChild($doc->createElement("dc:creator", $page->username()));

			$guid = $item_node->appendChild($doc->createElement("guid", $site_url . 'p?=' . $postID));
			$guid->setAttribute("isPermaLink","false");
				
			$description_node = $item_node->appendChild($doc->createElement("description"));
			
			$post_content = $page->content();
			
			$post_content = str_replace( array('<!-- pagebreak -->', '&quot;'), array('<!--more-->', '"'), $post_content);
			
			$post_content = str_replace( array('<p>', '</p>'), array('', ''), $post_content);
			
			//Find All the images in the post
			
			$images_in_post = $this->getImages( $post_content );
			
			if (!empty($images_in_post)) 
			{
				
				foreach ($images_in_post as $image) {
					$attachments++;
					
					$img_alt = '';
					
					preg_match('~<img.*?alt=["\']+(.*?)["\']+~', $image, $alt);
					
					preg_match('~<img.*?src=["\']+(.*?)["\']+~', $image, $src);
					
					if (!empty($alt['1']))
						$img_alt = trim( $alt['1'] );
					
					$img_src = $src['1'];
					
					list($img_width, $img_height) = @getimagesize( $img_src );
					
					if (!empty($alt['1']))
						$WPImg = '[caption id="attachment_' . $attachments . '" align="aligncenter" width="' . $img_width . '"]<img src="' . $img_src . '" alt="" width="' . $img_width . '" height="' . $img_height . '" class="size-full wp-image-' . $attachments . '" /> ' . $img_alt . '[/caption]';
					else
						$WPImg = '<img src="' . $img_src . '" alt="" width="' . $img_width . '" height="' . $img_height . '" class="size-full wp-image-' . $attachments . '" />';
				
					$post_content = str_replace($image, $WPImg, $post_content);
					
					$postnum++;
					
					$info = pathinfo($img_src);
				
					$attach_url = $img_src;
								
					$parent = $postID;
					
					if (empty( $info ))
						continue;
					
					$seo_file = str_replace('.', '-', strtolower($info['filename']));
					
					$item_node_3 = $channel_node->appendChild($doc->createElement("item"));
					$title_node_3 = $item_node_3->appendChild($doc->createElement( "title", ucfirst($info['filename'])));
						
					$seo_node_3 = $item_node_3->appendChild($doc->createElement("link", $page->permalink() . '/' . $seo_file . '/'));
																	
					$pubDate_node_3 = $item_node_3->appendChild($doc->createElement("pubDate", date ('r', strtotime($page->dateRaw())) ));
						
					$creator_node_3 = $item_node_3->appendChild($doc->createElement("dc:creator", $page->username()));

					$guid_3 = $item_node_3->appendChild($doc->createElement("guid", $attach_url));
					$guid_3->setAttribute("isPermaLink","false");
							
					$description_node_3 = $item_node_3->appendChild($doc->createElement("description"));
						
					$content_3 = $item_node_3->appendChild($doc->createElement("content:encoded"));
					$content_dt_3 = $doc->createCDATASection("");
					$content_3->appendChild($content_dt_3);
						
					$excerpt_3 = $item_node_3->appendChild($doc->createElement("excerpt:encoded"));
					$excerpt_dt_3 = $doc->createCDATASection("");
					$excerpt_3->appendChild($excerpt_dt_3);
						
					$post_id_3 = $item_node_3->appendChild($doc->createElement("wp:post_id", $postnum));
					$post_date_3 = $item_node_3->appendChild($doc->createElement("wp:post_date", date ('Y-m-d H:i:s', strtotime($page->dateRaw()))));
					$post_date_gmt_3 = $item_node_3->appendChild($doc->createElement("wp:post_date_gmt", date ('Y-m-d H:i:s', strtotime($page->dateRaw()))));
								
					$comments_3 = $item_node_3->appendChild($doc->createElement("wp:comment_status", 'open'));
					$ping_status_3 = $item_node_3->appendChild($doc->createElement("wp:ping_status", 'closed'));
						
					$post_name_3 = $item_node_3->appendChild($doc->createElement("wp:post_name", $seo_file));
					$post_status_3 = $item_node_3->appendChild($doc->createElement("wp:status", 'inherit'));
						
					$post_parent_3 = $item_node_3->appendChild($doc->createElement("wp:post_parent", $parent));
						
					$menu_order_3 = $item_node_3->appendChild($doc->createElement("wp:menu_order", '0'));
						
					$post_type_3 = $item_node_3->appendChild($doc->createElement("wp:post_type", 'attachment' ));
					$post_password_3 = $item_node_3->appendChild($doc->createElement("wp:post_password"));
					$attachm_url_3 = $item_node_3->appendChild($doc->createElement("wp:attachment_url", $attach_url ));
					
					$attach_3 = $item_node_3->appendChild($doc->createElement("wp:postmeta"));
					$attach_key = $attach_3->appendChild($doc->createElement("wp:meta_key", "_wp_attached_file"));
					$attach_value_3 = $attach_3->appendChild($doc->createElement("wp:meta_value"));
					$attached_file_3 = $doc->createCDATASection( date ('Y/m/', strtotime($page->dateRaw())) . $info['basename']);
					$attach_value_3->appendChild($attached_file_3);
					
					unset($item_node_3, $image);
				}
			}
			
			//Content
			$content = $item_node->appendChild($doc->createElement("content:encoded"));
			$content_dt = $doc->createCDATASection( $this->urlembed( $post_content ) );
			$content->appendChild($content_dt);
			
			//Excerpt
			$excerpt = $item_node->appendChild($doc->createElement("excerpt:encoded"));
			$excerpt_dt = $doc->createCDATASection("");
			$excerpt->appendChild($excerpt_dt);
			
			$post_id = $item_node->appendChild($doc->createElement("wp:post_id", $postID));
			$post_date = $item_node->appendChild($doc->createElement("wp:post_date", date ('Y-m-d H:i:s', strtotime($page->dateRaw()))));
			$post_date_gmt = $item_node->appendChild($doc->createElement("wp:post_date_gmt", date ('Y-m-d H:i:s', strtotime($page->dateRaw()))));
			
			$comments = $item_node->appendChild($doc->createElement("wp:comment_status", ($page->allowComments() == 1) ? 'open' : 'closed'));
			$ping_status = $item_node->appendChild($doc->createElement("wp:ping_status", 'closed'));
			
			$post_name = $item_node->appendChild($doc->createElement("wp:post_name", $page->key()));
			$post_status = $item_node->appendChild($doc->createElement("wp:status", ($page->type() == 'published') ? 'publish' : 'draft'));
			
			$post_parent = $item_node->appendChild($doc->createElement("wp:post_parent", '0'));
			
			$menu_order = $item_node->appendChild($doc->createElement("wp:menu_order", '0'));
			
			$post_type = $item_node->appendChild($doc->createElement("wp:post_type", ( !$page->isStatic() ? 'post' : 'page' ) ));
			
			$post_password = $item_node->appendChild($doc->createElement("wp:post_password"));
			
			$is_sticky = $item_node->appendChild($doc->createElement("wp:is_sticky", '0'));
			
			$category = $item_node->appendChild($doc->createElement("category"));
			$category_dt = $doc->createCDATASection($page->category());
			$category->appendChild($category_dt);

			$category->setAttribute("domain","category");
			$category->setAttribute("nicename",$page->categoryMap(true));
			
			$pagetags = $page->tags(true);
			
			if (!empty($pagetags)) 
			{
				
				foreach($pagetags as $tagKey=>$tagName) {
					
					$tag = $item_node->appendChild($doc->createElement("category"));
					$tag_dt = $doc->createCDATASection($tagName);
					$tag->appendChild($tag_dt);

					$tag->setAttribute("domain","post_tag");
					$tag->setAttribute("nicename",$tagKey);
				}
				
			}
			
			if ( Text::isNotEmpty($page->dateModified()) ) 
			{
				$edit_last = $item_node->appendChild($doc->createElement("wp:postmeta"));
				$edit_last_key = $edit_last->appendChild($doc->createElement("wp:meta_key", "_edit_last"));
				$edit_last_value = $edit_last->appendChild($doc->createElement("wp:meta_value"));
				$edited = $doc->createCDATASection(strtotime($page->dateModified()));
				$edit_last_value->appendChild($edited);
			}
			
			if( $page->coverImage() ) 
			{
				$attachments++;
				$info = pathinfo($page->coverImage());
				
				$attach_url = $page->coverImage();
								
				$parent = $postID;
				
				$seo_file = str_replace('.', '-', strtolower($info['filename']));
				
				$postnum++;
				
				$attach = $item_node->appendChild($doc->createElement("wp:postmeta"));
				$attach_key = $attach->appendChild($doc->createElement("wp:meta_key", "_thumbnail_id"));
				$attach_value = $attach->appendChild($doc->createElement("wp:meta_value"));
				$attach_id = $doc->createCDATASection($postnum);
				$attach_value->appendChild($attach_id);
				
				$item_node_2 = $channel_node->appendChild($doc->createElement("item"));
				$title_node2 = $item_node_2->appendChild($doc->createElement( "title", ucfirst($info['filename'])));
				
				$seo_node2 = $item_node_2->appendChild($doc->createElement("link", $page->permalink() . '/' . $seo_file . '/'));
															
				$pubDate_node2 = $item_node_2->appendChild($doc->createElement("pubDate", date ('r', strtotime($page->dateRaw())) ));
				
				$creator_node2 = $item_node_2->appendChild($doc->createElement("dc:creator", $page->username()));

				$guid2 = $item_node_2->appendChild($doc->createElement("guid", $attach_url));
				$guid2->setAttribute("isPermaLink","false");
					
				$description_node2 = $item_node_2->appendChild($doc->createElement("description"));
				
				$content2 = $item_node_2->appendChild($doc->createElement("content:encoded"));
				$content_dt2 = $doc->createCDATASection("");
				$content2->appendChild($content_dt2);
				
				$excerpt2 = $item_node_2->appendChild($doc->createElement("excerpt:encoded"));
				$excerpt_dt2 = $doc->createCDATASection("");
				$excerpt2->appendChild($excerpt_dt2);
				
				$post_id2 = $item_node_2->appendChild($doc->createElement("wp:post_id", $postnum));
				$post_date2 = $item_node_2->appendChild($doc->createElement("wp:post_date", date ('Y-m-d H:i:s', strtotime($page->dateRaw()))));
				$post_date_gmt2 = $item_node_2->appendChild($doc->createElement("wp:post_date_gmt", date ('Y-m-d H:i:s', strtotime($page->dateRaw()))));
						
				$comments2 = $item_node_2->appendChild($doc->createElement("wp:comment_status", 'open'));
				$ping_status2 = $item_node_2->appendChild($doc->createElement("wp:ping_status", 'closed'));
				
				$post_name2 = $item_node_2->appendChild($doc->createElement("wp:post_name", $seo_file));
				$post_status2 = $item_node_2->appendChild($doc->createElement("wp:status", 'inherit'));
				
				$post_parent2 = $item_node_2->appendChild($doc->createElement("wp:post_parent", $parent));
				
				$menu_order2 = $item_node_2->appendChild($doc->createElement("wp:menu_order", '0'));
				
				$post_type2 = $item_node_2->appendChild($doc->createElement("wp:post_type", 'attachment' ));
				$post_password2 = $item_node_2->appendChild($doc->createElement("wp:post_password"));
				$attachm_url2 = $item_node_2->appendChild($doc->createElement("wp:attachment_url", $attach_url ));
				
			}
									
		}
		

		$xml_file = str_replace(array('https://', 'http://', '/'), '', $site_url). '.' . date('Y-m-d', time()) . '.xml';
		$doc->save($this->workspace() . $xml_file);
		
		//exit;
	}
			
	
	public function workspace()
	{
		return PATH_CONTENT.'uploads'.DS;
	}
	
	public function urlembed( $post ) {
		
		//global $embed_js;
				
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
						
				$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $link, $post);
			}
			
			if (preg_match("/dailymotion/", $link)) {
				
				preg_match("/\/video\/(.+)/",$link, $matches);
				preg_match("/video\/([^_]+)/", $link, $matche);
				
				$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $link, $post);
			}
			
			if ( preg_match('#https?://(player\.)?vimeo\.com(/video)?/(\d+)#i', $link, $matches) ) {
							
				$yt_id = trim($matches[3]);
											
				$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $link, $post);
			}
			
			if (preg_match("/facebook.com\/(.*?)/", $link) || (preg_match('/facebook\.com/i', $link))) {
				
				$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $link, $post);
			}
			
			if ( preg_match('#^https?://twitter\.com/(?:\#!/)?(\w+)/status(es)?/(\d+)$#i', $link, $matches ) ) {
								
				$status_id = $matches[3];
								
				$post = str_replace('<a href="' . $link . '">' . $link . '</a>', $link, $post);
				
			}
			
		}
		
		$post = str_replace('<!-- pagebreak -->', '<!--more-->', $post);
				
		return $post;
	}
	
	
	// Returns the images from the page content
	private function getImages($content)
	{
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/ii', $content, $matches);
		
		if ( !empty( $matches[0] ) )
			return $matches[0];

		return false;
	}
	
}
?>