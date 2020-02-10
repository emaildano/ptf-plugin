<?php

class Brewery {

	//get a Brewery by slug, for name and description
	public static function getBrewery($slug){
		return get_term_by( 'slug', $slug, 'ptf_breweries');
	}

	//get a listing of all Beer Breweries
	public static function getAllBreweries() {
		$allBreweries = get_terms('ptf_breweries', array(
			'hide_empty' => 0
		));

		$breweries = array();

		foreach($allBreweries as $brew){
			if(Bar::getBarCountByBrewery($brew->slug) > 0){
				$breweries[] = $brew;
			}
		}
		return $breweries;
	}

	//get a Brewery associated to a Beer, by the Beer id
	public static function getBreweryByBeer($id){
		return get_the_terms( $id, 'ptf_breweries');
	}

	public static function getBreweriesByLetter($letter){

		if(strtolower($letter) === 'a'){
			$alphaNumeric = array('1', '2', '3', '4', '5', '6', '7', '8', '9', 'a');
			foreach($alphaNumeric as $alpha){

				$temp = get_terms('ptf_breweries', array(
					'hide_empty' => 0,
					'name__like' => $alpha
				));

				if(is_array($temp) && sizeof($temp) > 0 ){
					if(is_array($master)){
						$master = array_merge($master, $temp);
					}else{
						$master = $temp;
					}
				}
			}
		}else{
			$master = get_terms('ptf_breweries', array(
				'hide_empty' => 0,
				'search' => $letter
			));
		}

		$breweries = array();

		foreach($master as $brew){
			// see if first character of bar name matches search letter
			if(substr(strtolower($brew->name), 0 , 1) == $letter){
			  if(Bar::getBarCountByBrewery($brew->slug) > 0){
					$breweries[] = $brew;
				}
			}
		}
		return $breweries;
	}

}
