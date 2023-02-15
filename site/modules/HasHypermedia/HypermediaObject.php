<?php namespace ProcessWire;


class HypermediaObject {

    //use HypermediaObjectConstructor;

    public $master_resource;
    public $master_data;

    public $data;

    public $request_method;
    public $context;

    public $input_url;
    public $page;

    public $url;
    public $url_decoded;

    public $test;

    public $hash;
    public $hashhash;

    public $page_url;

    public $template_locations;
    public $template_resolved_paths;
    public $template_paths;
    public $template_path;

    static $char_table;

    public function __construct($page,$full_url){
        $this->page = $page;
        $this->input_url = $full_url;
        $this->page_url = $page->url;

        $this->initCharTable();
        $this->initTemplateLocations();
        

        $this->initSelf();   
    }

    public function initCharTable(){
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
        self::$char_table = $table;

    }

    public function initTemplateLocations(){
        $this->template_locations = [
			"site" => wire()->config->paths->templates."api/",
			"module" => wire()->config->paths->siteModules."api/"
		];
    }

    static function codeUrl($url){
        
        $output_array = array();

        foreach(mb_str_split($url) as $character){
            if (isset(self::$char_table[$character])){
                $output_array[] = self::$char_table[$character];
            }
            else {
                $output_array[] = $character;
            }
        }
        return implode("",$output_array);
    }

    static function decodeUrl($url){
        foreach(self::$char_table as $char => $code){
            $url=str_replace($code,$char,$url);
        }
        return $url;
    }

    public function initSelf(){
        if(isset($this->input_url)){
            $this->initSelfFromUrl();
            return $this;
        }
        else{
            // nemám url tzn mám data, ze lterých musím url poskládat ...
            // otázka je, co bude potřeba inicializovat když mám data
        }
    }

    public function initSelfFromUrl(){

        $this->master_resource = HypermediaPage::$master_page->getResource();
        $this->master_data = $this->master_resource->data;
        
        $this->request_method = "GET";

        $this->url = $this->input_url;
        $this->url_decoded = HypermediaObject::decodeUrl($this->url);

        $url_parts = explode("?",$this->url_decoded);

        $this->context = wire("input")->url == explode("?",$this->url)[0]
            ? "live"
            : "casted";
        
        $resource_data = array();
        $resource_data["page_url"] = $this->page_url;
        
        // GET ////////////////////////////////////////
        //dump($url_parts[1]);
        if(isset($url_parts[1])){
            $get = $url_parts[1];
            $get_data = ""; //todo tady možná to bude jinak ... možná array?
            parse_str($get,$get_data);
        }
        else{
            $get_data = array();
        }
        
        $resource_data["get"] = $get_data;
        
        // PATH

        $segments_str = str_replace($this->page_url,"",$url_parts[0]);
        
        $this->hash = substr(md5($segments_str),0,2);
        $this->hashhash = $this->master_resource->hash.$this->hash; //todo Špatěn má se brát caller_resource

        

        if($segments_str){
            $segments = explode("/",$segments_str);
            if(!$segments[0]){
                array_shift($segments);
            }
            $resource_data = array("page_url" => $this->page_url);
            
            foreach($segments as $segment){
                if($segment[0]=="r" && $segment[1]=="-"){
                    $resource_data["router"] = str_replace("r-","",$segment);
                }
                if($segment[0]=="q" && $segment[1]=="-"){
                    $clean_str = str_replace("q-","",$segment);
                    $clean_arr = explode("&",$clean_str);
                //bd($clean_arr);
                    $resource_data["query"] = self::arrayToAssoc("=",$clean_arr);
                }
            }
        }
        else{
            $resource_data["query"] = array();
        }

        $this->data = $resource_data;

        //$this->cleanEditUidFromUrl(); //todo 

        if(isset($this->data["router"])){
            $this->template_path = $this->resolveTemplatePath($this->data["router"]);
        }

    }

    static function arrayToAssoc($ch,$array){
        //bd($array);
        $output = array();
        foreach($array as $part){
            $aPart = explode($ch,$part);
            $output[$aPart[0]] = $aPart[1];
        }
        return $output;
    }

    static function assocToArray($ch,$assoc){
        $output = array();
        foreach($assoc as $key => $name){
            $output[]= $key.$ch.$name;
        }
        return $output;
    }


    function resolveTemplatePath($router_str){
        
        //bd($router_str);
        $possiblePaths = $this->prepareTemplatePathsFromSegments($router_str);
        //bd($possiblePaths);

        if($router_str){
            if(isset($this->template_resolved_paths[$router_str])){ //todo tohle se musí přemístit do Manageru
                return $this->template_resolved_paths[$router_str];
            }
            else{
                foreach($possiblePaths as $path){
                    if(file_exists($path)){
                        $this->template_resolved_paths[$router_str]= $path; 
                        //bd($path);               
                        return $path;
                    }
                }
            }
        }

        
        
    }

    function prepareTemplatePathsFromSegments($router_str){
      
        
        $router = explode("_",$router_str);
        
        $pathsToApi = $this->template_locations;
        //bd($pathsToApi);
        $possiblePaths = [];

		foreach($pathsToApi as $location => $pathToApi){
			if($router){
				$router_parts = $router;				
				$name_segments = array();
				do{						
					$separator = $router_parts ? "/" : "";

					$default_path = implode("/",$router_parts).$separator."default.php";

					array_unshift($name_segments,array_pop($router_parts));

					$separator = $router_parts ? "/" : "";

					$specific_path = implode("/",$router_parts).$separator.implode("_",$name_segments).".php";
					
					$possiblePaths[] = $pathToApi.$specific_path;
					$possiblePaths[] = $pathToApi.$default_path;
					
				}
				while($router_parts);
			}
		}

        $this->template_paths = $possiblePaths;

        return $possiblePaths;

    }

    public function include(){
        $page = $this->page;
        //dump($page);        
        ob_start();
            include($this->template_path);
        $buffer = ob_get_contents();
        @ob_end_clean();
        
        return $buffer;
    }

}