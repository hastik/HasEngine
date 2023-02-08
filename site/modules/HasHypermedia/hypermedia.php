<?php namespace ProcessWire;

class Hypermedia{

    public $insertMethod;

    public $requestMethod;

    public $url;
    public $sanitized_url;

    public $query;
    public $sanitized_query;

    public $get;

    public $path;
    public $segments;

    public $page;

    public $possiblePaths;
    
    public $time_start;
    public $time_end;
    public $time;


    public function __call($name, $args) {

        switch ($name) {
            case 'wireResource':
                switch (count($args)) {
                    case 1:
                        return call_user_func_array(array($this, 'setResourceFrom1'), $args);
                    case 2:
                        return call_user_func_array(array($this, 'setResourceFrom2'), $args);
                 }

            case 'includeResource':
                switch (count($args)) {
                    case 0:
                        return $this->anotherFuncWithNoArgs();
                    case 5:
                        return call_user_func_array(array($this, 'anotherFuncWithMoreArgs'), $args);
                }
        }
    }


    function getToQuery($get){
        $queryArray = array();
        foreach($get as $name => $value){
            $queryArray[]= $name."=".$value;
        }

        return implode("&",$queryArray);
    }

    function queryToGet($query){
        $queryArray = explode("&",$query);
        $get = array();
        foreach($queryArray as $queryPart){
            $pos = strpos($queryPart,"=");
            $name = substr($queryPart,0,$pos);
            $value = substr($queryPart,$pos+1,null);    
            $get[$name]=$value;
        }

        return $get;
    }

    function setInsertMethod($arg){
        
        $this->insertMethod = $arg ? $arg : "wire";
        
    }

    function sget()

    function get($arg1, $arg2 = null, $arg3 = null){


        $this->time_start = microtime(true);

        if(is_array($arg2)){            
            $this->setInsertMethod($arg3);
            $this->setResourceFrom2($arg1,$arg2);
        }
        elseif(is_object($arg2)){
            $this->setInsertMethod($arg3);
            $this->setResourceFromPage($arg1,$arg2);
        }
        else{
            $this->setInsertMethod($arg2);
            $this->setResourceFrom1($arg1);
        }

        return $this;

    }

    

    function setResourceFrom1($url){
        $this->url = $url;

        $urlParts = explode("?",$this->url);

        $this->path = $urlParts[0];
        $this->query = $urlParts[1];
        
        $this->get = $this->queryToGet($this->query);
        $this->sanitized_query = http_build_query($this->get);
        $this->sanitized_url = $this->path."?".$this->sanitized_query;
        $this->segments = $this->segmentsFromUrl($this->url);
        
        return $this;
    }

    function setResourceFrom2($path,$get){

        

        $this->requestMethod = "GET";

        $this->get = $get;
        $this->path = $path;

        $this->query = $this->getToQuery($get);
        $this->sanitized_query = http_build_query($get);

        $this->url = $this->path."?".$this->query;
        $this->sanitized_url = $this->path."?".$this->sanitized_query;

        $this->segments = $this->segmentsFromUrl($this->url);

        return $this;

    }

    function setResourceFromPage($url,$page){
        $this->page = $page;
        return $this->setResourceFrom1($url);
    }


    function printTime(){
        $time = round($this->time,4);
        return "<div class='time'>$time</div>";
    }

    function printUrl(){

        $san_url = $this->sanitized_url;
        $url = $this->url;

        return "<div class='time'><a href='$san_url'>$url</a></div>";
    }


    function fetch(){


        
        $output = "";
        switch ($this->insertMethod){
            case "wire" :
                $output = $this->fetchWire(); break;                           
            case "curl" :
                $output = $this->fetchCurl(); break;
            case "include" :
                $output = $this->fetchInclude();
        }

        $this->time_end = microtime(true);
        $this->time = $this->time_end - $this->time_start;        
        
        return $output;
        
            
    }


    function fetchWire(){

        //bd($this->get["cache"]);
        
        if(isset($this->get["cache"])){
            $cache = $this->get["cache"];
            //bd("kes bude");

            if(wire("cache")->get($this->url)){
               // bd("vracim kes");
                return wire("cache")->get($this->url);
                
            }
            else{
                $output = wire()->pages->getByPath($this->url, ['allowUrlSegments' => true, 'allowGet' => true])->render();//.$this->fetchHelpers();
                wire("cache")->save($this->url,$output,$cache);
                //bd("ukladam kes");
                return $output;
            }
        }

        //bd($this->url);
        //bd(wire()->pages->getByPath($this->url, ['allowUrlSegments' => true, 'allowGet' => true])->render().$this->fetchHelpers());

        return wire()->pages->getByPath($this->url, ['allowUrlSegments' => true, 'allowGet' => true])->render();//.$this->fetchHelpers();
    }
        

    function fetchHelpers(){
        
        $helpers = "";
        $helpers .="<br>***************<br>";
        $helpers .="<div><a href='".$this->sanitized_url."'>Odkaz ".$this->url."</a> </div>";
        $helpers .= "<br>***************<br>";

        return $helpers;
        
    }



    /*function getFromUrl($url){
       
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
    }*/

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

    function fetchCurl(){
        $http = new WireHttp();
        $curl = $http->get("http://pwx.local".$this->sanitized_url); //TODO začátek url adresy založit v konstruktoru
        return $curl;
    }

    function fetchInclude(){
        
        $page = $this->page;
        
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
        $this->possiblePaths = $possiblePaths;
        return $possiblePaths;

    }

}

?>