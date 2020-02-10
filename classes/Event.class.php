<?php

class Event {

	protected $title;
	protected $description;
	protected $bar;
	protected $beers;
	protected $slug;
	protected $id;
	protected $date;
	protected $time;

	//construct Bar by ID
	public function __construct($id) {
		$post = get_post($id);
		$this->description = $post->post_content;
		$this->title = $post->post_title;
		$this->slug = $post->post_name;
		$this->id = $id;
		$bar = get_post_meta($id, 'bar', true);
		$this->date = get_post_meta($id, 'event_date', true);
		// look in $bar array (fix to deal with serialzied data)
		if(is_array($bar)) {
			$this->bar = new Bar($bar[0]);
		} else {
			$this->bar = new Bar($bar);
		}
	}

	//get Event title
	public function getTitle() {
		return $this->title;
	}

	//get Event description, if there is one
	public function getDescription() {
		return $this->description;
	}

	//get Bar object for Event
	public function getBar() {
		return $this->bar;
	}

	// Get date for Event
	public function getDate() {
		return $this->date;
	}

	public function getDateAltFormat() {
		return str_replace("/", ".", $this->date);
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
		$this->beers = Beer::getBeersByEvent($this->id);
	}

	//get Event slug or url
	public function getSlug() {
		return $this->slug;
	}

	//get Event ID
	public function getId() {
		return $this->id;
	}

	//get all Events
	public static function getAllEvents($letter = null){
		$allEvents = new stdClass();
		$allEvents->week = array();
		$allEvents->all = array();

		date_default_timezone_set('America/New_York');
		//just get the date, not including time
		$date = date('m/d/Y');
		$currentTime = strtotime($date);
		//one week from now
		$weekTime = strtotime($date . ' + 7 day');

		$query = "
			SELECT
			ID as id,
			m.meta_value as date
			FROM
			wp_posts p,
			wp_postmeta m
			WHERE
			p.post_type = 'ptf_events'
			AND p.post_status = 'publish'
			AND p.ID = m.post_id
			AND m.meta_key = 'event_date'
			ORDER BY date ASC
		";

		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $event) {
			$e = new Event($event->id);
			$title = $e->getTitle();

			$eventDate = strtotime($e->getDate());

			if($eventDate < $currentTime){
				continue;
			}

			if($eventDate < $weekTime){
				$allEvents->week[] = $e;
			}

			if(isset($letter)){
				if($letter === strtolower(substr($title, 0, 1))){
					$allEvents->all[] = $e;
				}
			}else{
				$allEvents->all[] = $e;
			}
		}

		//sort by date and title
		usort($allEvents->all, function( $a, $b ) {
			$aDate = strtotime($a->getDate());
			$bDate = strtotime($b->getDate());
			$aTitle = strtolower($a->getTitle());
			$bTitle = strtolower($b->getTitle());

			if($aDate !== $bDate){
				return ($aDate < $bDate) ? -1 : 1;
			}
			return ($aTitle < $bTitle) ? -1 : 1;
		});

		return $allEvents;
	}

	//get array of Event objects, get a list of Events that serve this beer, by Beer ID
	public static function getEventsByBeer($id){
		$eventsByBeer = array();

		date_default_timezone_set('America/New_York');
		//just get the date, not including time
		$date = date('m/d/Y');
		$currentTime = strtotime($date);

		$query = "
			SELECT
			post_id as id
			FROM
			wp_postmeta m,
			wp_posts p
			WHERE
			m.meta_key = 'beers'
			AND m.post_id = p.ID
			AND p.post_type = 'ptf_events'
			AND p.post_status = 'publish'
			AND m.meta_value LIKE '%" . $id . "%'
		";

		// AND find_in_set( $id, m.meta_value ) > 0

		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $event) {
			$e = new Event($event->id);
			$eventDate = strtotime($e->getDate());

			if($eventDate < $currentTime){
				continue;
			}

			// TODO: need to figure out why multiple associations with the event
			// are getting made. This is a quick fix to eliminate duplicates.
			if(!in_array($e, $eventsByBeer)) {
				$eventsByBeer[] = $e;
			}

		}
		return $eventsByBeer;
	}

	//get array of Event objects, get a list of Events that serve this beer, by Beer ID
	public static function getEventsByBar($id){
		$evenstByBar = array();

		date_default_timezone_set('America/New_York');
		//just get the date, not including time
		$date = date('m/d/Y');
		$currentTime = strtotime($date);

		$query = "
			SELECT
			post_id as id
			FROM
			wp_postmeta m,
			wp_posts p
			WHERE
			m.meta_key = 'bar'
			AND m.post_id = p.ID
			AND p.post_type = 'ptf_events'
			AND p.post_status = 'publish'
			AND m.meta_value LIKE '%$id%'
		";

		global $wpdb;
		$results = $wpdb->get_results($query);

		foreach($results as $event) {
			$e = new Event($event->id);
			$eventDate = strtotime($e->getDate());

			if($eventDate < $currentTime){
				continue;
			}

			$evenstByBar[] = $e;
		}

		return $evenstByBar;
	}

	// Get date on which event was last modified
	public static function getModifiedDate($id) {
		date_default_timezone_set('America/New_York');
		$eventPost = get_post($id);
		$barTime = strtotime($eventPost->post_modified . ' UTC');
		$barTime = date('m/d/y', $barTime);
		return $barTime;
	}
}
