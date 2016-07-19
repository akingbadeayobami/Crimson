<?php 

    /*
    * Project : Crimson Harvester
    * Description : A Pattern Extractor from a website. Like you want tot extract all the emails from a target website or phone numbers etc just feed the target url, and the pattern (regular expression) you want to extract
    * Usage : 	new CrimsonHarvester('http://mytarget.com','/my/regular/expression/');
    * Date : 19 - 7 - 2016 
    * Author: Akingbade Ayobami Samuel
    */

	require_once 'simpleHtmlDom.php';

	class CrimsonHarvester {
	
	private $url, $pattern; // Instance variables
	
	private  $urlHost, $initialTime; // Project Variables
	
	private $_crawledURLs, $_foundCrimsons, $_caughtExceptions; // File Handlers
	
	private $uncrawledURLs = [],  $crawledURLs = [],  $foundCrimsons = [], $caughtExceptions = []; // Project Containers
        
        private $numberOfNewHarvest = 0, $numberOfCrawledURLs = 0 , $numberOfExceptions = 0; // Counters
        
		
		
        /*
        *   Constructor the initialize of the proceess
        *   The adds uncrawled links to the waiting list
        *   @params : $links -> Links found on the current page
        */
	public function __construct($url,$pattern){
			
        	$this->initialTime = time(); // We get the timestamp of when we started the crawling
            
        	$this->getURLHost($url); // we get the urlost
            
        	$this->filesBootstrap(); // we do fileboostrapping
            
        	$this->pattern = $pattern; // then we get the patterns
            
		$this->url = $url; // the initial URl morelike the base Url

        	$this->uncrawledURLs[] = $this->url; // Since we have not crawled this url we need to add it to the uncrawled URLs
            
	    	$this->crawl($this->url); // then here we initalize the crawling
			
	}
        
		/*
		* Here we handle the file operations so as to resume the operations whenever it stops for any reason
		* Creates a folder if it doesnot exist else it reads the content for continuation
		*/
        private function filesBootstrap(){
            
		if(file_exists($this->urlHost)){  // if the folder for this project exists

			$crawled = file( $this->urlHost . '/crawled.txt'); // then load the the crawled files to this project
			
			foreach($crawled as $f){ // loop through
			
				$this->crawledURLs[] =  trim($f); // append the array
			
			}
			
			
			$crimsons = file( $this->urlHost . '/crimsons.txt'); // then load the the crimsons files to this project
			
			foreach($crimsons as $f){ // loop through
			
				$this->foundCrimsons[] =  trim($f); // append the array
			
			}
			
		
		}else{ // else if the folde project doesnot exitt
	
			mkdir($this->urlHost);  // then make the file
				
		}

		$this->_crawledURLs = fopen($this->urlHost . '/crawled.txt', 'a');  //open the crawled files for appending or create it
	
		$this->_foundCrimsons = fopen($this->urlHost . '/crimsons.txt', 'a');  // open the crimsons file for appending or create it
	
		$this->_caughtExceptions = fopen($this->urlHost . '/exceptions.txt', 'a');  // open the exception file for appending or create it
	
        }
        
		// manage the crawled url
        private function addtoCrawledUrls($url){
            
            $this->crawledURLs[] = $url; // since are crawling this url we need to add it to the crawled once so as not to crawl it again
			
			fwrite($this->_crawledURLs, $url .  "\n"); // write to file
			
			$this->numberOfCrawledURLs++; // increment the operator
			
        }
		
		// manage the found crimsons
        private function addToFoundCrimsons($match){
			
            $this->foundCrimsons[] = $match; // append to array
            
			fwrite($this->_foundCrimsons, $match .  "\n"); // write to file
			
            $this->numberOfNewHarvest++; // increment the counter

        }
        
        // Manage the exceptions logging
        private function addToExceptions($url, $ex){
            
             $this->caughtExceptions[] = $url . ": " . $ex ; // append to array
			 
			 fwrite($this->_caughtExceptions, $url . ": " . $ex  .  "\n"); // wite to file
			 
			 $this->numberOfExceptions++; // increment the counter
			 
        }
        
        /*
		*	CORE of the Crimson Harvester
		*	This is where we are doing the actual crawling of each URl
		*	This is where the recursions is been done
		*/
	private function crawl($url){
			
            $this->addtoCrawledUrls($url); // since are crawling this url we need to add it to the crawled once so as not to crawl it again
            
			$this->uncrawledURLs = array_merge(array_diff($this->uncrawledURLs, [$url])); // in the same vein we need to remove it from the uncrawled url
			
			$body= $this->getBody($url); // we get the content of the of the url
			
            // to replace this with native script
			$this->addLinks($body->find('a')); // add all the new links  we find on this page
			
            // to replace this with naative script
			$this->lookForPattern($body->text()); // We match out the patterns off
			
			if(count($this->uncrawledURLs) == 0){ // If we have fiished crawling
			     
                $duration = time() - $this->initialTime; // get the total time
                
                echo "Crawling " . $this->url . ".\n"; 
				
				echo "Took " . $duration . " secs.\n";

				echo "Harvesting  " . $this->numberOfNewHarvest . " Crimsons.\n";

				echo "Through " . $this->numberOfCrawledURLs . " pages.\n";
						
				echo "Catching " . $this->numberOfExceptions . " exceptions.\n";
                
				return true; // end the whole journey
			
			}else{
			
				return $this->crawl($this->uncrawledURLs[0]); // go on and take on the next URL
			
			}
			
		}
		
        
        /*
        *   This Function is used to add all the new links found on each pages that we crawl
        *   The adds uncrawled links to the waiting list
        *   @params : $links -> Links found on the current page
        */
		private function addLinks($links){
		
			foreach ($links as $link){ // Looping through all the link found on this page
				
				$link = $this->sanitizeUrl($link->href) ; // Here we get a standardized URL from the href tag
				
				$linkParse = parse_url($link); // We parse the link using a native PHP function so as to be able to get the base url of the link
				
				if($linkParse['host'] == $this->urlHost){ // Here we check if the base link is equal to the base link of the instance URL
					
                    if(!in_array($link,$this->uncrawledURLs) ){ // Also we check that we do not have the link in the waiting list
                        
                        if(!in_array($link,$this->crawledURLs) ){ // Also we check that we have not crawled the link before
                             
                            if(strpos($link, '?time=') === false && strpos($link, '&time=') === false && preg_match('/\d{10}/', $link) == 0 ){ // Also to chack that we donot have any time generated link to avoid useless recursions on a same page
                           
								$this->uncrawledURLs[] =  $link; // then we add the link to the waiting list of uncrawled links
                            
							}
							
                        }
                        
                    }
					
				}
				
			}
                
		}
	   
        
        /*
        *   This is we slug in the pattern we want to match throughout the text of the content of this page
        *	And then we collect our found patterns
        */
		private function lookForPattern($text){
		   
            preg_match_all($this->pattern, $text, $matches); // here we perform the regualr expression

            foreach($matches[0] as $match){ // loop through the results
                 
                if(!in_array($match,  $this->foundCrimsons)){ // makes sure tht we dont have this result already collected
                    
                    $this->addToFoundCrimsons($match); // then we add to the crimsons collected
                    
                }
                
            }
            
		}
        
        
       /*
        * We need to get url host for url sanitize sake
        */
        private function getURLHost($url){
            
            $parsedUrl = parse_url($url); // We need to parse the url so as to get the base url to prevent external links
			
			$this->urlHost = $parsedUrl['host']; // here we get the base url
			
        }
		
        
        /*
        *   This is our links standardizer 
        *   @params : $link -> The link we want to standardize
        *   @return : $link -> The standardized Link
        */
		private function sanitizeUrl($link){
			
            $link = strtolower($link); // String to lower to allow uniformity
            
            if (substr($link, strlen($link) - 1 , 1) == '/'){ // (http://facebook.com/) => {http://facebook.com}
        
                $link = substr($link, 0, strlen($link) - 1 ); // removes all trailing slashes

            }
            
			if(strpos($link, "#")){ // (home.php#bottom) => {home.php}
				
				$link = substr($link, 0 , strpos($link, "#")); // Removes all ID references since they are not needed
				
			}
				
			if(substr($link, 0, 1) == "."){ // (./home.php) => {home.php}
				
				$link = substr($link, 1); // Removes the parent directory navigator  
				
			}
			
			if (substr($link, 0 ,7) == "http://" ||  substr($link, 0 ,8) == "https://"){  // Seems Pretty obvious but it is needed since to remove it from the grand else at the bottom
				
				$link = $link;
				
			}else if(substr($link, 0, 2) == "//"){ // (//facebook.com) => {http://facebook.com}  
				
				$link = 'http://' . $link; //append https to the link
				
			} else if(substr($link, 0, 1) == "#"){ // (#) => {thisurl.com}
				
				$link = $this->url;
				
			}else if (substr($link, 0 ,7) == "mailto:"){ // mails
				
				$link = "[" . $link . "]";
				
			} else if (substr($link, 0, 1) != "/"){ // appends full url to root relative paths (index.php) => {thisurl.com/index.php}
				
				$link = $this->url . "/" . $link;
				
			}else{
				
				$link = $this->url . $link;
				
			}
			
			return $link;
			
		}
		
        
        /*
        *   This is where we get the contents of the url from. 
        *   @params : $url -> The link we want to get the content of
        *   @return : The SIMPLE_HTML_DOM object of the page content
        */
		private function getDOM($url){
			
			$blob = file_get_contents($url);
					
			return str_get_html($blob);
            
            // $this->addToExceptions($url, $ex);
            
		}
		
        
        /*
        *   This is where we get the body of the content since are bothered by the content of the page and not the metadata 
        *   @params : $url -> The link we want to get the body of
        *   @return : The SIMPLE_HTML_DOM object of the page body
        */
		private function getBody($url){
		
			$DOM = $this->getDOM($url);
		
			return $DOM->find('body',0);
			
		}
	
	}
	

	
?>
