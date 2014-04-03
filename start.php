<?php
	include("outboundlinks.php");
	$site = "http://xxx"; //here the site you want to crawl
	$linkChecker = new linkChecker( $site );
	$linkChecker->initCrawler(); 
?>
