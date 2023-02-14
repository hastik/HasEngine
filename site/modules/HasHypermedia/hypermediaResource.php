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

    public $time_started;
    public $time_init;
    public $time_output;

    public function __construct($type){
        $this->time_started = microtime(true);
        $this->type = $type;

        return $this;
    }

    public function setPage($page){
        //bd("Ukládám Page");
        //bd($page);
        $this->page = $page;
    }

    public function set($url,$page_url){

        $this->url_arg = $url;
        $this->page_url = $page_url;
                
        $urls = explode("?",$this->url_arg);
        $urls[0] = wire("hypermedia")->codeUrl($urls[0]);

        $this->url = implode("?",$urls) ;  //TODO tadz by šlo optimalizovat, protože někdy už coded máme

        return $this;
    }

    public function initSelf()
    {

        if(!$this->url){
            $this->url = $this->getUrl(true);
        }

        $this->request_method = "GET";
        //bd($this->url);
        $this->url_decoded = wire("hypermedia")->decodeUrl($this->url); // TODO tatz by šlo ptim. protože někdy už decoded máme

        //bd($this->url_decoded);
        $url_parts = explode("?",$this->url_decoded);
        //bd($url_parts);
        // GET

        if(isset($url_parts[1])){
            $get = $url_parts[1];
            $get_array = explode("&",$get);        
            //bd($get_array);
            $get_data =$this->arrayToAssoc("=",$get_array);
            //bd($get_data);
        }
        else{
            $get_data = array();
        }
        

        // PATH

       
        $segments_str = str_replace($this->page_url,"",$url_parts[0]);
        //bd($segments_str);

        $path_data = array("page_url" => $this->page_url);
        if($segments_str){
            $segments = explode("/",$segments_str);
            //bd($segments);
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
        }
        else{
            $path_data["query"] = array();
        }
        
        
        $path_data["get"] = $get_data;

        $this->data = $path_data;
        

        if(isset($this->data["router"])){
            $this->template_path = wire("hypermedia")->resolveTemplatePath($this->data["router"]);
        }
        
        $this->time_init = microtime(true);

    }

    public function arrayToAssoc($ch,$array){
        //bd($array);
        $output = array();
        foreach($array as $part){
            $aPart = explode($ch,$part);
            $output[$aPart[0]] = $aPart[1];
        }
        return $output;
    }

    public function assocToArray($ch,$assoc){
        $output = array();
        foreach($assoc as $key => $name){
            $output[]= $key.$ch.$name;
        }
        return $output;
    }


    public function render(){
        if(method_exists($this->page,"setQuietly")){
            $output = $this->page->render(); 
            $this->time_output = microtime(true);
            return $output;
        }
        else{
            return "Renderuju array";
        }
    }

    public function include(){
        $page = $this->page;
        ob_start();
            include($this->template_path);
        $buffer = ob_get_contents();
        @ob_end_clean();
        $this->time_output = microtime(true);
        return $buffer;
    }

    public function timeReport(){
        $init =  round($this->time_init-$this->time_started,4);
        $outputed = round($this->time_output-$this->time_started,4);

        $output="<div class=times>";
        $output.="<div class='time'>$init</div>";
        $output.="<div class='time'>$outputed</div>";
        $output.="</div>";

        return $output;
    }


    public function getUrl($coded = true){
        //dump($this->data);
        
        $page_url = $this->data["page_url"];
        $router = "r-".$this->data["router"];
        if(count($this->data["query"])){
            $query_str = "q-".implode("&",$this->assocToArray("=",$this->data["query"]));
            $query_str_coded = wire("hypermedia")->codeUrl($query_str);
            $query_str_final = $coded ? $query_str_coded : $query_str;
        }
        if(count($this->data["get"])){
            $get_str = implode("&",$this->assocToArray("=",$this->data["get"]));
        }

        $link = $page_url."/".$router;"/";
        $link .= $query_str_final ? "/".$query_str_final : "";
        $link .= $get_str ? "?".$get_str : "";
        
        return $link;
    }

    public function ahref(){
        $ref = $this->getUrl(true);
        $text = $this->getUrl(false);

        return "<a class='hm-helperlink' href='$ref'>$text</a>";
    }

    public function getVal($name,$default = null){

        if(isset($this->data["get"][$name])){
            return $this->data["get"][$name];
        }
        
        if(isset($this->data["query"][$name])){
            return $this->data["query"][$name];
        }

        return $default;
        
    }

    public function setQueryVal($name,$value){
        $this->setVal($name,$value,"query");

        return $this;
    }

    public function setGetVal($name,$value){
        $this->setVal($name,$value,"get");

        return $this;
    }

    public function setVal($name,$value,$type){
        $this->data[$type][$name] = $value;
    }

    public function setRouter($router){
        
        if(strpos($router,"/")){
            $router = str_replace("/","_",$router);
        }
        $this->data["router"] = $router;

        return $this;
    }

    public function setPageUrl($url){
        $this->data["page_url"] = $url;
        $this->page_url = $url;

        return $this;
    }

}