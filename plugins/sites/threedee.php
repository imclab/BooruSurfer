<?php
	/*	This file is part of BooruSurfer.

		BooruSurfer is free software: you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.

		BooruSurfer is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with BooruSurfer.  If not, see <http://www.gnu.org/licenses/>.
	*/
	
	require_once "plugins/sites/dan.php";
	
	class ThreeDeeBooruApi extends DanbooruApi{
		public function __construct(){
			$this->url = "http://behoimi.org/";
		}
		
		protected function transform_url( &$url, $type ){
			parent::transform_url( $url, $type );
			
			//We need proxy here
			if( ($type == 'preview') || ($type == 'file') ){
				$code = $this->get_code();
				$proxy_url = str_replace( $this->url, "", $url );
				$url = "/$code/proxy/$proxy_url";
			}
		}
		
		public function get_name(){ return "3dbooru"; }
		public function get_code(){ return "threedee"; }
	}
	
	
?>