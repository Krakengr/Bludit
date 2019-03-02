<?php

class pluginBlumap extends Plugin {

	public function init()
	{
		$this->dbFields = array(
			'ping_time'=>''
		);
	}
	
	public function form()
	{
		global $L, $site;

		$html  = '<div>';
		$html .= '<label>'.$L->get('Sitemap URL').'</label>';
		$html .= '<a href="'.$this->sitemapURL().'">'.$this->sitemapURL().'</a>';
		$html .= '</div>';

		return $html;
	}

	private function createXML()
	{
		global $site;
		global $pages;
		
		$doc = new DOMDocument('1.0', 'UTF-8');
		
		//Non Friendly XML code
		$doc->formatOutput = false;
		
		// create urlset element
		$urlset = $doc->createElement("urlset");
		$url_node = $doc->appendChild($urlset);

		//set attributes
		$url_node->setAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
		$url_node->setAttribute("xmlns","http://www.sitemaps.org/schemas/sitemap/0.9");
		$url_node->setAttribute("xmlns:mobile","http://www.google.com/schemas/sitemap-mobile/1.0");
		$url_node->setAttribute("xmlns:image","http://www.google.com/schemas/sitemap-image/1.1");
		$url_node->setAttribute("xmlns:schemaLocation","http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd");
		
		
		// Get Posts DB
		$pageNumber = 1;
		$amountOfItems = -1;
		$onlyPublished = true;
		$list = $pages->getList($pageNumber, $amountOfItems, $onlyPublished);
		
		foreach($list as $pageKey) {
			try {
				// Create the page object from the page key
				$page = new Page($pageKey);
				
			} catch (Exception $e) {
				continue;
			}
			
			if ( strpos( $page->content(), 'noindex' ) !== false )
			{
				continue;			
			}
						
			$post_url = $doc->createElement('url');
			$post_url->appendChild($doc->createElement('loc', $page->permalink()));
			$post_url->appendChild($doc->createElement('mobile:mobile'));
			
			if ( Text::isNotEmpty( $page->coverImage() ) )
			{
				$post_image = $page->coverImage($absolute=true);
				
				//Find image's name
				$filename = pathinfo($post_image, PATHINFO_FILENAME);
				
				//Append image
				$img = $post_url->appendChild($doc->createElement('image:image'));
				$img_loc = $img->appendChild($doc->createElement('image:loc', $post_image));
				$img_title = $img->appendChild($doc->createElement('image:title', $filename));
			}
			
			if ( Text::isNotEmpty( $page->dateModified() ) )
				$date = date( 'c', strtotime( $page->dateModified() ) );
			else
				$date = date( 'c', strtotime( $page->dateRaw() ) );
			
			//Time this post Created OR edited - in ISO 8601
			$post_url->appendChild($doc->createElement('lastmod', $date));
						
			if ( $page->type() == "published" ) {
				$post_url->appendChild($doc->createElement('changefreq', 'daily'));
				$post_url->appendChild($doc->createElement('priority', '0.6'));
			} elseif ( $page->type() == "static" ) {
				$post_url->appendChild($doc->createElement('changefreq', 'monthly'));
				$post_url->appendChild($doc->createElement('priority', '0.3'));
			}
			

			$urlset->appendChild($post_url);
		}
		
		// Create main site's elements and put'em last //
		$date = date('c', time()); //Time this XML Created - in ISO 8601
		$url = $doc->createElement('url');
		$url->appendChild($doc->createElement('loc', $site->url()));
		$url->appendChild($doc->createElement('changefreq', 'daily'));
		$url->appendChild($doc->createElement('priority', '1.0'));
		$url->appendChild($doc->createElement('lastmod', $date));
		$urlset->appendChild($url);
		//.Create main site's elements //
		
		$doc->save($this->workspace().'sitemap.xml');
		
		//TODO
		
		//$sm =  file_get_contents( $this->workspace().'sitemap.xml' );
        //$gz = gzopen( $this->workspace().'sitemap.xml', 'w' );
		//if( gzwrite($gz,$sm) == -1 ) {
            //alert admin with location
        //}
		
	}

	public function install($position=0)
	{
		parent::install($position);
		$this->createXML();
	}

	public function afterPageCreate()
	{
		$this->createXML();
		$this->ping();
	}

	public function afterPageModify()
	{
		$this->createXML();
	}

	public function afterPageDelete()
	{
		$this->createXML();
		$this->ping();
	}
	
	public function sitemapURL()
	{
		global $site;
		
		$site_url = $site->url();
							
		$last = $site_url[strlen($site_url)-1];
							
		if ($last != '/')
			$site_url = $site_url . '/';
		
		return $site_url . 'sitemap.xml';
		
	}
	
	private function ping()
	{
		if ($this->getValue('pingGoogle')) {
			$url = 'https://www.google.com/webmasters/sitemaps/ping?sitemap='.Theme::sitemapUrl();
			TCP::http($url, 'GET', true, 3);
		}

		if ($this->getValue('pingBing')) {
			$url = 'https://www.bing.com/webmaster/ping.aspx?sitemap='.Theme::sitemapUrl();
			TCP::http($url, 'GET', true, 3);
		}
	}
	
	public function ping_() {
		
		//global $site;
		
		if ( Text::isNotEmpty( $this->getValue( 'ping_time' ) ) && ( ( $this->getValue( 'ping_time' ) + 3600 ) > time()  ) )
			return;
		
		$sitemap_url = $this->sitemapURL();
			
        $curl_req = array();
        $urls = array();
		
        // below are the SEs that we will be pining
        $urls[] = "https://www.google.com/webmasters/tools/ping?sitemap=" . urlencode( $sitemap_url );
        $urls[] = "https://www.bing.com/webmaster/ping.aspx?siteMap=" . urlencode( $sitemap_url );
        $urls[] = "https://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=YahooDemo&amp;url=" . urlencode( $sitemap_url );
		$urls[] = "https://blogs.yandex.ru/pings/?status=success&url=" . urlencode( $sitemap_url );

        foreach ($urls as $url)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURL_HTTP_VERSION_1_1, 1);
            $curl_req[] = $curl;
        }
       
	   //initiating multi handler
        $multiHandle = curl_multi_init();

        // adding all the single handler to a multi handler
        foreach( $curl_req as $key => $curl )
        {
            curl_multi_add_handle( $multiHandle,$curl );
        }
        
		$isactive = null;
        
		do
        {
            $multi_curl = curl_multi_exec( $multiHandle, $isactive );
        }
        
		while ( $isactive || $multi_curl == CURLM_CALL_MULTI_PERFORM );

        $success = true;
        
		foreach( $curl_req as $curlO )
        {
            if( curl_errno( $curlO ) != CURLE_OK )
            {
                $success = false;
            }
        }
        
		curl_multi_close( $multiHandle );
		
		$this->db['ping_time'] = time();
        
		return $success;
    }

	public function beforeAll()
	{
		$webhook = 'sitemap.xml';
		if( $this->webhook($webhook) ) {
			// Send XML header
			header('Content-type: text/xml');
			$doc = new DOMDocument();

			// Workaround for a bug https://bugs.php.net/bug.php?id=62577
			libxml_disable_entity_loader(false);

			// Load XML
			$doc->load($this->workspace().'sitemap.xml');

			libxml_disable_entity_loader(true);

			// Print the XML
			echo $doc->saveXML();

			// Terminate the run successfully
			exit(0);
		}
	}
}