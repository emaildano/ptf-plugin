<?php


require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-blog-header.php';

class Import {
	
	protected $db;
	protected $hoodCount;
	protected $styleCount;
	protected $beerCount;
	protected $brewCount;
	protected $barCount;
	protected $breweries;
	
	//make global wpdb a property of the class
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}
	
	//get number of beer styles imported
	public function getStyleCount() {
		return $this->styleCount - 1;
	}
		
	//get number of neighborhoods imported
	public function getHoodCount() {
		return $this->hoodCount - 1;
	}

	//get number of beers imported
	public function getBeerCount() {
		return $this->beerCount - 1;
	}
	
	//get number of breweries imported
	public function getBrewCount() {
		return $this->brewCount - 1;
	}
		
	//get number of bars imported
	public function getBarCount() {
		return $this->barCount - 1;
	}
	
	//start uploading content separated by different .csv files
	public function upload(){
		$this->uploadHoods();
		$this->uploadStyles();
		$this->uploadBreweries();
		$this->uploadBeers();
		$this->uploadBars();
	}	
	
	//upload neighborhoods, delete all terms in taxonomy first
	protected function uploadHoods(){
		$this->hoodCount = 0;
		$upload = new FileUpload($_SERVER['DOCUMENT_ROOT']. "/wp-content/plugins/ptf-plugin/data/", 'hoodcsv');

		if($upload->success){
			$handleFile = fopen($upload->handle, "r");
			$this->removeTerms('ptf_hoods');
			while (($hood = fgetcsv($handleFile)) !== false) {
				if(empty($hood[0])){
					continue;
				}
				if($this->hoodCount > 0){
					$t = $hood[0];
					wp_insert_term( $t, 'ptf_hoods' );
				}
				$this->hoodCount++;
			}
		}
	}
	
	//upload beer styles, delete all terms in taxonomy first
	protected function uploadStyles(){
		$this->styleCount = 0;
		$upload = new FileUpload($_SERVER['DOCUMENT_ROOT']. "/wp-content/plugins/ptf-plugin/data/", 'stylecsv');

		if($upload->success){
			$handleFile = fopen($upload->handle, "r");
			$this->removeTerms('ptf_beer_style');
			while (($style = fgetcsv($handleFile)) !== false) {
				if(empty($style[0])){
					continue;
				}
				if($this->styleCount > 0){
					$t = $style[0];
					$args = array(
						'description' => $style[1]
					);
					wp_insert_term( $t, 'ptf_beer_style', $args);
				}
				$this->styleCount++;
			}
		}
	}
	
	//upload beer breweries, delete all terms in taxonomy first
	protected function uploadBreweries(){
		$this->brewCount = 0;
		$this->breweries = array();
		$upload = new FileUpload($_SERVER['DOCUMENT_ROOT']. "/wp-content/plugins/ptf-plugin/data/", 'breweriesscsv');

		if($upload->success){
			$handleFile = fopen($upload->handle, "r");
			$this->removeTerms('ptf_breweries');
			while (($brew = fgetcsv($handleFile)) !== false) {
				if(empty($brew[0])){
					continue;
				}
				if($this->brewCount > 0){
					$t = $brew[0];
					$term = wp_insert_term( $t, 'ptf_breweries');
					if(!is_array($term)){
						echo $brew[0];
					}
					$this->breweries[] = $t;
				}
				$this->brewCount++;
			}
		}
	}
	
	//upload beers, delete all posts in custom content type first
	protected function uploadBeers(){
		$this->beerCount = 0;
		$upload = new FileUpload($_SERVER['DOCUMENT_ROOT']. "/wp-content/plugins/ptf-plugin/data/", 'beerscsv');

		if($upload->success){
			$handleFile = fopen($upload->handle, "r");
			$this->deletePosts('ptf_beers');
			while (($beer = fgetcsv($handleFile)) !== false) {
				if(empty($beer[0])){
					continue;
				}
				if($this->beerCount > 0){
					$my_post = array(
						'post_title' => $beer[0],
						'post_type' => 'ptf_beers',
						'post_content' => $beer[3],
						'post_status' => 'publish', 
						'post_author' => 1
					);

					$postId = wp_insert_post( $my_post );
					update_post_meta($postId, 'origin', $beer[1]);

					$t = $beer[2];
					if(!empty($t)){
						$term = term_exists($t, 'ptf_beer_style');
						if($term){
							wp_set_object_terms( $postId, (int)$term['term_id'], 'ptf_beer_style', true );
						}else{
							$term = wp_insert_term( $t, 'ptf_beer_style' );
							wp_set_object_terms( $postId, (int)$term['term_id'], 'ptf_beer_style', true );
						}
					}
					
					foreach($this->breweries as $brew){
						if(strpos($beer[0], $brew) === 0){
							$term = term_exists($brew, 'ptf_breweries');
							if($term){
								wp_set_object_terms( $postId, (int)$term['term_id'], 'ptf_breweries', true );
							}
						}
					}
					
					
				}
				$this->beerCount++;
			}
		}
	}
	
	//upload beers, delete all posts in custom content type first
	protected function uploadBars(){
		$this->barCount = 0;
		$upload = new FileUpload($_SERVER['DOCUMENT_ROOT']. "/wp-content/plugins/ptf-plugin/data/", 'barscsv');

		if($upload->success){
			$handleFile = fopen($upload->handle, "r");
			$this->deletePosts('ptf_bars');
			while (($bar = fgetcsv($handleFile)) !== false) {
				if(empty($bar[0])){
					continue;
				}
				if($this->barCount > 0){
					$my_post = array(
						'post_title' => $bar[0],
						'post_type' => 'ptf_bars',
						'post_content' => "",
						'post_status' => 'publish', 
						'post_author' => 1
					);

					$postId = wp_insert_post( $my_post );

					update_field('phone', $bar[3], $postId);
					update_field('iframe_map_link', $bar[4], $postId);
					update_field('address', $bar[1], $postId);
					
					$beers = explode(';', $bar[6]);
					$beerIds = array();
					
					foreach($beers as $beer){
						$slug = sanitize_title($beer);
						
						$options = array( 
							'post_type' => 'ptf_beers', 
							'post_status' => 'publish',
							'name' => $slug
						);
						$beerPost = new WP_Query($options);
						foreach($beerPost->posts as $p){
							$beerIds[] = $p->ID;
							break;
						}
					}
					
					update_field('beers', implode(',', $beerIds), $postId);
					
					$t = $bar[2];
					if(!empty($t)){
						$term = term_exists($t, 'ptf_hoods');
						if($term){
							wp_set_object_terms( $postId, (int)$term['term_id'], 'ptf_hoods', true );
						}else{
							echo $bar[2] . "<br/>";
						}
					}
				}
				$this->barCount++;
			}
		}
	}
	
	
	
	//generic function for removing all terms within a taxonomy
	private function removeTerms($taxonomy){
		$terms = get_terms($taxonomy, array(
			'hide_empty' => 0
		));
		
		foreach ($terms as $term) {
			wp_delete_term( $term->term_id, $taxonomy );
		}
	}
	
	//generic function for deleting all posts from a custom content type
	private function deletePosts($contentType){
		$options = array( 
			'post_type' => $contentType, 
			'post_status' => 'publish',
			'posts_per_page' => -1 
		);
		$beers = new WP_Query($options);
		foreach($beers->posts as $p){
			wp_delete_post( $p->ID, true );
		}
	}
}