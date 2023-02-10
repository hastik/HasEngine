<?php namespace Processwire;



class HypermediaResource {

    public $type;
    public $request_method;

    public $url_arg;
    public $url;
    public $url_decoded;
    
    public $data;

    public $page_url;

    public $template_path;

    public $page;

    public function __construct($type){
        $this->type = $type;
    }

    public function setPage($page){
        bd("Ukládám Page");
        bd($page);
        $this->page = $page;
    }

    public function set($url,$page_url){
        $this->url_arg = $url;
        $this->page_url = $page_url;

        
        $urls = explode("?",$this->url_arg);
        $urls[0] = wire("hypermedia")->codeUrl($urls[0]);

        $this->url = implode("?",$urls) ;  // TODO tadz by šlo optimalizovat, protože někdy už coded máme

        return $this;
    }

    public function initSelf(){

        $this->request_method = "GET";

        $this->url_decoded = wire("hypermedia")->decodeUrl($this->url); // TODO tatz by šlo ptim. protože někdy už decoded máme


        $url_parts = explode("?",$this->url_decoded);
        //bd($url_parts);
        // GET

        $get = $url_parts[1];
        $get_array = explode("&",$get);        
        //bd($get_array);
        $get_data =$this->arrayToAssoc("=",$get_array);
        //bd($get_data);

        // PATH

        $segments_str = str_replace($this->page_url,"",$this->url_decoded);
        //bd($segments_str);
        $segments = explode("/",$segments_str);
        if(!$segments[0]){
            array_shift($segments);
        }
        $path_data = array("page_url" => $this->page_url);
        
        //bd($segments);
        foreach($segments as $segment){
            if($segment[0]=="r" && $segment[1]=="-"){
                $path_data["router"] = str_replace("r-","",$segment);
            }
            if($segment[0]=="q" && $segment[1]=="-"){
                $clean_str = str_replace("q-","",$segment);
                $clean_arr = explode("&",$clean_str);
               //bd($clean_arr);
                $path_data["query"] = $this->arrayToAssoc("=",$clean_arr);
            }
        }
        
        $path_data["get"] = $get_data;

        $this->data = $path_data;

        //bd($path_data);
        if(isset($this->data["router"])){
            $this->template_path = wire("hypermedia")->resolveTemplatePath($this->data["router"]);
        }
        
        

    }

    public function arrayToAssoc($ch,$array){
        $output = array();
        foreach($array as $part){
            $aPart = explode($ch,$part);
            $output[$aPart[0]] = $aPart[1];
        }
        return $output;
    }


    public function render(){
        if(method_exists($this->page,"setQuietly")){
            return $this->page->render();
        }
        else{
            return "Renderuju array";
        }
    }



}