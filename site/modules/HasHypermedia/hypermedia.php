<?php namespace ProcessWire;

class Hypermedia{

    public $name;
    public $url;
    public $path;
    public $query;
    public $get;
    public $segments;
    public $endpoint;
    public $requestMethod;

    function setFragment($url,$query){

        $this->requestMethod = "GET";
        $this->query = $query;
        $this->url = $url;
        $this->segments = $this->segmentsFromUrl($url);

        $this->get = http_build_query($query);

        $this->endpoint = $url."?".$this->get;

        return $this;
    }


    function renderFragment($name,$url,$query){

        $this->setFragment($name,$url,$query)->render();

    }

    function fetch(){
        return wire()->pages->getByPath($this->endpoint, ['allowUrlSegments' => true, 'allowGet' => true])->render().$this->fetchHelpers();
    }


    function render(){

        echo $this->fetch();
        $castedPage = wire()->pages->getByPath($this->endpoint, ['allowUrlSegments' => true, 'allowGet' => true]);
        echo $castedPage->render();
        echo $this->fetchHelpers();
        
        return $this;
    }

    function fetchHelpers(){
        
        $helpers = "";
        $helpers .="<br>***************<br>";
        $helpers .="<div><a href='".$this->endpoint."'>Odkaz ".$this->endpoint."</a> </div>";
        $helpers .= "<br>***************<br>";

        return $helpers;
        
    }

    function include($data){

    }


    function getFromUrl($url){
       
        $question_mark = strpos($url,"?");

        if($question_mark){
            $pathParts = explode("?",$url);
            //var_dump($pathParts);
            $url = $pathParts[0];
            //var_dump($pathParts[1]);
            $_getString = $pathParts[1];
            //var_dump($_getString);
            $_get = array();
            parse_str($_getString,$_get);
            //var_dump($_get);
            return $_get;
        }

        else{
            return null;
        }
    }

    function segmentsFromUrl($url){

        $question_mark = strpos($url,"?");

        if($question_mark){
            $pathParts = explode("?",$url);
            //var_dump($pathParts);
            $url = $pathParts[0];
        }
        $this->path = $url;

        $segments = explode("/",$url);
        if($segments[0]) {} else { array_shift($segments);}

        return $segments;

    }

    function testParsingUrl($url){
        bd($this->getFromUrl($url));
        bd($this->segmentsFromUrl($url));
    }

    function templateFile(){

    }

    function wireFragment(){
        return wire()->pages->getByPath($this->endpoint, ['allowUrlSegments' => true, 'allowGet' => true])->render().$this->fetchHelpers();
    }

    function curlFragment(){
        $http = new WireHttp();
        $curl = $http->get("http://pwx.local".$this->url); //TODO začátek url adresy založit v konstruktoru
        return $curl;
    }

    function includeFragment($page){

        $segment_string = str_replace($page->url, "",$this->path);

		$activeSegments = $this->segmentsFromUrl($segment_string);
		$activeGet = $this->get;
		$activeRequestMethod = $this->requestMethod;

		$page->setQuietly("_hasUrlSegments",$activeSegments);
		$page->setQuietly("_hasGet",$activeGet);
		$page->setQuietly("_hasRequestMethod",$activeRequestMethod);


        $possible = $this->possibleTemplateFilesFromSegments($page->template->name,$page->get("_hasUrlSegments"));

		$page->setQuietly("_hasTemplateFile",$this->activeTemplateFromPossibles($possible));

        ob_start();
            include($page->get("_hasTemplateFile"));
           
        $buffer = ob_get_contents();
        @ob_end_clean();

        return $buffer;

    }


    function activeTemplateFromPossibles($possiblePaths){
        foreach($possiblePaths as $path){
			if(file_exists($path)){
				return $path;
			}
		}
    }

    function possibleTemplateFilesFromSegments($template,$activeSegments){
      
        $pathsToApi = [
			"site" => wire()->config->paths->templates."api/",
			"module" => wire()->config->paths->siteModules."api/"
		];

        $possiblePaths = [];

        $time_start = microtime(true);

		foreach($pathsToApi as $location => $pathToApi){
			if($activeSegments){
				$segments = $activeSegments;
				array_unshift($segments,$template);
				$name_segments = array();
				do{	
					
					$separator = $segments ? "/" : "";

					$default_path = implode("/",$segments).$separator."default.php";

					array_unshift($name_segments,array_pop($segments));

					$separator = $segments ? "/" : "";

					$specific_path = implode("/",$segments).$separator.implode("_",$name_segments).".php";
					
					$possiblePaths[] = $pathToApi.$specific_path;
					$possiblePaths[] = $pathToApi.$default_path;
					
				}
				while($segments);
			}
		}

		$time_end = microtime(true);
		$time = $time_end - $time_start;

		//bd($time);		

        return $possiblePaths;

    }

}

?>