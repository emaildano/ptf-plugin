<?php

class Style {
	
	//get a term by slug for name and description
	public static function getStyle($slug){
		return get_term_by( 'slug', $slug, 'ptf_beer_style');
	}
	
	//get a listing of all Beer Styles
	public static function getAllStyles(){	
		$allStyles = get_terms('ptf_beer_style', array(
			'hide_empty' => 0
		));
		return $allStyles;
	}
	
	//get a Style associated to a Beer, by the Beer id
	public static function getStyleByBeer($id){
		return get_the_terms( $id, 'ptf_beer_style');
	}
		
}


