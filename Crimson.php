<?php
	require_once 'simpleHtmlDom.php';

	class CrimsonHarvester {
	
		private $DOM, $url, $body, $urlHost, $initialTime, $pattern;
		
		private $uncrawledURLs = [];
		
		private $crawledURLs = [];
		
		private $foundCrimsons = [];
        
        private $numberOfHarvest = 0;
        
        
        /*
        *   Constructor the initialize of the proceess
        *   The adds uncrawled links to the waiting list
        *   @params : $links -> Links found on the current page
        */
		public function __construct($url,$pattern){
			
            $this->initialTime = time();
            
            $this->getURLHost($url);
            
            $this->pattern = $pattern;
            
			$this->url = $url; // the initial URl morelike the base Url

            $this->uncrawledURLs[] = $this->url; // Since we have not crawled this url we need to add it to the uncrawled URLs
            
			$this->crawl($this->url); // then here we initalize the crawling
			
		}
        
        
		private function crawl($url){
			
			$this->crawledURLs[] = $url; // since are crawling this url we need to add it to the crawled once so as not to crawl it again
			
			$this->uncrawledURLs = array_merge(array_diff($this->uncrawledURLs, [$url])); // in the same vein we need to remove it from the uncrawled url
			
			$this->body = $this->getBody($url); // we get the content of the of the url
			
            // to replace this with native script
			$this->addLinks($this->body->find('a')); // add all the new links  we find on this page
			
            // to replace this with naative script
			$this->lookForPattern($this->body->text()); // We match out the patterns off
			
			if(count($this->uncrawledURLs) == 0){
			     
                $duration = time() - $this->initialTime;
                
                echo "Crawled " . $this->url . " took " . $duration . " secs. Harvesting  " . $this->numberOfHarvest . " Crimsons through " . count($this->crawledURLs) . " pages";
                
				return true;
			
			}else{
			
				return $this->crawl($this->uncrawledURLs[0]);
			
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
                            
                             $this->uncrawledURLs[] =  $link; // then we add the link to the waiting list of uncrawled links
                            
                        }
                        
                    }
					
				}
				
			}
                
		}
	   
        
        /*
        *   This is we slug in the pattern we want to match throughout the text of the content of this page
        *
        */
		private function lookForPattern($text){
		   
            preg_match_all($this->pattern, $text, $matches);

            foreach($matches[0] as $match){
                
                if(!in_array($match,  $this->foundCrimsons)){
                    
                     $this->foundCrimsons[] = $match;
                    
                     $this->numberOfHarvest++;
                    
                    
                    
                    // want to add files
                    
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
	
	
	new CrimsonHarvester('http://kademiks.com','/[a-zA-Z]{3}(\s)?\d{3}/');
	
?>
