<?php
	require_once 'lib/Api.php';
	
	abstract class DanApi extends Api{
		protected $url;
		protected $always_login = false;
		
		public function hash_password( $pass ){
			return sha1( "choujin-steiner--" . $_GET['hash'] . "--" );
		}
		
	//General parsing functions
		protected function transform_url( &$url, $type ){
			if( $url && $url[0] == '/' )
				$url = $this->url . rtrim( $url, '/' );
		}
		protected function transform_date( $date ){
			
		}
		
	//Parsing of post data
		abstract protected function get_post_mapping(); //This changes so much, must be implemented
		protected function transform_post( &$data ){
			//This function may be overloaded to standalize formatting
			//but remember to call this implementation too!
			
			//Transform links
			if( isset( $data['file_url'] ) )
				$this->transform_url( $data['file_url'], 'file' );
			if( isset( $data['thumb_url'] ) )
				$this->transform_url( $data['thumb_url'], 'thumb' );
			if( isset( $data['preview_url'] ) )
				$this->transform_url( $data['preview_url'], 'preview' );
			if( isset( $data['reduced_url'] ) )
				$this->transform_url( $data['reduced_url'], 'reduced' );
			
			//Remove dublicate images
			if(	isset( $data['preview_url'] )
				&&	(	$data['preview_url'] == $data['url']
					||	( isset( $data['reduced_url'] )
						&& $data['preview_url'] == $data['reduced_url'] 
						)
					)
				){
				$data['preview_url'] = NULL;
				$data['preview_height'] = NULL;
				$data['preview_width'] = NULL;
				$data['preview_filesize'] = NULL;
			}
			if( isset( $data['reduced_url'] ) && $data['reduced_url'] == $data['url'] ){
				$data['reduced_url'] = NULL;
				$data['reduced_height'] = NULL;
				$data['reduced_width'] = NULL;
				$data['reduced_filesize'] = NULL;
			}
			
			//Get rid of some commonly wrong NULLs
			if( $data['source'] === "" )
				$data['source'] = NULL;
		}
		protected final function parse_post( $data ){
			$arr = $this->element_to_array( $data );
			$post = $this->transform_array( $arr, $this->get_post_mapping() );
			$this->transform_post( $post );
			return $post;
		}
		
		
	//Parsing of tag data
		protected function get_tag_mapping(){
			//This seems pretty much the same across the boorus
			//so lets just make a default implementation
			return array(
					'id' => 'name',
					'type' => 'type',
					'count' => 'count',
					'ambiguous' => 'ambiguous'
				);
		}
		protected function transform_tag( &$data ){
			//Nothing to do here
		}
		protected final function parse_tag( $data ){
			$arr = $this->element_to_array( $data );
			$tag = $this->transform_array( $arr, $this->get_tag_mapping() );
			$this->transform_tag( $tag );
			return $tag;
		}
		
	//Parsing of note data
		//protected function parse_note( $data );
		
		
		
		protected function get_url( $handler, $action, $format, $parameters=array(), $login=false ){
			if( $this->always_login || $login ){
				if( !$this->username )
					die( "Missign login which is needed for $handler/$action" );
				$parameters['login'] = $this->username;
				$parameters['password_hash'] = $this->password_hash;
			}
			
			//make parameters
			$para = "?";
			foreach( $parameters as $key => $value )
				$para .= "$key=$value&";
			$para = rtrim( $para, "&" );
			
			//Create URL
			return $this->url . "$handler/$action.$format$para";
		}
		
	//Fetch methods
		public function index( $search=NULL, $page=1, $limit=NULL ){
			//Format parameters
			$para = array();
			if( $search != NULL && $search != "" )
				$para['tags'] = $search;
			if( $limit )
				$para['limit'] = $limit;
			if( $page > 1 )
				$para['page'] = $page;
			
			//Retrive raw data from the server
			$url = $this->get_url( 'post', 'index', 'xml', $para );
			$data = $this->get_xml( $url );
			if( !$data )	//Kill if failed
				return NULL;
			
			//Parse array of posts
			$posts = array();
			foreach( $data->post as $post_data )
				$posts[] = $this->parse_post( $post_data );
			
			//Get amount of posts total
			$posts['count'] = (string)$data['count'];
			
			//Return posts
			return $posts;
		}
		
		public function post( $id ){
			$posts = $this->index( 'id:' . $id );
			return isset( $posts[0] ) ? $posts[0] : NULL;
		}
		
		public function tag_index( $search, $page=1, $limit=NULL ){
			//Format parameters
			$para = array();
			if( $search != NULL && $search != "" )
				$para['name_pattern'] = $search;
			if( $limit )
				$para['limit'] = $limit;
			if( $page > 1 )
				$para['page'] = $page;
			
			
			//Retrive raw data from the server
			$url = $this->get_url( 'tag', 'index', 'xml', $para );
			$data = $this->get_xml( $url );
			if( !$data )	//Kill if failed
				return NULL;
		}
	}
	
	
	class DanbooruApi extends DanApi{
		public function __construct(){
			$this->url = "http://danbooru.donmai.us/";
			$this->always_login = true;
		}
		
		protected function get_post_mapping(){ return DanbooruApi::$post_mapping; }
		public static $post_mapping = array(
			'id'	=>	'id',
			'hash'	=>	'md5',
			'author'	=>	'author',
			'creation_date'	=>	'created_at',
			
			'parent_id'	=>	'parent_id',
			'has_children'	=>	'has_children',
			'has_notes'	=>	'has_notes',
			'has_comments'	=>	'has_comments',
			'source'	=>	'source',
			
			'tags'	=>	'tags',
			'score'	=>	'score',
			'rating'	=>	'rating',
			'status'	=>	'status',
			
			'url'	=>	'file_url',
			'width'	=>	'width',
			'height'	=>	'height',
			'filesize'	=>	'file_size',
			
			'thumb_url'	=>	'preview_url',
			'thumb_width'	=>	'preview_width',
			'thumb_height'	=>	'preview_height',
		//	'thumb_filesize'	=>	NULL,
			
			'preview_url'	=>	'sample_url',
			'preview_width'	=>	'sample_width',
			'preview_height'	=>	'sample_height',
		//	'preview_filesize'	=>	NULL,
			
		//	'reduced_url'	=>	'jpeg_url',
		//	'reduced_width'	=>	'jpeg_width',
		//	'reduced_height'	=>	'jpeg_height',
		//	'reduced_filesize'	=>	'jpeg_file_size'
		);
		
		public function get_name(){ return "Danbooru"; }
		public function get_code(){ return "dan"; }
	}
?>