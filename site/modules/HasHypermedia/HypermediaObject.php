<?php namespace ProcessWire;


class HypermediaObject {

    //use HypermediaObjectConstructor;

    public $master_resource;
    public $master_data;

    public $caller_resource;

    public $data;

    public $request_method;
    public $context;

    public $input_url;
    public $page;

    public $url;
    public $url_decoded;

    public $test;
    public $euid;
    public $temp_data;

    public $hash;
    public $hashhash;

    public $page_url;

    public $template_locations;
    public $template_resolved_paths;
    public $template_paths;
    public $template_path;

    static $char_table;

    public $time_started;
    public $time_init;
    public $time_output;

    public function __construct($page = null,$full_url = null,$caller_resource = null){


        $this->master_resource = HypermediaPage::$master_page->getResource();
        $this->master_data = $this->master_resource->data ?? $this->master_resource->data ?? null;
        
        $this->request_method = "GET";

        $this->caller_resource = $caller_resource;


        if(!$page || !$full_url){
            return;
        }

        $this->page = $page;
        $this->page->resource = $this;
        $this->input_url = $full_url;
        $this->page_url = $page->url;

        //if(HypermediaPage::$i==1){dump($this);}

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

       
        $this->url = $this->input_url;
        $this->url_decoded = HypermediaObject::decodeUrl($this->url);

        $url_parts = explode("?",$this->url_decoded);

        $this->context = wire("input")->url == explode("?",$this->url)[0]
            ? "live"
            : "casted";

        $resource_data = array();
        $resource_data["page_url"] = $this->page_url;
        
        // GET ////////////////////////////////////////

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
        
        
    
        //dump($segments_str);

        if($segments_str){
            $segments = explode("/",$segments_str);
            if(!$segments[0]){
                array_shift($segments);
            }

            
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
        
        $this->cleanEditUidFromUrl(); 
        $this->cleanTempFromUrl();

        if(isset($this->data["router"])){
            $this->template_path = $this->resolveTemplatePath($this->data["router"]);
        }
        $this->page->initialized = true;;
        $this->page->setQuietly("resource",$this);
        
        $this->generateHashes();

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

    public function render(){
        
        if(method_exists($this->page,"setQuietly")){
            $output = $this->page->render(); 
            return $output;
        }
        else{
            return "Renderuju array";
        }
    }


    public function generateUrl($data,$coded = true,$get = 1){
        //dump($get);
        
        $page_url = $data["page_url"];
        $router = "r-".$data["router"];
        //dump($data);
        $query_str_final="";
        if(isset($data["query"])){
            if(count($data["query"])){
                $query_str = "q-".implode("&",self::assocToArray("=",$data["query"]));
                $query_str_coded = self::codeUrl($query_str);
                $query_str_final = $coded ? $query_str_coded : $query_str;
            }
        }     
        
        
        //dump($get==1);
        if($get==1){
            //dump("nenula");
            $get_str = "";
            if(isset($data["get"])){
                if(count($data["get"])){
                    $get_str = http_build_query($data["get"]);
                }
            }
        }
        else{
            $get_str = null;
        }
        
        
        $link = $page_url."/".$router;"/";
        $link .= $query_str_final ? "/".$query_str_final : "";
        $link .= $get_str ? "?".$get_str : "";
        //dump($link);

        //dump($link);

        return $link;

    }

    public function getCastedUrl($coded = true){
        if($this->context == "live"){
            return $this->generateUrl($this->data);    
        }
        else{
            return $this->generateUrl($this->master_data);
        }
        
    }

    public function getLiveUrl($coded = true){
        //dump($this->data);
        return $this->generateUrl($this->data);
    }

    public function getLivePath($coded = true){
        return $this->generateUrl($this->data,true,0);
    }

    public function setEditVal($uid){

        $this->data["get"]["euid"] = $uid;
        $this->master_data["get"]["euid"] = $uid;
        
        return $this;
    }

    public function setQueryVal($name,$value){        

        $this->setValSmart($name,$value,"query");

        //$this->partialInit();

        return $this;
    }

    public function setGetVal($name,$value){
        $this->setExtraGetVal($name,$value);

        return $this;
    }


    public function setValSmart($name,$value,$location){

        if($location == "get"){
            $this->data["get"][$this->hash][$name] = $value;
            $this->master_data["get"][$this->hash][$name] = $value;
        }

        if($location=="query"){
            $this->data["query"][$name] = $value;
            $this->master_data["get"][$this->hash][$name] = $value;
        }

    }


    public function setVal($name,$value,$type){
        
        $this->data[$type][$name] = $value;
        
    }

    public function setExtraGetVal($name,$value){
            
        //dump($this->hash);

        $this->data["get"][$this->hash][$name]=$value;
        $this->master_data["get"][$this->hash][$name]=$value;
    }

    public function setRouter($router){
        
        if(strpos($router,"/")){
            $router = str_replace("/","_",$router);
        }
        $this->data["router"] = $router;

        //$this->partialInit();

        return $this;
    }

    public function setPageUrl($url){
        $this->data["page_url"] = $url;
        $this->page_url = $url;

        //$this->partialInit();

        return $this;
    }

    public function getVal($name,$default = null){
        
        if(isset($this->master_data["get"][$this->hash][$name])){
            return $this->master_data["get"][$this->hash][$name];
        }
        
        if(isset($this->data["query"][$name])){
            return $this->data["query"][$name];
        }

        if(isset($this->data["get"][$this->hash][$name])){
            return $this->data["get"][$this->hash][$name];
        }

        return $default;
        
    }

    public function removeVal($name){

        /*dump("Jsem tady");
        dump($this->hash);
        dump($this->data["get"][$this->hash][$name]);*/

        if(isset($this->master_data["get"][$this->hash][$name])){
            unset($this->master_data["get"][$this->hash][$name]);
        }
        
        if(isset($this->data["query"][$this->hash][$name])){
            unset($this->data["query"][$this->hash][$name]);
        }

        if(isset($this->data["get"][$this->hash][$name])){
            unset($this->data["get"][$this->hash][$name]);
        }

        return $this;

    }

    // depr
    public function update(){

        if(isset($this->data["page_url"]) && isset($this->data["router"]) ){

            $this->generateHashes();

            if(isset($this->data["get"]["undefined"])){
                $this->data["get"][$this->hash] = $this->data["get"]["undefined"];
                unset($this->data["get"]["undefined"]);
            }
            if(isset($this->master_data["get"]["undefined"])){
                $this->master_data["get"][$this->hash] = $this->master_data["get"]["undefined"];
                unset($this->master_data["get"]["undefined"]);
            }

        }

        return $this;

    }

    public function generateHashes(){
        
        $segments_str = $this->getLivePath();
            
            if(isset($this->master_resource->hash)){
                $hash_inherited = $this->master_resource->hash;         
            }
            else{
                $hash_inherited = "m";
            }
            //dump("update hash = ".$segments_str);

            $hash_str = $this->page_url.$segments_str;

            $this->hash = "h".substr(md5($hash_str),0,2);
            //dump($this->hash);
            $this->hashhash = $hash_inherited.$this->hash; 
    }

    public function cleanEditUidFromUrl(){

        //bd($this->data["get"]["euid"]);
        
        if(isset($this->data["get"]["euid"])){            
            //dump($this->data["get"]["euid"]);
            $this->euid = $this->data["get"]["euid"];
            unset($this->data["get"]["euid"]);
        }
        if(isset($this->data["query"]["euid"])){
            $this->euid = $this->data["query"]["euid"];
            unset($this->data["query"]["euid"]);
        }

        if(isset($this->master_data["get"]["euid"])){            
            $this->euid = $this->master_data["get"]["euid"];
            unset($this->master_data["get"]["euid"]);
        }
        if(isset($this->master_data["query"]["euid"])){
            $this->euid = $this->master_data["query"]["euid"];
            unset($this->master_data["query"]["euid"]);
        }

        //bd($this->euid);
        //bd($this->master_resource->euid);
        
    }

    public function cleanTempFromUrl(){
        
        if(isset($this->data["get"]["temp"])){
            $this->temp_data= $this->data["get"]["temp"];
            unset($this->data["get"]["temp"]);
        }
        if(isset($this->master_data["get"]["temp"])){
            $this->temp_data= $this->master_data["get"]["temp"];
            unset($this->master_data["get"]["temp"]);
        }

        
    }

    public function setTempVal($name,$value){
        
        $this->data["get"]["temp"][$name] = $value;
        $this->master_data["get"]["temp"][$name] = $value;

        return $this;
    }

    public function getTempVal($name){
        
        $value = $this->temp_data[$name] ?? $this->temp_data[$name] ?? null;
        return $value;

    }


}

