<?php
class pluginCache extends Plugin {
	
	public function init()
	{
				
		// Fields and default values for the database of this plugin
		$this->dbFields = array(
			'hash'=>'',
			'duration'=>86400,
			'cachehome'=>false,
			'compressfile'=>true
		);
		
		// Disable default form buttons
		//$this->formButtons = false;

	}
	
	public function post()
	{
		if (isset($_POST['clearCache'])) 
		{
			return $this->rrmdir( $this->workspace() );
		}
		
		$this->db['duration'] = $_POST['duration'];
		$this->db['cachehome'] = $_POST['cachehome'];
		$this->db['compressfile'] = $_POST['compressfile'];
		
		$this->rrmdir( $this->workspace() );
						
		return $this->save();
	}

	public function form()
	{
		global $L;
		
		$html  = '';
		
		$html .= '<div>';
		$html .= '<label>' . $L->get('cache-duration') . '</label>';
		$html .= '<input value="' .  $this->getValue('duration') . '" type="number" name="duration" id="jsduration" placeholder="Cache in Seconds" step="any" min="1" max="604800" required>'; //1 WEEK
		$html .= '<span class="tip">'.$L->get('cache-duration-tip').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('cachehome').'</label>';
		$html .= '<input type="checkbox" name="cachehome" id="jscachehome" value="true" ' . ($this->getValue('cachehome') ? 'checked' : '' ) . '>';
		$html .= '<span class="tip">'.$L->get('cachehome-tip').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('compressfile').'</label>';
		$html .= '<input type="checkbox" name="compressfile" id="jscompressfile" value="true" ' . ($this->getValue('compressfile') ? 'checked' : '' ) . '>';
		$html .= '<span class="tip">'.$L->get('compressfile-tip').'</span>';
		$html .= '</div>';
		
		$html .= '<hr />';	
		$html .= '<div>';
		$html .= '<button name="clearCache" value="true" class="left small blue" type="submit"><i class="uk-icon-eraser"></i> '.$L->get('clear-cache').'</button>';
		$html .= '</div>';
	
		return $html;
	}
	
	public function beforeAll()
	{
		$expireTime = $this->getValue('duration');
		
		if (file_exists( $this->cache() ) && time() - $expireTime < filemtime( $this->cache() )) 
		{
			if ( $this->getValue('compressfile') )
				ob_start(array($this,'compress'));
			else
				ob_start();
			
			include( $this->cache() );
			
			ob_end_flush();
			
			exit;
		}
		
		if ( !file_exists( $this->cache() ) && $this->is_allowed() )
		{
			if ( $this->getValue('compressfile') )
				ob_start(array($this,'compress'));
			else
				ob_start();
		}
		//ob_start(array($this,'compress'));
		
	}
	
	private function cache_message() {
		
		return '<!-- This website\'s performance optimized by Bludit Cache Plugin. Read more: https://g3ar.gr/en/blog/post/cache-plugin-bludit-cms/ - Debug: cached@' . time() . ' -->';
	}
	
	private function is_allowed() 
	{
		global $url;
				
		$allow = false;
		
		if ( ( $url->whereAmI() == 'page' ) && !$url->notFound() )
			$allow = true;
		
		elseif ( ( $url->whereAmI() == 'home' ) && ( $this->getValue('cachehome') ) && ( ( strpos( $_SERVER['REQUEST_URI'], 'page=' ) === false ) || ( $url->pageNumber() == 1 ) ) )
			$allow = true;
		
		return $allow;
	}
	
	public function afterSiteLoad()
	{
		global $url;
		
		//Make sure that we cache only posts and pages		
		if ( !file_exists( $this->cache() ) && ( $this->is_allowed() ) )
		{
			
			if ( ( $url->whereAmI() == 'home' ) && ( (strpos( $_SERVER['REQUEST_URI'], 'page=' ) !== false) || ( $url->whereAmI() == 'blog' ) || !$this->getValue('cachehome') ) )
			{
				return false;
			}
			
			$fp = fopen( $this->cache(), 'w');  //open file for writing
			fwrite($fp, ob_get_contents() . $this->cache_message()); //write contents of the output buffer in Cache file
			fclose($fp); //Close file pointer
			ob_end_flush();
		}

		return;
	}
		
	// Define Cache-file
	public function cache( $file = null)
	{
		global $url;
		
		$filename = '';
		
		$salt = $this->getValue('hash');
		
		if (!empty ($_POST['slug']))
			$cacheFile = $file;
		
		elseif ( ( $url->whereAmI() == 'home' ) && $this->getValue('cachehome') )
			$cacheFile = 'home';
		
		elseif ( !$url->notFound() && ( $url->whereAmI() == 'page' ) )
			$cacheFile = $url->slug();
		
		else 
			return false;
					
		$filename = md5 ($cacheFile . $salt) . '.php';
		$c_letter = strtolower(substr($cacheFile, 0, 1));
		$c_dir = $this->workspace(). $c_letter;
			
		if (!is_dir($c_dir))
			mkdir($c_dir, 0755, true);
						
		return $c_dir . '/' . $filename;
	}
	
	public function workspace()
	{
		return PATH_CONTENT . 'cache' . DS . 'site' . DS;
	}
	
	public function afterPageModify()
	{
		$cacheFile = $this->cache( $_POST['slug'] );
		
		if ( file_exists( $cacheFile ) )
			unlink ( $cacheFile );
		
		if ( file_exists( $this->cache( 'home' ) ) )
			unlink ( $this->cache( 'home' ) );
		
		return;
	}

	public function afterPageDelete()
	{
		
		$cacheFile = $this->cache( $_POST['slug'] );
				
		if ( file_exists( $cacheFile ) )
			unlink ( $cacheFile );
		
		if ( file_exists( $this->cache( 'home' ) ) )
			unlink ( $this->cache( 'home' ) );
		
		return;
	}
	
	// Uninstall the plugin and delete the workspace directory
	public function uninstall()
	{
		parent::uninstall();
		$workspace = $this->workspace();
		return Filesystem::deleteRecursive($workspace);
	}
	
	// Install the plugin and create the workspace directory
	public function install($position=0)
	{
		parent::install($position);
		
		$workspace = $this->workspace();
		
		mkdir($workspace, 0755, true);
		
		$this->db['hash'] = $this->generate_key();
						
		return $this->save();
	}
	
	public function rrmdir($dir) {
	  if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
		  if ($object != "." && $object != "..") {
			if (filetype($dir."/".$object) == "dir") 
			   pluginCache::rrmdir($dir."/".$object); 
			else unlink   ($dir."/".$object);
		  }
		}
		reset($objects);
		rmdir($dir);
	  }
	}
	
	public function generate_key() {
		return substr(str_shuffle('qwertyuiopasdfghjklmnbvcxz1234567890'), 0, 10);
	}
	
	function compress($buffer)
	{
		$search = array(
			'/\>[^\S ]+/s', //strip whitespaces after tags, except space
			'/[^\S ]+\</s', //strip whitespaces before tags, except space
			'/(\s)+/s'  // shorten multiple whitespace sequences
			);
		
		$replace = array(
			'>',
			'<',
			'\\1'
			);
	  $buffer = preg_replace($search, $replace, $buffer);
	  
	  return $buffer;
	}
}
?>
