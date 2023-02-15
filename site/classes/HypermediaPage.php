<?php namespace ProcessWire;



class HypermediaPage extends Page {

    use HypermediaCaster;

    static $master_page;

    public $call_method; // wire or live
    public $call_mode; // live or casted

    public $inicialized;
    public $hyper_hash;
    public $hyper_euid;

    public $resource;


    public function initBeforePageRender(){
        if($this->initialized){ // pokud stránka je inicializovaná, jedná se nejspíše o castování, proto nepokračujeme
            return;
        }

        HypermediaPage::$master_page = &$this;

        

        $url = wire("input")->url;
        $query_str = http_build_query($_GET, 'flags_');
        $full_url = $url;
        $full_url .= $query_str ? "?".$query_str : "";
        
        $this->resource = new HypermediaObject($this,$full_url);
        

        $this->template->setFilename($this->resource->template_path);

    }

    public function newSourceFromUrl($decoded_url){
        
        $decoded_url_array = explode("?",$decoded_url);
        $page_path = $decoded_url_array[0];
        
        $coded_get = null;
        if(isset($decoded_url_array[1])){
            $decoded_get = $decoded_url_array[1];
            $coded_get = HypermediaObject::codeUrl($decoded_url);
        }

        $input_url = $page_path;
        $input_url .= $coded_get ? "?".$coded_get : "";

        $page = wire("pages")->getByPath($page_path,['allowUrlSegments' => true]);
        $page = clone $page;
        // unset($page->resource)  ??? nev9m pro4
        
        $newResource =  new HypermediaObject($page,$input_url);

        return $newResource;

    }
    

    public function getResource(){
        return $this->resource;
    }




}

