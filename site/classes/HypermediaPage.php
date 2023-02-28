<?php namespace ProcessWire;



class HypermediaPage extends Page {

    use HypermediaCaster;

    static $master_page;
    static $i;

    public $call_method; // wire or live
    public $call_mode; // live or casted

    public $initialized;
    public $hyper_hash;
    public $hyper_euid;

    public $resource;


    public function initBeforePageRender(){
        
        //if(HypermediaPage::$i==2){dump("here");exit;} //!!!!!!!!!!!!!!!!!!!!!!!

        if(isset($this->resource)){ // pokud stránka je inicializovaná, jedná se nejspíše o castování, proto nepokračujeme
            $this->template->setFilename($this->resource->template_path);
            return;
        }

        if(HypermediaPage::$i==2){ 
            //dump($this);
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
        HypermediaPage::$i++;
        
        
    
        $decoded_url_array = explode("?",$decoded_url);

        
        $page_path = $decoded_url_array[0];

        $coded_get = null;        
        if(isset($decoded_url_array[1])){
            $decoded_get = $decoded_url_array[1];            
            //$coded_get = HypermediaObject::codeUrl($decoded_get);
            
            $coded_get = $decoded_get; 
            
        }

        $input_url = $page_path;
        $input_url .= $coded_get ? "?".$coded_get : "";
        //if(HypermediaPage::$i==1){dump($input_url);}
        $page = wire("pages")->getByPath($page_path,['allowUrlSegments' => true]);
        $page = clone $page;
        // unset($page->resource)  ??? nev9m pro4

        $newResource =  new HypermediaObject($page,$input_url,$this->resource);
    

        return $newResource;

    }

    public function newSourceFromResource($resource){
            
        $url = $resource->getLiveUrl();

        return $this->newSourceFromUrl($url);        

    }

    public function newSourceFromUrlAndPage($url,$page){        
        $input_url = $url;

        $newResource =  new HypermediaObject($page,$input_url,$this->resource);

        return $newResource;
    }

    public function newSourceFromRouterAndPage($router,$page){        
        
        $page = clone $page;

        $page_url = $page->url;

        $router_arr = explode("/",$router);

        $router_str = "r-".implode("_",$router_arr);

        $input_url = $page_url."/".$router_str;

        $newResource =  new HypermediaObject($page,$input_url,$this->resource);

        return $newResource;
    }


    public function newResource($name = null){
        $newResource = new HypermediaObject();
        $newResource->hash = "undefined";
        $newResource->hashhash = "undefined";
        return $newResource;
    }

    public function cloneResource($name = null){
        $newResource = clone $this->resource;
        $newResource->hash = "undefined";
        $newResource->hashhash = "undefined";
        return $newResource;
    }

    

    public function getResource(){
        return $this->resource;
    }


    public function hxLink($text,$live_link,$casted_link,$target,$select,$method = "get"){
        ob_start();
        ?>
            <a href="<?=$casted_link?>" 
                hx-<?=$method?>="<?=$live_link?>" 
                hx-target="<?=$target?>" 
                hx-select="<?=$select?>"
                hx-push-url="<?=$casted_link?>"
                >
                <?=$text?>
            </a>
            <a href="<?=$live_link?>" 
                style='width:0.3em; height:0.3rem; border-radius: 100%; background-color:blueviolet; display: inline-block; margin:0.2em 0.5em'></a><?php
        $buffer = ob_get_contents();
        @ob_end_clean();
        return $buffer;
    }


}

