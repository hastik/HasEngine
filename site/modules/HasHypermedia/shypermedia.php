<?php namespace ProcessWire;

class Hypermedias{

    public $insertMethod;

    public $requestMethod;

    public $url;
    public $url_decoded;

    public $path;
    public $path_decoded;


    public $segments;
    public $segments_parts;
    public $segments_parts_decoded;
    public $segments_data;
    //public $segments_no_query;

    public $get;
    public $get_decoded;
    public $get_data;


    public $url_data;

    public $page;
    public $page_url;
    private $templates_locations;
    public $template_uri;
    private $templates_resolved_paths;
    public $template_paths;
    public $template_path;

    private $char_table; 
    private $time_start;
    private $time_end;
    private $time;



    function setInsertMethod($method){
        $this->insertMethod = $method;
    }

    function __construct(){
        $table = array(
            "=" => "_eq_",
            ">" => "_gr_",
            "<" => "_lw_",
            "!" => "_ex_",
            "%" => "_pc_",
            "*" => "_as_",
            "~" => "_tl_",
            "|" => "_br_",
            "&" => "_am_",
            "," => "_cm_",
            "." => "_dt_",
            "$" => "_dl_",
        );
        $this->char_table = $table;

        $this->templates_locations = [
			"site" => wire()->config->paths->templates."api/",
			"module" => wire()->config->paths->siteModules."api/"
		];

        $this->templates_resolved_paths = array();

    }

    function translateToUrl($url){
        
        $output_array = array();
        //$url = stringToArray($url);
        
        foreach(mb_str_split($url) as $character){
            //dump($this->char_table[$character]);
            if (isset($this->char_table[$character])){
                
                $output_array[] = $this->char_table[$character];
            }
            else {
               
                $output_array[] = $character;
            }
        }

        return implode("",$output_array);
    }

    function translateFromUrl($url){
        $output_array = array();
        //$url = stringToArray($url);
        
        foreach($this->char_table as $char => $code){

            $url=str_replace($code,$char,$url);
        }

        return $url;
    }

    function decodedQueryToArray($query){
        
        $query_parts=explode("&",$query);
        

        $output = array();
        foreach($query_parts as $queryPart){
            $pos = strpos($queryPart,"=");
            $name = substr($queryPart,0,$pos);
            $value = substr($queryPart,$pos+1,null);    
            $output[$name]=$value;
        }
        return $output;
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
        $this->get = $urlParts[1];

        

        // ! Get Query 

        $this->get_data = $this->queryToGet($this->get);
        $this->get_sanitized = http_build_query($this->get_data);

        $this->sanitized_url = wire("sanitizer")->url($this->path."?".$this->get_sanitized);
        
        // ! Segmenty
        
        $this->segments = $this->segmentsFromUrl($this->url);

        
        //dump($this);
        
        return $this;
    }


    function setResourceFromLive($page){
        
        $this->page = $page;
        $this->page_url = $page->url;
       // dump(wire()->input->urlSegments());
        $this->segments = isset(wire()->input->urlSegments()[1]) ? wire()->input->urlSegments() : null;
        $this->get_data = wire()->input->get()->getArray();
       
        $this->get = http_build_query($_GET);

        /*$get = "";
        $i=0;
        foreach($_GET as $name => $value){
            $i++;
            $get.= $name."=".$value;
            if($i<count($_GET)){
                $get.="&";
            }
        }
        $this->get = $get;
        */

        $this->completeData();
        
        $this->resolveTemplatePath();
        
        //dumpBig($this);

        return $this;
    }

    function completeData(){
        
        $this->requestMethod = "GET";
        $this->get_decoded = $this->get;

        $segments = $this->segments;

        


        if($segments){            
            $u=0;
            foreach($segments as $segment){
                if($segment[0]=="q"){
                    $segments_parts["query"] = substr($segment,2,null);
                }
                elseif($segment[0]=="r"){
                    $segments_parts["route"] = substr($segment,2,null);
                    $this->template_uri = implode("/",explode("_",$segments_parts["route"]));                    
                }
                else{
                    $segments_parts["undefined"][]=$segment;
                }
            }

            $this->segments_parts = $segments_parts;


            $segments_parts_decoded = $segments_parts;
            if(isset($segments_parts_decoded["query"])){
                $segments_parts_decoded["query"] = $this->translateFromUrl($segments_parts_decoded["query"]);
            }
            $this->segments_parts_decoded = $segments_parts_decoded;


            $segments_data;
            
            foreach($this->segments_parts_decoded as $name => $value){
                //dump($value);
                if($name == "route"){
                    $segments_data["route"] = $value;
                }
                if($name == "query"){
                    $segments_data["query"] = $this->decodedQueryToArray($value);
                }
            }
            
            $this->segments_data = $segments_data;
        
        }
        

        if( isset($this->path_query) || isset($this->path_query_decoded)){
            if(!isset($this->path_query)){
                $this->path_query =  $this->translateToUrl($this->path_query);
            }
            if(!isset($this->path_query_decoded)){
                $this->path_query_decoded =  $this->translateFromUrl($this->path_query);
            }
        }
        
        if(isset($this->path_query_decoded)){
            $this->path_data = $this->decodedQueryToArray($this->path_query_decoded);
        }   
        
        $url_data = array();
        $url_data["page"] = $this->page_url;
        
        if(isset($this->segments_data["route"])){
            $url_data["route"] = $this->segments_data["route"];
        }
        if(isset($this->segments_data["query"])){
            $url_data["query"] = $this->segments_data["query"];
        }
        if($this->get_data){
            $url_data["get"] = $this->get_data;
        }
        
        $this->url_data = $url_data;
        


        $this->path = $this->page_url;
        $this->path .= $this->segments ? "/".implode("/",$this->segments) : "";

        $this->path_decoded = $this->page_url;
        $this->path_decoded .= $this->segments ? "/".implode("/",$this->segments_parts_decoded) : "";;
        
        $this->url = $this->path;
        $this->url .= $this->get ? "?".$this->get : "";
        
        $this->url_decoded = $this->path_decoded;
        $this->url_decoded .= $this->get_decoded ? "?".$this->get_decoded : "";

        //$this->setQueryData("limit",40);
        //$this->unsetQueryData("selector");
        dump($this->getUrl());
        dump($this->getUrlDecoded());
        
    }

    function implodeAssocArray($ch,$array){

        $output_array = array();
        foreach($array as $name => $value){
            $output_array[]=$name."=".$value;
        }

        return implode($ch,$output_array);
    }


    function getUrlDecoded(){

        $data = $this->url_data;

        $url="";

        $url .= isset($data["page"]) ? $data["page"] : "";
        $url .= isset($data["route"]) ? "/r-".$data["route"] : "";
        $url .= isset($data["query"]) ? "/q-".$this->implodeAssocArray("&",$data["query"]) : "";
        $url .= isset($data["get"]) ? "?".$this->implodeAssocArray("&",$data["get"]) : "";

        return $url;

    }

    function getUrl(){

        $data = $this->url_data;

        $url="";

        $url .= isset($data["page"]) ? $data["page"] : "";
        $url .= isset($data["route"]) ? "/r-".$data["route"] : "";
        $url .= isset($data["query"]) ? "/q-".$this->translateToUrl($this->implodeAssocArray("&",$data["query"])) : "";
        $url .= isset($data["get"]) ? "?".$this->implodeAssocArray("&",$data["get"]) : "";

        return $url;

    }

    function setQueryData($name,$value){
        return $this->setData("query",$name,$value);
    }

    function unsetQueryData($name){
        return $this->unsetData("query",$name);
    }

    function setData($space,$name,$value){
        $this->url_data[$space][$name]=$value;
        return $this;
    }

    function unsetData($space,$name){
        unset($this->url_data[$space][$name]);
        return $this;
    }


    function getData($space,$name){
        return $this->url_data[$space][$name];
    }

    function getQueryData($name){        
        return $this->getData("query",$name);
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

    // TODO aktivní templaty uložit, abych pro stejný path_no_query nemusel cel0 kolečko opakovat
    function resolveTemplatePath(){
        $template = $this->page->template->name;
        
        $path_in_api = $template."/".$this->template_uri;

        if(isset($this->templates_resolved_paths[$path_in_api])){
            $this->template_path = $this->templates_resolved_paths[$path_in_api];
            return $this;
        }

        
        
        $possiblePaths = $this->prepareTemplatePathsFromSegments();
        foreach($possiblePaths as $path){
			if(file_exists($path)){
                $this->templates_resolved_paths[$path_in_api]= $path;
                $this->template_path=$path;
				return $this;
			}
		}
    }

    function prepareTemplatePathsFromSegments($template_uri = null){
      
        if(!$template_uri){
            $template_uri = $this->template_uri;
        }
        
        if($template_uri){
            $activeSegments = explode("/",$template_uri);
        }
        else{
            $activeSegments = array();
        }
        

        $pathsToApi = $this->templates_locations;

        $possiblePaths = [];
        $template = $this->page->template->name;

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

		
        $this->template_paths = $possiblePaths;
        return $possiblePaths;

    }

}

