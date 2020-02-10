<?php

class Bar {

	protected $title;
	protected $phone;
	protected $address;
	protected $description;
	protected $mapLink;
	protected $embedLink;
	protected $beers;
	protected $neighborhood;
	protected $slug;
	protected $siteURL;
	protected $id;

	//construct Bar by ID
	public function __construct($id=null) {
		if(isset($id)){
			$this->phone = get_post_meta($id, 'phone', true);
			$this->mapLink = get_post_meta($id, 'iframe_map_link', true);
			$this->embedLink = $this->mapLink . '&output=embed';
			$this->address = get_post_meta($id, 'address', true);
			$this->siteURL = get_post_meta($id, 'bar_website_url', true);

			if($post = get_post($id)) {
				$this->description = $post->post_content;
				$this->title = $post->post_title;
				$this->slug = $post->post_name;
			}

			$this->id = $id;
		}
	}

	//get Bar title
	public function getTitle() {
		return $this->title;
	}

	//get Bar's Website URL
	public function getSiteURL() {
		return $this->siteURL;
	}

	//get Bar phone number
	public function getPhone() {
		return $this->phone;
	}

	//get Bar address
	public function getAddress() {
		return $this->address;
	}

	//get Bar description, if there is one
	public function getDescription() {
		return $this->description;
	}

	//get Bar map link, use with target _blank
	public function getMapLink() {
		return $this->mapLink;
	}

	//get Bar embed link, use with iframe or other embed method
	public function getEmbedLink() {
		return $this->embedLink;
	}

	//get list of Beers, array of Beer objects
	public function getBeers() {
		if(!isset($this->beers)){
			$this->setBeers();
		}
		return $this->beers;
	}

	//set Beers array
	protected function setBeers(){
		$this->beers = Beer::getBeersByBar($this->id);
	}

	//get Bar Neighborhood
	public function getNeighborhood() {
		if(!isset($this->neighborhood)){
			$this->setNeighborhood();
		}
		return $this->neighborhood;
	}

	//set Bar Neighborhood
	protected function setNeighborhood(){
		$this->neighborhood = Neighborhood::getNeighborhoodByBar($this->id);
	}

	//get Bar slug or url
	public function getSlug() {
		return $this->slug;
	}

	//get Bar ID
	public function getId() {
		return $this->id;
	}

	//get array of featured Bars with optional count
	public static function getFeaturedBar(){
		$featuredBars = array();

		$options = array(
			'post_type' => 'ptf_bars',
			'orderby' => 'date',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'ptf_bar_featured',
					'field' => 'slug',
					'terms' => 'feature'
				)
			)
		);

		$bars = new WP_Query($options);

		foreach($bars->posts as $p){
			$featuredBars[] = new Bar($p->ID);
		}

		return $featuredBars;
	}

	//get array of featured Bars with optional count
	public static function getHomeFeaturedBar($count = 1){
		$featuredBars = array();

		$options = array(
			'post_type' => 'ptf_bars',
			'orderby' => 'date',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'posts_per_page' => $count,
			'tax_query' => array(
				array(
					'taxonomy' => 'ptf_bar_featured',
					'field' => 'slug',
					'terms' => 'home-feature'
				)
			)
		);

		$bars = new WP_Query($options);

		foreach($bars->posts as $p){
			$featuredBars[] = new Bar($p->ID);
		}

		return $featuredBars;
	}

	//get an array of Bars based on beginning letter
	public static function getBarsByLetter($letter, $sorted = null){
		$barByLetter = array();

		if ($sorted) {

			$query = "
			SELECT
			post_title as title,
			post_name as slug,
			ID as id,
			post_modified as updated
			FROM
			pt7wp_posts
			WHERE
			post_type = 'ptf_bars'
			AND post_status = 'publish'
			AND post_title LIKE '$letter%'
			ORDER BY
			post_modified DESC
			";

		} else {

			$query = "
			SELECT
			post_title as title,
			post_name as slug,
			ID as id
			FROM
			pt7wp_posts
			WHERE
			post_type = 'ptf_bars'
			AND post_status = 'publish'
			AND post_title LIKE '$letter%'
			ORDER BY
			post_name
			";

		}

		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $bar) {
			$barByLetter[] = new Bar($bar->id);
		}

		return $barByLetter;
	}

	//get Bars by Neighborhood slug
	public static function getBarsByNeighborhood($neighborhood){
		$barsByhood = array();

		$options = array(
			'post_type' => 'ptf_bars',
			'orderby' => 'title',
			'order' => 'ASC',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'ptf_hoods',
					'field' => 'slug',
					'terms' => $neighborhood
				)
			)
		);

		$bars = new WP_Query($options);

		foreach($bars->posts as $p){
			$barsByhood[] = new Bar($p->ID);
		}

		return $barsByhood;
	}

	//get array of Bar objects, get a list of bars that serve this beer, by Beer ID
	public static function getBarsByBeer($id){
		$barsByBeer = array();

		$query = "
			SELECT
			post_id as id
			FROM
			pt7wp_postmeta m,
			pt7wp_posts p
			WHERE
			m.meta_key = 'beers'
			AND m.post_id = p.ID
			AND p.post_type = 'ptf_bars'
			AND p.post_status = 'publish'
			AND m.meta_value LIKE '%" . $id . "%'
		";

		// AND find_in_set( $id, m.meta_value ) > 0

		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $bar) {
			$barsByBeer[] = new Bar($bar->id);
		}

		return $barsByBeer;
	}

	public static function getBarCountByBeer($id){

		//includes code for checking events time
		$barArray = array();

		date_default_timezone_set('America/New_York');
		//just get the date, not including time
		$date = date('m/d/Y');
		$currentTime = strtotime($date);

		$query = "
			SELECT
			m.post_id as id,
			p.post_type
			FROM
			pt7wp_posts p,
			pt7wp_postmeta m
			WHERE
			p.ID = m.post_id
			AND m.meta_key = 'beers'
			AND m.meta_value LIKE '%" . $id . "%'
		";

		// AND find_in_set( $id, m.meta_value ) > 0

		global $wpdb;
 	 	$results = $wpdb->get_results($query);

		// handles case where serialized data returns as an array vs. string
		if(!is_array($results)){
			$beers = explode(',', $results);
		}

		foreach($results as $bar) {
			if($bar->post_type === 'ptf_events'){
				$event = new Event($bar->id);
				$eventDate = strtotime($event->getDate());

				if($eventDate < $currentTime){
					continue;
				}
			}
			if(!isset($barArray[$bar->id])){
				$barArray[$bar->id] = $bar->id;
			}
		}

		return sizeof($barArray);
	}

	public static function getBarCountByBrewery($slug){
		$barArray = array();
		$beers = Beer::getBeersByBrewery($slug);
		foreach($beers as $beer){
			$id = $beer->getId();
			$query = "
				SELECT
				post_id as id
				FROM
				pt7wp_postmeta as m
				WHERE
				meta_key = 'beers'
				AND m.meta_value LIKE '%" . $id . "%'
			";

			// AND find_in_set( $id, meta_value ) > 0

			global $wpdb;
			$results = $wpdb->get_results($query);

			foreach($results as $bar) {
				if(!isset($barArray[$bar->id])){
					$barArray[$bar->id] = $bar->id;
				}
			}

		}
		return sizeof($barArray);
	}

	//get all Bars
	public static function getAllBars(){
		$allBars = array();

		$options = array(
			'post_type' => 'ptf_bars',
			'orderby' => 'title',
			'order' => 'ASC',
			'post_status' => 'publish',
			'posts_per_page' => -1
		);

		$bars = new WP_Query($options);

		foreach($bars->posts as $p){
			$allBars[] = new Bar($p->ID);
		}

		return $allBars;
	}

	// Get date on which bar was last modified
	public static function getModifiedDate($id) {
		date_default_timezone_set('America/New_York');
		$barPost = get_post($id);
		$barTime = strtotime($barPost->post_modified . ' UTC');
		$barTime = date('m/d/y', $barTime);
		return $barTime;
	}
}
