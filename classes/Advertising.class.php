<?php

class Advertising {

	protected $id;

	//construct Ads by ID
	public function __construct($id = null) {
		$this->advertisement_url = get_post_meta($id, 'advertisement_url', true);
		$this->advertisement_image = get_post_meta($id, 'advertisement_image', true);
		$this->ad_size = get_post_meta($id, 'ad_size', true);
		$this->ad_placement_overrides = wp_get_post_terms($id, 'ptf_ad_overrides');
		$this->id = $id;
		$this->ad_title = get_the_title($id);
	}

	//get Ad destination URL
	public function getAdUrl() {
		return $this->advertisement_url;
	}

	//get Ad image src URL
	public function getAdImgID() {
		return $this->advertisement_image;
	}

	//get Ad size
	public function getAdSize() {
		return $this->ad_size;
	}

	//get Ad URL
	public function getAdOverrides() {
		return $this->ad_placement_overrides;
	}

	//get Ad ID
	public function getAdID() {
		return $this->id;
	}

	//get Ad Title
	public function getAdTitle() {
		return $this->ad_title;
	}

	// Use this to nab all ads in a given Override taxonomy
	public function getSomeAds($overrideSlug) {
		$args = array(
			'post_type' => 'ptf_ads',
			'tax_query' => array(
				array(
					'taxonomy' => 'ptf_ad_overrides',
					'field' => 'slug',
					'terms' => $overrideSlug
				)
			)
		);

		$findSomeAds = new WP_Query($args);

		if ( !empty($findSomeAds->posts) ) {
			$someAds = $findSomeAds->posts;
			return $someAds;
		} else {
			// No overriden ads
			return false;
		}
	}

	// This takes a collection of ads, randomizes the order, and returns a specified number of ads
	public function shuffleAdsAndGimmeSome($someAds, $howMany) {
		shuffle($someAds);
		$adCount = 0;
		$shuffledAds = array();
		foreach ( $someAds as $ads ) {
			$adCount++;
			$shuffledAds[] = $ads;
			if ( $adCount == $howMany ) {
				break;
			}
		}
		return $shuffledAds;
	}

	// Once you've finally got a collection of ads you want to use, this filters the ads by size and spits 'em out as json
	public function filterAndEncodeAds($someAdsArray, $requestedAdSize) {
		$adClass = new Advertising();

		if ($someAdsArray) {
			foreach ( $someAdsArray as $ads ) {
				$checkSize = new Advertising($ads->ID);
				$thisAdSize = $checkSize->getAdSize();
				if ( $thisAdSize == $requestedAdSize ) {
					$yourAds[] = $ads;
				}
			}
			if ( !empty($yourAds) ) {
				$finalAds = $adClass->shuffleAdsAndGimmeSome($yourAds, 1);
				foreach ( $finalAds as $anAd ) {

					$finalOutput = new Advertising($anAd->ID);
					$imgData = wp_get_attachment_image_src($finalOutput->getAdImgID(), 'full');
					$imgAlt = get_post_meta($finalOutput->getAdImgID(), '_wp_attachment_image_alt', true);

					$arrayToJson['ad_img'] = $imgData[0];
					$arrayToJson['ad_width'] = $imgData[1];
					$arrayToJson['ad_height'] = $imgData[2];
					$arrayToJson['ad_alt'] = $imgAlt;
					$arrayToJson['ad_url'] = $finalOutput->getAdUrl();
					$arrayToJson['ad_title'] = $finalOutput->getAdTitle();

					return json_encode($arrayToJson);

				}
			} else {
				return false;
			}
		}
	}

	/*	This one does all the work... the ajax call asks for this function,
	 *  and it figures out if there's any overrides, and if not, what ads are selected on the page in question,
	 *  and then returns the json-ified ads to the ajax call to then be used in jquery tmpls
	 */
	public function showAdvertisement($requestedAdSize = null, $pageType = null, $theID = null) {

		extract($_POST);
		global $post;

		if ( !empty($adSize) ) {
			$requestedAdSize = $adSize;
		}
		if ( !empty($whichPage) ) {
			$pageType = $whichPage;
		}
		if ( !empty($currID) ) {
			$theID = $currID;
		}

		$adClass = new Advertising();

		$globalAds = $adClass->getSomeAds('global-override');
		$barAds = $adClass->getSomeAds('bar-page-override');
		$beerAds = $adClass->getSomeAds('beer-page-override');
		$styleAds = $adClass->getSomeAds('style-page-override');
		$hoodAds = $adClass->getSomeAds('hood-page-override');
		$eventAds = $adClass->getSomeAds('event-page-override');

		// Get globally overridden ads above all else
		if ( $globalAds ) {
			$encodedAds = $adClass->filterAndEncodeAds($globalAds, $requestedAdSize);
			if ( $encodedAds ) {
				return $encodedAds;
			}
		}

		/**
		* Look to see first if there's a specific ad selected for the page in question;
		* if there is, return it.
		*/
		if ( $requestedAdSize == 'leaderboard' ) {
			$selectedAds = get_field('select_a_leaderboard_ad', $theID);
		} else {
			$selectedAds = get_field('select_a_single_ad', $theID);
		}

		/* This is were the 'Advertise on PTF' ad is getting returned, even though
		   the logic still makes it to the 'tax-ptf_hoods' block. Below hotfix
			 works, but should be addressed more structurately going forward.
		*/

		if ( $selectedAds && $pageType != 'tax-ptf_hoods') {
			$encodedAds = $adClass->filterAndEncodeAds($selectedAds, $requestedAdSize);
			if ( $encodedAds ) {
				return $encodedAds;
			}
		}

		// If it's a single bar page, get any Bar Page Override ads
		if ( $pageType == 'single-ptf_bars' ) {
			if ( $barAds ) {
				$encodedAds = $adClass->filterAndEncodeAds($barAds, $requestedAdSize);
				if ( $encodedAds ) {
					return $encodedAds;
				}
			}
		}

		// If it's a single event page, get any Bar Page Override ads
		if ( $pageType == 'single-ptf_events' ) {
			if ( $barAds ) {
				$encodedAds = $adClass->filterAndEncodeAds($eventAds, $requestedAdSize);
				if ( $encodedAds ) {
					return $encodedAds;
				}
			}
		}

		// If it's a single beer page, get any Beer Page Override ads
		if ( $pageType == 'single-ptf_beers' ) {
			if ( $beerAds ) {
				$encodedAds = $adClass->filterAndEncodeAds($beerAds, $requestedAdSize);
				if ( $encodedAds ) {
					return $encodedAds;
				}
			}
		}

		// If it's a style page, get any Style Page Override ads
		if ( $pageType == 'tax-ptf_beer_style' ) {
			if ( $styleAds ) {
				$encodedAds = $adClass->filterAndEncodeAds($styleAds, $requestedAdSize);
				if ( $encodedAds ) {
					return $encodedAds;
				}
			}
		}

		// If it's a hood page, get any Hood Page Override ads
		if ( $pageType == 'tax-ptf_hoods' ) {
			if ( $hoodAds ) {
				$encodedAds = $adClass->filterAndEncodeAds($hoodAds, $requestedAdSize);
				if ( $encodedAds ) {
					return $encodedAds;
				}
			}
		}

		// If none of the above, get whatever ads are attached directly to the page
		if ( $requestedAdSize == 'leaderboard' ) {

			$selectedAds = get_field('select_a_leaderboard_ad', $theID);
		} else {
			$selectedAds = get_field('select_a_single_ad', $theID);
		}

		if ( $selectedAds ) {
			$encodedAds = $adClass->filterAndEncodeAds($selectedAds, $requestedAdSize);
			if ( $encodedAds ) {
				return $encodedAds;
			}
		} else {
			return false;
		}
	}

	/* This function is called via ajax to get 3 square ads,
	 * for use at the bottom of grid pages.
	 * It is hideous and I am ashamed of myself.
	 */
	public function show3Ads($theID = null) {
		extract($_POST);
		global $post;

		$threeAdArray = array();

		if ( !empty($currID) ) {
			$theID = $currID;
		}

		$adClass = new Advertising();
		$globalAds = $adClass->getSomeAds('global-override');

		$selectedAds = get_field('select_an_ad', $theID);

		if ( $selectedAds ) {
			foreach ( $selectedAds as $ads ) {
				$checkSize = new Advertising($ads->ID);
				$thisAdSize = $checkSize->getAdSize();
				if ( $thisAdSize == 'square' ) {
					$yourAds[] = $ads;
				}
			}
		}

		if ( !empty($yourAds) ) {
			$finalAds = $adClass->shuffleAdsAndGimmeSome($yourAds, 3);
		}

		// Get globally overridden ads above all else
		if ( $globalAds ) {
			$limit3 = 0;
			foreach ( $globalAds as $ads ) {
				$limit3++;
				if ($limit3 == 4) {
					break;
				}
				$checkSize = new Advertising($ads->ID);
				$thisAdSize = $checkSize->getAdSize();
				if ( $thisAdSize == 'square' ) {
					if ( !in_array($ads, $finalAds) ) {
						array_shift($finalAds);
						$finalAds[] = $ads;
					}
				}
			}
		}

		if ( !empty($finalAds) ) {
			foreach ( $finalAds as $anAd ) {

				$finalOutput = new Advertising($anAd->ID);
				$imgData = wp_get_attachment_image_src($finalOutput->getAdImgID(), 'full');
				$imgAlt = get_post_meta($finalOutput->getAdImgID(), '_wp_attachment_image_alt', true);

				$arrayToJson = array();

				$arrayToJson['ad_img'] = $imgData[0];
				$arrayToJson['ad_width'] = $imgData[1];
				$arrayToJson['ad_height'] = $imgData[2];
				$arrayToJson['ad_alt'] = $imgAlt;
				$arrayToJson['ad_url'] = $finalOutput->getAdUrl();
				$arrayToJson['ad_title'] = $finalOutput->getAdTitle();

				$threeAdArray[] = $arrayToJson;

			}

			return json_encode($threeAdArray);
		}

	}

}

?>
