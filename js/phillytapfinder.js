jQuery(document).ready(function($) {
  function SearchPTF() {
    this.tapList = $("#search-tap-list");
    this.styleList = $("#search-style-list");
    this.barList = $("#search-bar-list");
    this.hoodList = $("#search-hood-list");
    this.noResults = $("#no-results");

    this.containerDiv = $(".search-auto-fills");
    this.searchBox = $("#searchptf");
    this.loader = $(".loader");
    this.xhr;
    this.data;
  }

  SearchPTF.prototype.getData = function(searchValue) {
    var that = this;
    if (this.xhr) {
      this.xhr.abort();
    }
    this.xhr = $.ajax({
      type: "POST",
      url: "/wp-content/plugins/ptf-plugin/",
      data:
        "class=Search" + "&process=searchAll" + "&searchTerm=" + searchValue,
      success: function(data) {
        that.data = data;
        that.showResults();
      }
    });
  };

  SearchPTF.prototype.showResults = function() {
    var that = this;
    this.loader.hide(0);

    $("#search-tap-list li:not(:first-child)").remove();
    if (that.data.beers.length > 0) {
      $("#search-tap-tmpl")
        .tmpl(that.data.beers)
        .insertAfter("#search-tap-list .auto-list-title");
      this.tapList.show();
    } else {
      this.tapList.hide(0);
    }

    $("#search-style-list li:not(:first-child)").remove();
    if (that.data.styles.length > 0) {
      $("#search-style-tmpl")
        .tmpl(that.data.styles)
        .insertAfter("#search-style-list .auto-list-title");
      this.styleList.show();
    } else {
      this.styleList.hide(0);
    }

    $("#search-bar-list li:not(:first-child)").remove();
    if (that.data.bars.length > 0) {
      $("#search-bar-tmpl")
        .tmpl(that.data.bars)
        .insertAfter("#search-bar-list .auto-list-title");
      this.barList.show();
    } else {
      this.barList.hide(0);
    }

    $("#search-hood-list li:not(:first-child)").remove();
    if (that.data.hoods.length > 0) {
      $("#search-hood-tmpl")
        .tmpl(that.data.hoods)
        .insertAfter("#search-hood-list .auto-list-title");
      this.hoodList.show();
    } else {
      this.hoodList.hide(0);
    }

    if (
      that.data.hoods.length == 0 &&
      that.data.bars.length == 0 &&
      that.data.styles.length == 0 &&
      that.data.beers.length == 0
    ) {
      $("#search-hood-tmpl")
        .tmpl(that.data.hoods)
        .insertAfter("#search-hood-list .auto-list-title");
      this.noResults.show();
    } else {
      this.noResults.hide(0);
    }
  };

  SearchPTF.prototype.hideResults = function() {
    this.containerDiv.hide(0);
  };

  SearchPTF.prototype.evalValue = function() {
    var that = this;
    var searchValue = $.trim(that.searchBox.val());
    if (searchValue.length < 3) {
      this.hideResults();
    } else {
      this.containerDiv.show(0);
      this.loader.show(0).fadeTo(0, 0.6);
      this.getData(searchValue);
    }
  };

  /* End "Search" stuff and start "Ad" stuff */

  // function PTFAds() {
  //   this.data;
  //   this.xhr;
  // }

  // PTFAds.prototype.getLeaderboardAd = function() {
  //   var that = this;
  //   if (this.xhr) {
  //     this.xhr.abort();
  //   }
  //   this.xhr = $.ajax({
  //     type: "POST",
  //     url: "/wp-content/plugins/ptf-plugin/",
  //     data:
  //       "class=Advertising" +
  //       "&process=showAdvertisement" +
  //       "&adSize=leaderboard",
  //     success: function(data) {
  //       console.log(data);
  //       that.data = jQuery.parseJSON(data);
  //       that.showLeaderboard();
  //     }
  //   });
  // };

  // Displays a square ad.

  // PTFAds.prototype.getSingleSquareAd = function(whichPage, currID) {
  //   var that = this;
  //   if (this.xhr) {
  //     this.xhr.abort();
  //   }

  //   this.xhr = $.ajax({
  //     type: "POST",
  //     url: "/wp-content/plugins/ptf-plugin/",
  //     data:
  //       "class=Advertising" +
  //       "&process=showAdvertisement" +
  //       "&adSize=square" +
  //       "&whichPage=" +
  //       whichPage +
  //       "&currID=" +
  //       currID,
  //     success: function(data) {
  //       that.data = jQuery.parseJSON(data);
  //       that.showSingleSquareAd();
  //     }
  //   });
  // };

  // PTFAds.prototype.get3Ads = function(currID) {
  //   var that = this;
  //   if (this.xhr) {
  //     this.xhr.abort();
  //   }
  //   this.xhr = $.ajax({
  //     type: "POST",
  //     url: "/wp-content/plugins/ptf-plugin/",
  //     data: "class=Advertising" + "&process=show3Ads" + "&currID=" + currID,
  //     success: function(data) {
  //       that.data = jQuery.parseJSON(data);
  //       that.show3Ads();
  //     }
  //   });
  // };

  // PTFAds.prototype.showLeaderboard = function() {
  //   var that = this;
  //   console.log("displaying leaderboard ad");
	// 	console.log(that.data);
  //   window.ga("send", "event", "Ad", "Viewed", decodeHtml(that.data.ad_title));
  //   $("#ad-leaderboard-tmpl")
  //     .tmpl(that.data)
  //     .appendTo(".ad-leaderboard");
  // };

  // PTFAds.prototype.showSingleSquareAd = function() {
  //   var that = this;
  //   console.log("displaying single square ad");
  //   console.log(that.data);
  //   window.ga("send", "event", "Ad", "Viewed", decodeHtml(that.data.ad_title));
  //   $("#single-square-tmpl")
  //     .tmpl(that.data)
  //     .appendTo(".ad");
  // };

  // PTFAds.prototype.show3Ads = function() {
  //   console.log("Displaying 3 ads");
  //   var that = this;

  //   $(that.data).each(function(index, ad) {
  //     // Send event data to GA
  //     window.ga("send", "event", "Ad", "Viewed", decodeHtml(ad.ad_title));
  //   });

  //   $("#three-square-tmpl")
  //     .tmpl(that.data)
  //     .prependTo(".ads");
  // };

  // Ad Helper Functions

  // Decodes HTML entities within a string (textarea never gets attached to the document)
  function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  /* END Ad stuff and start RSS stuff */

  function PTFrss() {
    this.data;
    this.xhr;
  }

  PTFrss.prototype.getRSS = function(rssURL, rssNum) {
    var rssURL = encodeURIComponent(rssURL);
    var _this = this;

    if (this.xhr) {
      this.xhr.abort();
    }
    this.xhr = $.ajax({
      type: "POST",
      url: "/wp-content/plugins/ptf-plugin/",
      data:
        "class=RSS" +
        "&process=getFeed" +
        "&rssURL=" +
        rssURL +
        "&rssNum=" +
        rssNum,
      success: function(data) {
        _this.data = jQuery.parseJSON(data);
        _this.showRSS();
      }
    });
  };

  PTFrss.prototype.showRSS = function() {
    //console.log("showRSS");
    //console.log(this.data);
    //console.log(that.data);

    var that = this;
    //$('#rss-tmpl').tmpl(this.data).prependTo('.links-pane');
  };

  jQuery(document).ready(function($) {
    // Search stuff
    var search = new SearchPTF();

    search.searchBox.keyup(function() {
      search.evalValue();
    });

    search.searchBox.focus(function() {
      search.evalValue();
    });

    search.searchBox.focusout(function() {
      setTimeout(function() {
        search.hideResults();
      }, 300);
    });

    // Ad stuff
    // var leaderBoardAd = new PTFAds();
    // var singleSquareAd = new PTFAds();
    // var threeAds = new PTFAds();
    // var currID = $("body").attr("data-id");
    // leaderBoardAd.getLeaderboardAd();
    // if ($("body").hasClass("single-ptf_beers")) {
    //   singleSquareAd.getSingleSquareAd("single-ptf_beers", currID);
    // }
    // if ($("body").hasClass("single-ptf_bars")) {
    //   singleSquareAd.getSingleSquareAd("single-ptf_bars", currID);
    // }
    // if ($("body").hasClass("single-ptf_events")) {
    //   singleSquareAd.getSingleSquareAd("single-ptf_events", currID);
    // }
    // if ($("body").hasClass("tax-ptf_beer_style")) {
    //   singleSquareAd.getSingleSquareAd(
    //     "tax-ptf_beer_style",
    //     "ptf_beer_style_" + currID
    //   );
    // }
    // if ($("body").hasClass("tax-ptf_hoods")) {
    //   singleSquareAd.getSingleSquareAd("tax-ptf_hoods", "ptf_hoods_" + currID);
    // }

    // if (
    //   $("body").hasClass("page-template-page_search-results-bar-php") ||
    //   $("body").hasClass("page-template-page_search-results-tap-php") ||
    //   $("body").hasClass("page-template-page_search-results-style-php") ||
    //   $("body").hasClass("page-template-page_search-results-event-php") ||
    //   $("body").hasClass("page-template-page_search-results-hood-php")
    // ) {
    //   threeAds.get3Ads(currID);
    // }

    // RSS Stuff

    var homeRSS = new PTFrss();

    if ($("body").hasClass("page-template-page_home-php")) {
      homeRSS.getRSS("http://phillytapfinder.tumblr.com/rss", 3);
    }
  });
});
