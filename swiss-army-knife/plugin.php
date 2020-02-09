<?php

class pluginSwissArmyKnife extends Plugin {

	public function init()
	{
		define('DB_IMG', $this->workspace() . 'db.php' );

		if ( !file_exists( DB_IMG ) )
		{
			$dt = "<?php defined('BLUDIT') or die('Bludit CMS.'); ?>" . PHP_EOL;
			
			@file_put_contents( DB_IMG, $dt, LOCK_EX);
		}
		
		$this->dbFields = array
		(
			'copy_external'=>false,
			'compress_img'=>false,
			'https_redir'=>false,
			'beauty_post'=>false,
			'header_code'=>'',
			'categories'=>array(),
			'sidebar_code'=>'',
			'footer_code'=>''
		);
	}

	public function form()
	{
		global $L, $categories;

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('copy-external').'</label>';
		$html .= '<input type="checkbox" id="jscopy_external" name="copy_external" ' . ( $this->getValue('copy_external') == 1 ? 'checked="checked"' :'' ).' value="1" />';
		$html .= '<span class="tip">'.$L->get('copy-info').'</small></span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('compress').'</label>';
		$html .= '<input type="checkbox" id="jscompress_img" name="compress_img" ' . ($this->getValue('compress_img') == 1 ? 'checked="checked"' : '' ) . ' value="1" />';
		$html .= '<span class="tip">'.$L->get('compress-info').'</small></span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('https-redir').'</label>';
		$html .= '<input type="checkbox" id="jshttps_redir" name="https_redir" ' . ( $this->getValue('https_redir') == 1 ? 'checked="checked"' : '' ) . ' value="1" />';
		$html .= '<span class="tip">'.$L->get('https-info').'</small></span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('beauty-post').'</label>';
		$html .= '<input type="checkbox" id="jsbeauty_post" name="beauty_post" ' . ( $this->getValue('beauty_post') == 1 ? 'checked="checked"' : '' ) . ' value="1" />';
		$html .= '<span class="tip">'.$L->get('beauty-info').'</small></span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('header-code').'</label>';
		$html .= '<textarea name="header_code" id="jsheader_code">'.$this->getValue('header_code').'</textarea>';
		$html .= '<span class="tip">'.$L->get('header-info').'</span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('footer-code').'</label>';
		$html .= '<textarea name="footer_code" id="jsfooter_code">'.$this->getValue('footer_code').'</textarea>';
		$html .= '<span class="tip">'.$L->get('footer-info').'</span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('sidebar-code').'</label>';
		$html .= '<textarea name="sidebar_code" id="jssidebar_code">' . $this->getValue('sidebar_code') . '</textarea>';
		$html .= '<span class="tip">'.$L->get('sidebar-info').'</span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('exclude-category').'</label>';
		$html .= '<select multiple = "multiple" data-placeholder = "Select" id="jscategories" class = "multiselect form-control categories" name = "categories[]">';
		
		foreach ( $categories->db as $key => $fields ) 
		{
			if ( count( $fields['list'] ) > 0 ) 
			{
				$html .= '<option value="' . $key . '" ' . ( ( !empty( $this->getValue('categories') ) && in_array( $key, $this->getValue('categories') ) ) ? 'selected="selected"' : '') . '>' . $fields['name'] . '</option>';
			}

		}
		
		$html .= '</select>';
		$html .= '<span class="tip">'.$L->get('exclude-info').'</span>';
		$html .= '</div>';
				
		return $html;
	}

	public function post()
	{
		// Build the database
		$this->db['copy_external'] = ( !empty( $_POST['copy_external'] ) ? 1 : 0 );
		$this->db['compress_img'] = ( !empty( $_POST['compress_img'] ) ? 1 : 0 );
		$this->db['beauty_post'] = ( !empty( $_POST['beauty_post'] ) ? 1 : 0 );
		$this->db['https_redir'] = ( !empty( $_POST['https_redir'] ) ? 1 : 0 );
		$this->db['header_code'] = Sanitize::html( $_POST['header_code'] );
		$this->db['footer_code'] = Sanitize::html( $_POST['footer_code'] );

		$categories = array();

		if ( isset($_POST['categories']) && !empty( $_POST['categories'] ) )
		{
			
			foreach ( $_POST['categories'] as $category ) 
			{
				$categories[] = $category;
			}
		}

		$this->db['categories'] = $categories;

		// Save the database
		return $this->save();
	}

	public function buildHomepage()
	{
		global $pages, $url, $site, $content;

		if ( empty( $this->getValue( 'categories' ) ) )
			return false;
		
		$itemsPerPage = $site->itemsPerPage();
		
		$num = 0;
		
		$content = array();
		
		$pagenum = ( ( !empty( $url->pageNumber() ) && is_numeric( $url->pageNumber() ) ) ? $url->pageNumber() : 1 );
					
		//We need them all
		$list = $pages->getList( $pagenum, -1, true, false, true, false, false );

		foreach ( $list as $pageKey )
		{
			if ( $num == $itemsPerPage)
				break;
				
			$page = buildPage( $pageKey );
				
			if ( $page  && !in_array( $page->categoryKey(), $this->getValue( 'categories' ) ) )
			{
				array_push( $content, $page );
			}
			else
				continue; // Don't count it as item

			$num++;
		}
		
		return $content;
	}

	public function beforeSiteLoad() 
	{
		
		global $page, $url;

		if ( $this->getValue('beauty_post') && !$url->notFound() && ( $url->whereAmI() == 'page' ) )
		{
			require ( $this->phpPath() . 'php' . DS . 'functions.php' );

			$content = wpautop( $page->content() );

			$page->setField('content', $content);

			//return;
		}

		if ( !empty( $this->getValue( 'categories' ) ) && ( $url->whereAmI() == 'home' ) )
		{
			global $content;

			$content = $this->buildHomepage();
		}
		

		return false;
	}

	public function siteSidebar()
	{

		$html = '';

		if ( Text::isNotEmpty( $this->getValue( 'sidebar_code' ) ) )
		{
			$html .= html_entity_decode( $this->getValue('sidebar_code') ) . PHP_EOL;
		}

		return $html;

	}

	public function afterPageModify()
	{
		global $site;

		require ( $this->phpPath() . 'php' . DS . 'image.class.php' );

		$uuid = $_POST['uuid'];

		if ( $this->getValue('copy_external') )
		{
			$content = $_POST['content'];

			$images = $this->getImages($content);

			if ( !empty( $images ) )
			{

				foreach ( $images as $s )
				{
					$imgURL = $this->returnImgUrl( $s );

					$imgHost = $this->get_HostName ( $imgURL );

					$siteHost = $this->get_HostName ( $site->url() );

					if ( $imgHost != $siteHost )
					{
						$info = pathinfo( $imgURL );

						$img_name = $info['filename'] . '.' . $info['extension'];

						$file = PATH_UPLOADS_PAGES . $uuid . DS . $img_name;

						$thumb = PATH_UPLOADS_PAGES . $uuid . DS . 'thumbnails' . DS . $img_name;

						$html_file = $this->site_url() . HTML_PATH_UPLOADS_PAGES . $uuid . '/' . $img_name;

						if ( @copy( $imgURL, $file ) ) 
						{

							$img = new SimpleImage;
		
							$img->load( $file );
							
							//$img->scale( 70 );

							$img->save( $file, '', 70 );

							@copy( $file, $thumb );

							$content = str_replace ( $s, $html_file, $content);

							$filetxt = PATH_PAGES . $_POST['slug'] . DS . 'index.txt';

							@file_put_contents( $filetxt, $content, LOCK_EX );
						}

					}
				}

			}

		}

		if ( $this->getValue('compress_img') )
		{
		
			$imgs = json_decode( file_get_contents( DB_IMG, NULL, NULL, 50), TRUE );

			if ( !is_array( $imgs ) )
				$imgs = array();

			$coverImage = $_POST['coverImage'];

			if ( empty( $coverImage ) )
				return false;

			if( in_array( $coverImage, $imgs ) )
				return false;

			$file = PATH_UPLOADS_PAGES . $uuid . DS . $coverImage;

			if ( !file_exists( $file ) )
				return false;

			$img = new SimpleImage;
		
			$img->load( $file );

			$img->save( $file, '', 70 );
			
			//$img->scale( 70 );

			//$img->save( $file );

			array_push( $imgs, $coverImage );

			$dt = "<?php defined('BLUDIT') or die('Bludit CMS.'); ?>" . PHP_EOL . json_encode( $imgs, JSON_PRETTY_PRINT );

			@file_put_contents( DB_IMG, $dt, LOCK_EX );
		}
				
	}
	
	public function returnImgUrl($img)
	{
		if ( strpos( $img, '?' ) !== false ) 
		{
			$img = explode('?', $img);
			$img = $img['0'];
		}
		
		return $img;
	}

	public function site_url() 
	{
		global $site;
		
		$site_url = $site->url();
						
		$last = $site_url[strlen($site_url)-1];
						
		if ( $last == '/' )
			$site_url = rtrim( $site_url, "/" );//$site_url . '/';
		
		return $site_url;
		
	}
	
	/**
	 * Returns all the images found in a text
	 *
	 * @access public
	 * @param string $content
	 * @return array
	 */
	public function getImages($content)
	{
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/ii', $content, $matches);
			
		if (!empty($matches[1]))
			return $matches[1];

		return false;
	}

	public function get_HostName ( $url )
	{
		$url_details = parse_url( $url );

		$host = str_replace( 'www.', '', $url_details['host'] );
		
		return $host;
	
	}

	public function siteHead()
	{
		$html = '';

		if ( $this->getValue('https_redir') )
		{
			$html .= '<script>if (document.location.protocol != "https:") {document.location = document.URL.replace(/^http:/i, "https:");}</script>' . PHP_EOL;
		}

		if ( Text::isNotEmpty( $this->getValue( 'header_code' ) ) )
		{
			$html .= html_entity_decode( $this->getValue('header_code') ) . PHP_EOL;
		}
		
		return $html;
	}
	
	public function siteBodyEnd()
	{
		
		$html = '';

		if ( Text::isNotEmpty( $this->getValue( 'footer_code' ) ) )
			$html .= html_entity_decode( $this->getValue('footer_code') ) . PHP_EOL;

		return $html;

	}

}
