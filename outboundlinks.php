<?php
include ("simple_html_dom.php");

class linkChecker{
   private $site = "NOT DEFINED";
   private $file = "NOT DEFINED";
   private $pages = Array();
   private $considered = Array();
   private $count_outer =0;
   
   function __construct( $site ) {
	   $this->logEvent("=== __construct() start, site = $site ===");
	   
       $this->site = $site;
	   try {
	   		$html = file_get_html( $site ); //get home page
	   }
	   catch (Exception $e) {
       		echo 'Caught exception: ',  $e->getMessage(), ', maybe no internet connection available.\n';
	   }
	   
	   $this->file = preg_replace("/http:\/\//", "", $site)."-".date('Y-d-m').".csv";
	   $this->logEvent("file is ".$this->file);
	   
	   //start algorithm
	   $this->logEvent("start search, first time finder()");
	   $this->finder($html, $this->site);
	   
	   $this->logEvent("=== __construct() end, site = $site ===");
   }	   
   
   private function logEvent($message) {
    if ($message != '') {
        // Add a timestamp to the start of the $message
        $message = date("d/m/Y H:i:s").': '.$message;
        $fp = fopen('log_events.txt', 'a');
        fwrite($fp, $message."\n");
        fclose($fp);
    }
   }
   
	private function isOuterLink( $element ){		
		return !preg_match( "/http:\/\/".preg_replace("/http:\/\//", "", $this->site)."(.*)/", $element->href );
	}
	
	public function initCrawler(){
		$this->logEvent("=== initCrawler() start ===");

		$lines = file( "inner-".$this->file );

		// Loop through our array, show HTML source as HTML source; and line numbers too.
		foreach ($lines as $line_num => $line) {
		    echo "Riga #<b>{$line_num}</b> : " . htmlspecialchars($line) . "<br />\n";
			array_push ( $this->pages, htmlspecialchars($line) );
		}
		
		$this->logEvent("=== initCrawler() end ===");
		$this->crawler(); //start scansione
	}

	function crawler(){
		//$this->logEvent("=== crawler() start ===");
		
		//prepare data
		$this->pages = array_unique( $this->pages ); //no duplicate URLs
		sort( $this->pages );
		
		//main loop
		$it=1;
		while ( !empty($this->pages) ){
			$this->logEvent(" it ".$it.", in queue: ".sizeof($this->pages)." elements. ");
			
			//array as stack
			$curr_url = array_pop( $this->pages );

			//track considered elements
			array_push( $this->considered, $curr_url );
			
			$curr_html = file_get_html ($curr_url);
			$this->finder( $curr_html, $curr_url );
			$it++;
		}	
		
		//$this->logEvent("=== crawler() end! ===");
		
	}


	function finder( $html , $curr_url){
		if ( $html==null ) return;
		
		//$this->logEvent("=== finder() start ===");
		
		//nofollow 
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML($html); // loads your html
		$xpath = new DOMXPath($doc);
		// returns a list of all links with rel=nofollow
		$nlist = $xpath->query("//a[@rel='nofollow']");
		
		
		foreach ($nlist as $entry) {
    		//$this->logEvent ("@@@ FOUND {$entry->previousSibling->previousSibling->nodeValue}," .
         	//" by {$entry->previousSibling->nodeValue}\n");
		}

		$link = "/<(a)[^>]*rel\s*=\s*(['\"])nofollow\\2[^>]*>(.*?)<\/\\1>/i";
		$nlist = $xpath->query( $link );
		foreach ($nlist as $entry) {
			//$this->logEvent ("@@@ FOUND {$entry->previousSibling->previousSibling->nodeValue}," .
         	//" by {$entry->previousSibling->nodeValue}\n");

		}
		
		// Find all links
		foreach( $html->find('a') as $element )
		{
			$curr_size = sizeof( $this->pages );

			//if ( !duplicato )					
			if ( !in_array( $element->href, $this->considered ) ){
				
				if ( $this->isOuterLink( $element ) ){
					/*
					 * 
simple_html_dom_node Object
(
    [nodetype] => 1
    [tag] => a
    [attr] => Array
        (
            [href] => http://www.facebook.com/my.social.web
            [class] => flare-button button-type-facebook flare-iconstyle-round-bevel flare-iconsize-24
            [style] => background-color:#0b59aa;
        )
					 * 
					 * */
					
					//$rel=$element['attr']['rel'];
					//echo "element start<pre>";
					//print_r($element);
					//echo "</p>element end";
					//die();
					if ( $element->rel=="" )
						$this->logEvent( "*,".$element->href.",link="+$link+"," );
					else
						$this->logEvent( "+,".$element->href.","+$link+",rel="+$element->rel );
					
					//save on current file links
					$current = file_get_contents( "outer-".$this->file );
					$current .= $curr_url.",".$element->href.".,rel=".$element->rel."\n";
					file_put_contents( "outer-".$this->file, $current ); 
					$count_outer++;
					//$this->logEvent( "outer: $html, <b>".$element->href."" ); 
				}
				else if ( !array_search( $element, $this->pages ) ){
					//$current = file_get_contents( "inner-".$this->file );
					//$current .= $element->href."\n";
					//file_put_contents( "inner-".$this->file, $current ); 
					$count_inner++;
					
					//save in array  
					array_push ( $this->pages, $element->href );
					//$this->logEvent( "inner: $html, <i>".$element->href.""); 					
				}
			}
		}
		
		$this->logEvent("=== finder() end ===");
		$this->logEvent("outer links: "+$count_outer);
		$this->logEvent("inner links: "+$count_inner);
			
		}
	}


?>
