<?php

class RSS {

	protected $url;

	//construct RSS by URL
	public function __construct($url = null, $numPosts = null) {
		$this->rss_url = $url;
		$this->number_of_posts = $numPosts;

	}

	//get RSS URL
	public function getRSSUrl() {
		return $this->rss_url;
	}

	//get RSS number of posts
	public function getRSSNumber() {
		return $this->number_of_posts;
	}

	function getFeed($rssURL = null, $rssNum = null) {
		extract($_POST);
		$rssArray = array();

		$rss = new RSS($rssURL, $rssNum);
		$rssURL = $rss->getRSSUrl();
		$rssNum = $rss->getRSSNumber();

	    $content = file_get_contents($rssURL);
	    $x = new SimpleXmlElement($content);

	    $counter = 1;

	    if ( !empty($x) ) {

		    foreach($x->channel->item as $entry) {
		    	$theTitle = str_replace('"','',$entry->title);
		    	$theTitle = str_replace('\'','&#39', $theTitle);
		    	$shortTitle = truncate_it(36, $theTitle);
		    	$theLink = $entry->link;

		    	$rssArray[] = array(
	    			'rss_full_title' => $theTitle,
	    			'rss_link' => $theLink,
	    			'rss_num' => $rssNum,
	    			'rss_short_title' => $shortTitle
	    		);

		        if ( $counter == $rssNum ) {
		        	break;
		        } else {
		        	$counter++;
		        }
		    }

				// Drop the returned data into the postmeta table
				$homepage_id = get_option('page_on_front');
				update_post_meta($homepage_id, "blog_full_title_1", $rssArray[0]['rss_full_title']);
				update_post_meta($homepage_id, 'blog_link_1', (string)$rssArray[0]['rss_link'][0]);
				update_post_meta($homepage_id, 'blog_short_title_1', $rssArray[0]['rss_short_title']);

				update_post_meta($homepage_id, "blog_full_title_2", $rssArray[1]['rss_full_title']);
				update_post_meta($homepage_id, 'blog_link_2', (string)$rssArray[1]['rss_link'][0]);
				update_post_meta($homepage_id, 'blog_short_title_2', $rssArray[1]['rss_short_title']);

				update_post_meta($homepage_id, "blog_full_title_3", $rssArray[2]['rss_full_title']);
				update_post_meta($homepage_id, 'blog_link_3', (string)$rssArray[2]['rss_link'][0]);
				update_post_meta($homepage_id, 'blog_short_title_3', $rssArray[2]['rss_short_title']);

		    return json_encode($rssArray);
	    }
	}

}


?>
