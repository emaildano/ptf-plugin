<?php

class Beer {

	protected $id;
	protected $title;
	protected $description;
	protected $origin;
	protected $slug;
	protected $style;
	protected $brewery;

	//construct, set base properties based on set ID
	public function __construct($id = null) {
		if(isset($id)){
			$this->origin = get_post_meta($id, 'origin', true);
			$post = get_post($id);
			$this->description = $post->post_content;
			$this->title = $post->post_title;
			$this->slug = $post->post_name;
			$this->id = $id;
		}
	}

	//get Beer id
	public function getId() {
		return $this->id;
	}

	//get Beer title
	public function getTitle() {
		return trim($this->title);
	}

	//get Beer description
	public function getDescription() {
		return $this->description;
	}

	//get origin or city, state location of Beer
	public function getOrigin() {
		return $this->origin;
	}

	//get array of Style objects of styles associated with this Beer
	public function getStyle() {
		if(!isset($this->style)){
			$this->setStyle();
		}
		return $this->style;
	}

	protected function setStyle(){
		$this->style = Style::getStyleByBeer($this->id);
	}

	//get Brewery object associated with Beer
	public function getBrewery() {
		if(!isset($this->brewery)){
			$this->setBrewery();
		}
		return $this->brewery;
	}

	//set Brewery object, returns array of Terms
	protected function setBrewery(){
		$this->brewery = Brewery::getBreweryByBeer($this->id);
	}

	//get slug or url of beer
	public function getSlug() {
		return $this->slug;
	}

	//get JSON package of all Beers and Styles
	public function getSearchDataPackage(){
		header("Content-type: text/json");

		$carrier = new stdClass();
		$carrier->beers = array();
		$carrier->styles = array();

		$query = "
		SELECT
		post_title as title,
		post_name as slug,
		ID as id
		FROM
		pt7wp_posts
		WHERE
		post_type = 'ptf_beers'
		AND post_status = 'publish'
		";

		/**
		 * Gets every value from every entry of the "beers" custom field,
		 * then breaks it down to a single array of beers that have been
		 * related to a bar (i.e. "tapped").
		 * This filters the beers feteched to include only what's available on tap.
		 */
		global $wpdb;
		$getRelatedBeers = $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'beers'" );
		$allRelatedBeers = array();
		foreach ( $getRelatedBeers as $aRelBeer ) {
			$seperateBeers = explode(',', $aRelBeer);
			foreach ($seperateBeers as $i) {
				$allRelatedBeers[] = $i;
			}
		}
		$tappedBeers = array_unique($allRelatedBeers);

		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $beer) {
			if ( in_array($beer->id, $tappedBeers) ){
				$carrier->beers[] = $beer;
			}
		}

		foreach(Style::getAllStyles() as $s){
			$temp = new stdClass();
			$temp->title = $s->name;
			$temp->slug = $s->slug;
			$carrier->styles[] = $temp;
		}

		return json_encode($carrier);
	}

	//get array of Beer objects by Bar ID
	public static function getBeersByBar($id){
		$beers = get_post_meta($id, 'beers', true);

		// deal with situation where serialized data returns as an array vs. string
		if(!is_array($beers)){
			$beers = explode(',', $beers);
		}

		$beerArray = array();
		foreach($beers as $beer){
			//if(Bar::getBarCountByBeer($beer) > 0){
				$beerArray[] = new Beer($beer);
			//}
		}
		return $beerArray;
	}

	//get array of Beer objects by Event ID
	public static function getBeersByEvent($id){
		$beers = get_post_meta($id, 'beers', true);
		$beerArray = array();

		if(!is_array($beers)){
			$beers = explode(',', $beers);
		}

		foreach($beers as $beer){
			$beerArray[] = new Beer($beer);
		}
		return $beerArray;
	}

	//get Beers by Style, returns an array of Beer objects
	public static function getBeersByStyle($slug){
		$beersByStyle = array();

		$options = array(
			'post_type' => 'ptf_beers',
			'orderby' => 'title',
			'order' => 'ASC',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'ptf_beer_style',
					'field' => 'slug',
					'terms' => $slug
				)
			)
		);

		$beers = new WP_Query($options);

		foreach($beers->posts as $p){
			if(Bar::getBarCountByBeer($p->ID) > 0){
				$beersByStyle[] = new Beer($p->ID);
			}
		}

		return $beersByStyle;
	}

	//get Beers by Brewery name
	public static function getBeersByBrewery($slug){
		$beersByBrewery = array();

		$query = "
			SELECT
			p.ID,
			p.post_title
			FROM
			pt7wp_posts p,
			pt7wp_term_taxonomy t,
			pt7wp_terms term,
			pt7wp_term_relationships r
			WHERE post_type = 'ptf_beers'
			AND term.slug = '$slug'
			AND t.taxonomy = 'ptf_breweries'
			AND t.term_id = term.term_id
			AND r.term_taxonomy_id = t.term_taxonomy_id
			AND r.object_id = p.ID
			AND p.post_status = 'publish'
			ORDER BY p.post_title ASC
		";


		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $beer) {
			if(Bar::getBarCountByBeer($beer->ID) > 0){
				$beersByBrewery[] = new Beer($beer->ID);
			}
		}

		return $beersByBrewery;
	}

	// get featured Beers and Breweries; return the the latest-modified item
	public static function getFeaturedBeerOrBrewery($count = 1){

		$beerFeatDate;
		$breweryFeatDate;

		$payloadObj = new stdClass();

		$featuredBeers = array();
		$featuredBreweries = array();

		$optionsBeers = array(
			'post_type' => 'ptf_beers',
			'orderby' => 'modified',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'ptf_beer_featured',
					'field' => 'slug',
					'terms' => array('homepage-feature')
				)
			)
		);

		$optionsBreweries = array(
			'post_type' => 'ptf_breweries_meta',
			'orderby' => 'modified',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'ptf_brewery_featured',
					'field' => 'slug',
					'terms' => array('homepage-feature')
				)
			)
		);

		$beers = new WP_Query($optionsBeers);
		$breweries = new WP_Query($optionsBreweries);

		foreach($beers->posts as $p){

			if(!isset($beerFeatDate) || $p->post_modified > $beerFeatDate){
				$beerFeatDate = $p->post_modified;
			}

			$featuredBeers[] = new Beer($p->ID);
		}

		foreach($breweries->posts as $p){

			if(!isset($breweryFeatDate) || $p->post_modified > $breweryFeatDate){
				$breweryFeatDate = $p->post_modified;
			}

			$featuredBreweries[] = new Beer($p->ID);
		}

		if(isset($beerFeatDate) && !isset($breweryFeatDate)) {
			$payloadObj->type = 'beer';
			$payloadObj->payload = $featuredBeers;
		} elseif (!isset($beerFeatDate) && isset($breweryFeatDate)) {
			$payloadObj->type = 'brewery';
			$payloadObj->payload = $featuredBreweries;
		} elseif ($beerFeatDate > $breweryFeatDate) {
			$payloadObj->type = 'beer';
			$payloadObj->payload = $featuredBeers;
		} else {
			$payloadObj->type = 'brewery';
			$payloadObj->payload = $featuredBreweries;
		}

		return $payloadObj;
	}

	// Get date on which beer was last modified
	public static function getModifiedDate($id) {
		$beerPost = get_post($id);
		$beerTime = strtotime($beerPost->post_modified);
		$beerTime = date('m/d/y', $beerTime);
		return $beerTime;
	}

	//get an array of Bars based on beginning letter
	public static function getBeersByLetter($letter){
		$beerByLetter = array();

		$query = "
		SELECT
		post_title as title,
		post_name as slug,
		ID as id
		FROM
		pt7wp_posts
		WHERE
		post_type = 'ptf_beers'
		AND post_status = 'publish'
		AND post_title LIKE '$letter%'
		";

		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $beer) {
			if(Bar::getBarCountByBeer($beer->id) > 0){
				$beerByLetter[] = new Beer($beer->id);
			}
		}

		return $beerByLetter;
	}

	//get all Beers
	public static function getAllBeers(){
		$allBeers = array();

		$options = array(
			'post_type' => 'ptf_beers',
			'orderby' => 'title',
			'order' => 'ASC',
			'post_status' => 'publish',
			'posts_per_page' => -1
		);

		$beers = new WP_Query($options);

		foreach($beers->posts as $p){
			if(Bar::getBarCountByBeer($p->ID) > 0){
				$allBeers[] = new Beer($p->ID);
			}
		}

		return $allBeers;
	}

	/*public static function isBeerOnTap($beerID) {

		$options = array(
			'post_type' => 'ptf_bars',
			'post_status' => 'publish',
			'posts_per_page' => -1
		);

		$allBars = new WP_Query($options);

		foreach($allBars->posts as $aBar){
			$beersOnTap = get_field('beers', $aBar->ID);
			foreach ($beersOnTap as $tappedBeer) {
				if ( $tappedBeer->ID == $beerID) {
					return $tappedBeer->ID;
				} else {
					return false;
				}
			}
		}

	}	*/
}
