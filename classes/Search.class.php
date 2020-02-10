<?php


class Search {

	protected $searchResults;

	public function __construct() {
		$this->searchResults = new stdClass();
		$this->searchResults->beers = array();
		$this->searchResults->styles = array();
		$this->searchResults->bars = array();
		$this->searchResults->hoods = array();
	}

	protected function cmp( $a, $b ){
		if(  $a->count ==  $b->count ){
			return 0;
		}
		return ($a->count > $b->count) ? -1 : 1;
	}

	protected function addBeers($results, $weight = 1, $start = 0){

		if(count($results) > 0){

			foreach($results as $res) {

				if(isset($this->searchResults->beers[$res->ID])){
					$temp = $this->searchResults->beers[$res->ID];
					$temp->count += $weight;
				}else{
					$temp = new stdClass();
					$temp->name = $res->post_title;
					$temp->link = "/?p=$res->ID";
					$temp->count = $start;
					$this->searchResults->beers[$res->ID] = $temp;
				}

			}
		}
	}

	public function searchAll(){
		extract($_POST);

		if(!empty($searchTerm)){
			$search = mysqli::real_escape_string(trim($searchTerm));
			$find  = array(' ');
			$replace = array('%');
			$search = str_replace($find, $replace, $search);

			//search beers
			$query = "
				SELECT
				ID,
				post_title
				FROM
				pt7wp_posts
				WHERE post_type = 'ptf_beers'
				AND post_status = 'publish'
				AND ((post_title LIKE '%$search%') OR  (post_content LIKE '%$search%'))
			";

			global $wpdb;
			$results = $wpdb->get_results($query);

			$filtered_results = array();
			$Bars = new Bar();

			foreach($results as $res) {
				$numServe = $Bars->getBarCountByBeer($res->ID);
				if($numServe > 0) {
					array_push($filtered_results, $res);
				}
			}

			$this->addBeers($filtered_results, 2, 1);
			//$this->addBeers($results, 2, 1);

/*
 * following seems to be a duplicate of the above....
 */
/*
			//search beers
			$query = "
				SELECT
				ID,
				post_title
				FROM
				pt7wp_posts
				WHERE post_type = 'ptf_beers'
				AND post_status = 'publish'
				AND (post_content LIKE '%$search%')
			";

			$results = $wpdb->get_results($query);
			$this->addBeers($results);
*/

			//search styles
			$query = "
				SELECT
				x.term_id,
				t.name,
				t.slug
				FROM
				pt7wp_term_taxonomy x,
				pt7wp_terms t
				WHERE x.taxonomy = 'ptf_beer_style'
				AND description LIKE '%$search%'
				AND x.term_id = t.term_id
			";

			$results = $wpdb->get_results($query);

			if(count($results) > 0){
				foreach($results as $res) {

					if(isset($this->searchResults->styles[$res->term_id])){
						$temp = $this->searchResults->styles[$res->term_id];
						$temp->count += 1;

					}else{
						$temp = new stdClass();
						$temp->name = $res->name;
						$temp->link = $res->slug;
						$temp->count = 0;
						$this->searchResults->styles[$res->term_id] = $temp;
					}
				}
			}

			$query = "
				SELECT
				x.term_id,
				t.name,
				t.slug,
				x.term_taxonomy_id
				FROM
				pt7wp_term_taxonomy x,
				pt7wp_terms t
				WHERE x.taxonomy = 'ptf_beer_style'
				AND x.term_id = t.term_id
				AND t.name LIKE '%$search%'
			";

			$results = $wpdb->get_results($query);

			if(count($results) > 0){
				foreach($results as $res) {

					if(isset($this->searchResults->styles[$res->term_id])){
						$temp = $this->searchResults->styles[$res->term_id];
						$temp->count += 2; //add weight to direct style match

					}else{
						$temp = new stdClass();
						$temp->name = $res->name;
						$temp->link = $res->slug;
						$temp->count = 1; //add weight to direct style match
						$this->searchResults->styles[$res->term_id] = $temp;
					}

					$query = "
						SELECT
						object_id
						FROM
						pt7wp_term_relationships
						WHERE term_taxonomy_id = '$res->term_taxonomy_id'
					";

					$resultsBeer = $wpdb->get_results($query);
					$this->addBeers($resultsBeer);
				}
			}

			//search bars
			$query = "
				SELECT
				ID,
				post_title
				FROM
				pt7wp_posts
				WHERE post_type = 'ptf_bars'
				AND post_status = 'publish'
				AND (post_title LIKE '%$search%' OR post_content LIKE '%$search%')
			";

			$results = $wpdb->get_results($query);

			if(count($results) > 0){
				foreach($results as $res) {

					if(isset($this->searchResults->bars[$res->ID])){
						$temp = $this->searchResults->bars[$res->ID];
						$temp->count += 1;
					}else{
						$temp = new stdClass();
						$temp->name = $res->post_title;
						$temp->link = "/?p=$res->ID";
						$temp->count = 0;
						$this->searchResults->bars[$res->ID] = $temp;
					}
				}
			}

			$query = "
				SELECT
				x.term_id,
				t.name,
				t.slug
				FROM
				pt7wp_term_taxonomy x,
				pt7wp_terms t
				WHERE x.taxonomy = 'ptf_hoods'
				AND x.term_id = t.term_id
				AND t.name LIKE '%$search%'
			";

			$results = $wpdb->get_results($query);

			if(count($results) > 0){
				foreach($results as $res) {

					if(isset($this->searchResults->hoods[$res->term_id])){
						$temp = $this->searchResults->hoods[$res->term_id];
						$temp->count += 1;
					}else{
						$temp = new stdClass();
						$temp->name = $res->name;
						$temp->link = $res->slug;
						$temp->count = 0;
						$this->searchResults->hoods[$res->term_id] = $temp;
					}
				}
			}


			$query = "
				SELECT
				meta_value
				FROM
				pt7wp_postmeta
				WHERE meta_key = 'beers'
			";


			$allRelatedBeers = array();

			$results = $wpdb->get_results($query);

			if(count($results) > 0){
				foreach($results as $res) {
					$temp = explode(',', $res->meta_value);
					$allRelatedBeers = array_merge($allRelatedBeers, $temp);
				}
			}
			$tappedBeers = array_unique($allRelatedBeers);

			foreach($this->searchResults->beers as $beerId => $beer){
				if(!in_array($beerId, $tappedBeers)){
					unset($this->searchResults->beers[$beerId]);
				}
			}

			usort($this->searchResults->beers, array('Search', 'cmp'));
			usort($this->searchResults->styles, array('Search', 'cmp'));
			usort($this->searchResults->bars, array('Search', 'cmp'));
			usort($this->searchResults->hoods, array('Search', 'cmp'));

			array_splice($this->searchResults->beers, 20);
			array_splice($this->searchResults->styles, 5);
			array_splice($this->searchResults->bars, 5);
			array_splice($this->searchResults->hoods, 5);

		}

		header("Content-type: text/json");
		return json_encode($this->searchResults);
	}

}



?>
