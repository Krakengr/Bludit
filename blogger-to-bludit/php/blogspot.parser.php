<?php
/**
 * Blogspotparser 
 * 
 * Blogspotparser is a class that takes the backup xml file from blogspot and parses it
 * into a basic array, returning the posts as is, with html markup or as clean text.
 * 
 * @author 		: Benjamin Horn
 * @project		: Fiinixdesign.eu
 * @file		: class.blogspot.parser.php
 * @version		: 1.0.0
 * @created		: 2012-09-22
 * @updated		: 2012-09-23
 *
 * @usage		:
 *
 * $p = new Blogspotparser( $XMLDATASTRING );	// Initiate class
 * $p->fetch_entries_clean();					// Output posts as clean data
 * $p->fetch_entries();							// Output posts with original markup
 * $p->fetch_amount_of_entries();				// Fetch amount of posts in backup
 * $p->fetch_image_links();						// Ouputs an array with image links withing the posts
 *
 */

class Blogspotparser {

	private $rawdata;		// Stored rawdata
	private $parsed;		// Stored parsed data ( as array ) 
	private $posts;			// Stored posts
	
    /**
     * __construct()
     *
     * @param    string $data XML data
     *
     */
	public function __construct( $data ) {
		$this->rawdata = $data;
		$this->parse();
		$this->posts = $this->generate_posts();
	}
	
	/**
     * function fetch_amount_of_entries()
     * Returns how many posts that the backup contains
	 * 
     * @return    integer
     *
     */
	public function fetch_amount_of_entries() {
		return count( $this->posts );
	}	
	
	/**
     * function fetch_entries()
     * Returns all the posts as is.
	 *
     * @return    array
     *
     */
	public function fetch_entries() {
		return $this->posts;
	}
	
	/**
     * function fetch_entries_clean()
     * Returns all the posts without markup and extra whitespace.
	 *
     * @return    array
     *
     */
	public function fetch_entries_clean() {
		$arr = array();
		foreach( $this->posts as $k => $post ) {
			$arr[$k] = $this->scrub( $post );
		}
		return $arr;
	}
	
	/**
     * function fetch_image_links()
     * Searches for and outputs links to images within the messages
	 *
     * @return   array
     *
     */
	public function fetch_image_links() {
		$links = array();
		$final = array();
		foreach( $this->posts as $post ) {
			preg_match_all("/src\=\"(.+?)?\"/", $post['content'], $matches);
			$links = array_merge( $links, $matches[1] );
		}
		foreach( $links as $link ) {
			if( strpos($link, 'blogspot.com') ) {
				$final[] = $link;
			}
		}

		return $final;
	}
	/**
     * function generate_posts()
     * Parses all the posts out of the backup string
	 *
     * @param    array $allowable_fields Which fields from the posts that we should save
     * @return   array
     *
     */
	private function generate_posts( $allowable_fields = array('content','title','published','updated','category', 'link', 'author') ) {
		$arr = array();
		$counter = 0;
		foreach( $this->parsed['entry'] as $k => $entry ) {
			preg_match('/settings|layout/', $entry['id'], $matches );	
			if( count( $matches ) == 0 ) {
				$arr[$counter] = $entry;
				
				foreach( $arr[$counter] as $c => $val ) {
					if( !in_array( $c, $allowable_fields ) ) {
						unset( $arr[$counter][$c] );
					}
				}
				$counter++;
			}
		}
		return $arr;
	}
	
	/**
     * function scrub()
     * Recursivly cleans all the data within an array
	 *
     * @param    Mixed $str String or Array that should be cleaned
     * @return   Mixed
     *
     */
	private function scrub( $str ) {
		if( is_array( $str ) ) {
			foreach( $str as $k => $item ) {
				$str[$k] = $this->scrub( $item );
			}
			return $str;
		}
		return $this->clean( $str );
	}
	/**
     * function clean()
     * Removes whitespace and html markup from a string
	 *
     * @param    string $str String that should be cleaned
     * @return   string
     *
     */
	private function clean( $str ) {
		$str = strip_tags( $str );
		$str = preg_replace( '/\s+/', ' ', $str );
		return $str;
	}
	
	/**
     * function parse()
     * Parses the xml string, and rebuilds it to an array
     *
     */
	private function parse() {
		$this->parsed = $this->object2array( simplexml_load_string( $this->rawdata ) );
		return true;
	}
	
	/**
     * function object2array()
     * Recursively rebuilds an object to an array, the input can be mixed
	 * array/object
	 *
     * @param    Mixed $obj The objecy/array that should be converted
     * @return   array
     *
     */
	private function object2array( $obj ) {
		if( is_object( $obj ) ) {
			$obj = get_object_vars( $obj );
		}
		if( is_array( $obj ) ) {
			return array_map( array( $this, __function__) , $obj );
		}	
		return $obj;
	}
}
?>