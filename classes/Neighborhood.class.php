<?php

class Neighborhood {
	
	//get a Neighborhood by slug, for name and description
	public static function getNeighborhood($slug){
		return get_term_by( 'slug', $slug, 'ptf_hoods');
	}
	
	//get a listing of all Neighborhoods
	public static function getAllNeighborhoods() {
		$allHoods = get_terms('ptf_hoods', array(
			'hide_empty' => 0
		));
		return $allHoods;
	}
	
	//get a Neighborhood associated to a Bar, by the Bar id
	public static function getNeighborhoodByBar($id){
		return get_the_terms( $id, 'ptf_hoods');
	}
	
}