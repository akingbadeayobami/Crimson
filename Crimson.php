<?php

	require_once 'simpleHtmlDom.php';

	class Crawl {
	
		private $DOM, $url, $body, $urlHost;
		
		private $uncrawledURLs = [];
		
		private $crawledURLs = [];
		
		private $foundCourese = [];
		
		public function __construct($url){
			
			$this->url = $url; // the initial URl morelike the base Url
			
			$parsedUrl = parse_url($url); // We need to parse the url so as to get the base url to prevent external links
			
			$this->urlHost = $parsedUrl['host']; // here we get the base url
			
			$this->startTheCrawling($this->url); // then here we initalize the crawling
			
		}
		
		private function startTheCrawling($url){
			
			$this->crawledURLs[] = $url;
			
			// then also remove the $url from $uncrawled urls
			
			$this->body = $this->getBody($url);
			
			$this->lookForCourses($this->body->text());
			
			$this->manageLinks($this->body->find('a'));
			
			if(count($this->uncrawledURLs) == 0){
			
				// echo things like the course found time taken and numbe rof url crawled
				
				// .txt will need to do a file open stuufs
			
				return true;
			
			}else{
			
				$this->startTheCrawling();
			
			}
			
		}
		
		private function manageLinks($links){
		
			foreach ($links as $link){
				
				$link = $this->sanitizeUrl($link->href) ;
				
				$linkParse = parse_url($link);
				
				if($linkParse['host'] == $this->urlHost){
					
					// if $link is not in $found links and $crawled links then
					
					$this->uncrawledURLs[] =  $link;
				
				}
				
			}
		
		}
	
		private function lookForCourses($text){
		
				// preg matter
				
				// get it as a plain array 
				
				// then merge with no duplicte with $this->foundcourses;
		
		}
		
		private function findLink($element){
			
			$href = ( $element->find('a',0) ) ? $element->find('a',0)->href : $element->href;
				
			return $this->sanitizeUrl($href);
			
		}
		
		private function sanitizeUrl($link){
			
			if(strpos($link, "#")){ 
				
				$link = substr($link, 0 , strpos($link, "#")); 
				
			}
				
			if(substr($link, 0, 1) == "."){ 
				
				$link = substr($link, 1);
				
			}
			
			if (substr($link, 0 ,7) == "http://"){
				
				$link = $link;
				
			} else if (substr($link, 0 ,8) == "https://"){
				
				$link = $link;
				
			}else if(substr($link, 0, 2) == "//"){ 
				
				$link = $this->url . '/' . substr($link, 2);
				
			} else if(substr($link, 0, 1) == "#"){ 
				
				$link = $this->url;
				
			}else if (substr($link, 0 ,7) == "mailto:"){
				
				$link = "[" . $link . "]";
				
			} else if (substr($link, 0, 1) != "/"){
				
				$link = $this->url . "/" . $link;
				
			}else{
				
				$link = $this->url . $link;
				
			}
			
			// if the base of teh url is not the base of the inout url then we dont want o add the page o our groupmof links
			
			return $link;
			
		}
		
		private function getDOM($url){
			
			$blob = file_get_contents($url);

			return str_get_html($blob);
			
		}
		
		private function getBody($url){
		
			$DOM = $this->getDOM($url);
		
			return $DOM->find('body',0);
			
		}
	
	}
	
	
	new Crawl('http://moodle.unilag.edu.ng');
	
?>