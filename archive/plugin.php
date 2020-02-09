<?php

class pluginArchive extends Plugin {

	public function init()
	{

		$this->dbFields = array(
			'label'=>'Archive',
			'page'=>''	// <= Slug url of sitemap page
			);
	}
	
	public function form()
	{
		global $L, $pages;
		
		//Get only pages
		$list = $pages->getList( 1, -1, false, true, false, false, false );
				
		$html = '<div class="uk-form-select" data-uk-form-select>';		
		$html .= '<label for="jspage">' .$L->get('select-a-page-archive').'</label>';
		$html .= '<select name="page" class="uk-form-width-medium">';
		$html .= '<option value="0">---------------------</option>';
		
        foreach ($list as $pageKey)
        {
        	$page = new Page($pageKey);

            $html .= '<option value="'.$page->key().'"'.( ( $this->getValue('page') === $page->key() ) ? ' selected="selected"' : '' ).'>' . $page->title() . '</option>';
        }

		$html .= '</select>';	
		$html .= '</div>';	
			
		return $html;
	}
	
	
	public function pageEnd()
	{
		global $url, $L, $pages, $tags, $categories;

		if( ( $url->whereAmI() == 'page' ) && ( $url->slug() == $this->getValue('page') ) )
		{
		   $html = '';
		   
		   $html .= '<h2>Archive</h2>';
		   $html .= '<ul>';
		   
		   $list = $pages->getList(1, -1, true, false, true, false, false);

		   foreach ($list as $pageKey) 
		   {
				
				$page = new Page($pageKey);
				$html .= '<li><a href="' . $page->permalink() . '" title="' . htmlspecialchars( $page->title() ) . '">' . $page->title() . '</a></li>';
				
		   }

		   unset ( $list ) ;

		   $html .= '<h2>Posts Per Category</h2>';
		   		   
		   foreach ($categories->db as $key=>$fields)
		   {
			   $count = count($fields['list']);
				
				if ( $count == 0 )
					continue;

				$list = buildPagesFor('category', $key, false);

				$html .= '<ul><li><h3>' . $fields['name'] . '</h3>';
				$html .= '<ul>';
				
				foreach ($list as $page) 
				{

					$html .= '<li><a href="' . $page->permalink() . '" title="' . htmlspecialchars( $page->title() ) . '">' . $page->title() . '</a></li>';
				
				}
				
				$html .= '</ul>';
				$html .= '</li></ul>';
		   }

		   unset ( $list ) ;
		   
		   $html .= '<h2>Categories</h2>';
		   $html .= '<ul>';
		   
		   foreach ($categories->db as $key=>$fields)
		   {
				$count = count($fields['list']);
				
				if ( $count>0) {
					$html .= '<li>';
					$html .= '<a href="'.DOMAIN_CATEGORIES . $key . '">';
					$html .= $fields['name'];
					$html .= ' ('. $count .')';
					$html .= '</a>';
					$html .= '</li>';
				}
		   }
		   
		   $html .= '</ul>';	
		   
		   /*
		   $html .= '<h2>Tags</h2>';
		   
		   $html .= '<ul>';
		   
		   foreach( $dbTags->db as $key=>$fields ) 
		   {
				$html .= '<li>';
				$html .= '<a href="'.DOMAIN_TAGS.$key.'">';
				$html .= $fields['name'];
				$html .= '</li>';
		   }
		   
		    $html .= '</ul>';	
			*/
			unset ($pages, $list);
			
			return $html;
		}
		
		return;
	}
}