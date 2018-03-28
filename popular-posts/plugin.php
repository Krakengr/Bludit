<?php

class pluginPopularPosts extends Plugin {

	public function init()
	{
		define('DB_COUNTER', PATH_ROOT.'bl-content'.DS.'databases'.DS.'counter.php');
		
		$this->dbFields = array(
			'num_items'=>'5',
			'label'=>'Popular Posts',
		);
	}
	
	public function form()
	{
		global $Language;
		
		$nums = array('1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9', '10' => '10');
					
		$html = '<div>';
		$html .= '<label>'.$Language->get('label').'</label>';
		$html .= '<input id="jslabel" name="label" type="text" value="'.$this->getValue('label').'">';
		$html .= '<span class="tip">'.$Language->get('label-tip').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$Language->get('items').'</label>';
		$html .= '<select name="num_items" id="jsnum_items">';
		
		foreach ($nums as $num) {
			$html .= '<option value="' . $num . '" ';
			if ( Text::isNotEmpty( $this->getValue( 'num_items' ) ) && $this->getValue('num_items') == $num )
				$html .= 'selected';
			$html .= '>' . $num . '</option>';
		}
		
		$html .= '</select>';
		//$html .= '<input id="jsnum_items" name="num_items" type="text" value="'.$this->getValue('num_items').'">';
		$html .= '</div>';
				
		return $html;
	}
	
	
	public function beforeSiteLoad() {
		
		global $page, $Url;
				
		if ( !$Url->notFound() && ( $Url->whereAmI() == 'page' ) )
		{
			if (!isset($_SESSION['counter'][$post_id])) 
			{
				$data = json_decode(file_get_contents(DB_COUNTER, NULL, NULL, 50), TRUE);
				
				if (isset($data[$post_id]))
				{
					$curr = $data[$post_id]['num'];
					$numm = $curr + 1;
					$data[$post_id] = array('num' => $numm, 'title' => $page->title(), 'url' => $page->permalink(), 'date' => time() );
				} else {
					$data[$post_id] = array('num' => 1 , 'title' => $page->title(), 'url' => $page->permalink(), 'date' => time());
				}
				
				$data = json_encode($data, JSON_PRETTY_PRINT);
				$data_dt = "<?php defined('BLUDIT') or die('Bludit CMS.'); ?>" . PHP_EOL . $data;
				file_put_contents( DB_COUNTER, $data_dt );
				
				$_SESSION['counter'] = array();
				$_SESSION['counter'][$post_id] = 0;
			}

	
		}
	}
	
	
	public function siteSidebar()
	{	
		
		// HTML for sidebar
		$html  = '<div class="plugin plugin-popular-posts">';

		// Print the label if not empty
		$label = $this->getValue('label');
		
		if (!empty($label)) {
			$html .= '<h2 class="plugin-label">'.$label.'</h2>';
		}
		
		$data = json_decode(file_get_contents(DB_COUNTER, NULL, NULL, 50), TRUE);
		
		uasort( $data, function($a, $b) { return $b['num'] - $a['num']; } );
		
		$data = array_slice($data, 0, $this->getValue('num_items'));
		
		if ( empty( $data ) )
			return;
		
		$html .= '<div class="plugin-content">';
		$html .= '<ul>';

		foreach ($data as $key=>$row) {
			$html .= '<li>';
			$html .= '<a href="' . $row['permalink'] . '">' . $row['title'] . '</a>';
			$html .= '</li>';
		}

		$html .= '</ul>';
 		$html .= '</div>';
 		$html .= '</div>';
			
		return $html;
	}
	
}