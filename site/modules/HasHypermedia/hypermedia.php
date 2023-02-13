<?php namespace Processwire;



class Hypermedia {

    public $char_table;

    public $template_locations;
    public $template_paths;
    public $template_resolved_paths;

    public function __construct(){
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

        $this->template_locations = [
			"site" => wire()->config->paths->templates."api/",
			"module" => wire()->config->paths->siteModules."api/"
		];
    }


    function codeUrl($url){
        
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

    function decodeUrl($url){
        $output_array = array();
        //$url = stringToArray($url);
        
        foreach($this->char_table as $char => $code){

            $url=str_replace($code,$char,$url);
        }

        return $url;
    }


    public function getWired($url){
        $page = wire("pages")->getByPath($url,['allowUrlSegments' => true, 'allowGet' => true]);
        unset($page->_hypermedia);

        return $this->get($url,$page,"wire");
    }

    public function getWiredFromPage($url,$page){        
        unset($page->_hypermedia);
        return $this->get($url,$page,"wire");
    }

    public function getWiredFromArray($url,$array){        
        $page = json_decode(json_encode($array), FALSE);
        unset($page->_hypermedia);
        return $this->get($url,$page,"wire");
    }



    public function getLive($page){

        if($page->get("_hypermedia")){
            //dump("no");
            return;
        }
        
        $url = wire("input")->url;
        $url .= wire("input")->queryString ? "?".wire("input")->queryString : "";
        return $this->get($url,$page,"live");
        
    }

    public function get($url,$page,$type){

        $hm_resource = new HypermediaResource($type);
        $hm_resource->set($url,$page->url);

        $hm_resource->initSelf();

        
        if(method_exists($page,"setQuietly")){
            $page->setQuietly("_hypermedia",$hm_resource);
            $hm_resource->setPage($page);
            $page->template->setFilename($hm_resource->template_path);
        }
        else{            
            $hm_resource->setPage($page);
            $page->_hypermedia = $hm_resource;            
        }        
        
        //bd($hm_resource);
        
        

        return $hm_resource;
    }   


    function resolveTemplatePath($router_str){


        $possiblePaths = $this->prepareTemplatePathsFromSegments($router_str);
        //bd($router_str);

        if($router_str){
            if(isset($this->template_resolved_paths[$router_str])){
                return $this->template_resolved_paths[$router_str];
            }
            else{
                foreach($possiblePaths as $path){
                    if(file_exists($path)){
                        $this->template_resolved_paths[$router_str]= $path;                
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
    

    public function hxLink($text,$link,$target,$select,$method = "get"){
        ob_start();
        ?><a href="<?=$link?>" hx-<?=$method?>="<?=$link?>" hx-target="<?=$target?>" hx-select="<?=$select?>" ><?=$text?></a><?php
        $buffer = ob_get_contents();
        @ob_end_clean();
        return $buffer;
    }
    
}