<?php namespace ProcessWire;

class Hypermedias{

    public $insertMethod;

    public $requestMethod;

    public $url;
    public $sanitized_url;

    public $cachable_url;
    public $sanitized_cachable_url;

    public $path;
    public $segments;

    public $get_query;
    public $sanitized_get_query;
    public $get_query_array;

    public $path_query;
    public $sanitized_path_query;
    public $path_query_array;
    

    public $possiblePaths;

    
    public $page;

    

    public $time_start;
    public $time_end;
    public $time;



    function setInsertMethod($method){
        $this->insertMethod = $method;
    }
    
    function get($arg1, $arg2 = null, $arg3 = null){


        $this->time_start = microtime(true);

        if(is_array($arg2)){            
            $this->setInsertMethod($arg3);
            //$this->setResourceFrom2($arg1,$arg2);
        }
        elseif(is_object($arg2)){
            $this->setInsertMethod($arg3);
            $this->setResourceFromPage($arg1,$arg2);
        }
        elseif(is_object($arg1)){
            $this->setInsertMethod($arg2);
            $this->setResourceFromLive($arg1);
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
        $this->get_query = $urlParts[1];

        

        // ! Get Query 

        $this->get_query_array = $this->queryToGet($this->get_query);
        $this->sanitized_get_query = http_build_query($this->get_query_array);

        $this->sanitized_url = wire("sanitizer")->url($this->path."?".$this->sanitized_get_query);
        
        // ! Segmenty
        
        $this->segments = $this->segmentsFromUrl($this->url);



        dump($this);
        
        return $this;
    }


    function setResourceFromLive($page){
        
        $this->page = $page;
        $this->segments = $page->get("_urlSegments") ? $page->get("_urlSegments") : wire()->input->urlSegments();
        $this->get_query = $page->get("_get") ? $page->get("_get") : wire()->input->get();
        
        $this->completeData();
        
        dumpBig($this);

        return $this;
    }

    function completeData(){
        
    }


    function setPageRender($page){

    }

    function queryToGet($query){
        $queryArray = explode("&",$query);
        $get = array();
        foreach($queryArray as $queryPart){
            $part = explode("=",$queryPart);                        
            $get[$part[0]]=$part[1];
        }

        return $get;
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

}

