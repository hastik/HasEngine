<?php namespace ProcessWire;

class Hypermedias{

    public $insertMethod;

    public $requestMethod;

    public $url;
    public $url_decoded;
    public $url_cachable;
    public $url_cachable_decoded;

    public $path;
    public $path_no_query;
    public $path_query;
    public $path_query_decoded;
    public $path_data;

    public $segments;
    public $segments_parts;
    //public $segments_no_query;

    public $get;
    public $get_decoded;
    public $get_data;

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
            "=" => "_eql_",
            ">" => "_grt_",
            "<" => "_lwr_",
            "!" => "_exc_",
            "%" => "_pct_",
            "*" => "_ast_",
            "~" => "_tld_",
            "|" => "_bar_",
            "&" => "_amp_",
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
        $query = substr($query,2,null);
        
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

        
        dump($this);
        
        return $this;
    }


    function setResourceFromLive($page){
        
        $this->page = $page;
        $this->page_url = $page->url;
       // dump(wire()->input->urlSegments());
        $this->segments = isset(wire()->input->urlSegments()[1]) ? wire()->input->urlSegments() : null;
        $this->get_data = wire()->input->get();
       
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

        $this->path = $this->page_url;
        $this->path .= $this->segments ? "/".implode("/",$this->segments) : "";

        if($segments){

            $u=0;
            foreach($segments as $segment){
                if($segment[0]=="q"){
                    $segments_parts["query"] = $segment;
                }
                elseif($segment[0]=="p"){
                    $segments_parts["template_uri"] = $segment;
                    $this->template_uri = implode("/",explode("_",substr($segments_parts["template_uri"],2,null)));
                }
                else{
                    $segments_parts["undefined"][]=$segment;
                }
            }

            $this->segments_parts = $segments_parts;
        
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
        

        
        $this->path_no_query = is_array($this->segments_no_query) ? "/".implode("/",$this->segments_no_query) : null;


        $this->url_cachable = $this->page_url.$this->path_no_query;
        $this->url_cachable .= $this->path_query ? "/".$this->path_query : "" ;

        $this->url_cachable_decoded = $this->page_url.$this->path_no_query;
        $this->url_cachable_decoded .= $this->path_query_decoded ? "/".$this->path_query_decoded : "";
        
        $this->url_decoded = $this->url_cachable_decoded;
        $this->url_decoded .= $this->get_decoded ? "?".$this->get_decoded : "";

        $this->url = $this->url_cachable;
        $this->url .= $this->get ? "?".$this->get : "";



        

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
        $path_no_query = $this->path_no_query;
        $path_in_api = $template.$path_no_query;

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

