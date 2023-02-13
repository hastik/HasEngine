<?php namespace ProcessWire;

include "hResourcer.php";

function hComponent($name,$component_data= false){

    $component = new hComponent($name, $component_data); 
    
    return $component;
}


class hComponent {

    public $path;
    public $data;
    public $name;
    public $template;
    public $less_strings = array();
    public $less_files = array();

    public function __construct($name,$component_data){

        if($component_data instanceof RepeaterMatrixPage){
            //dump("Je to repeater page");
            //dump($data->getFields());
        }

        //bd($this->data);
        $this->name = $name;
        $this->path = $this->getComponentFile($name);
        $this->data = $component_data;
    }

    public function __toString()
    {
        return $this->preRender();
    }

    public function fetch(){
        return $this->preRender();
    }

    public function render(){
        echo $this->preRender();
    }

    public function testMe($ta){
        //bd($ta);
    }

    public function addLessString($string){
        $this->less_strings[] = $string;
        
    }
    public function addLessFile($file_name){
        $this->less_files[] = $file_name;
    }

    public function addLessParser(){

        $this->less_strings[]=Templater::getPartial("less");
        Templater::removePartial("less");

        foreach($this->less_strings as $string){
            hResourcer::addLessString($string,$this->name);
        }
        foreach($this->less_files as $file){
            hResourcer::addLessFile($file,$file);
        }

    }

    public function preRender(){
       
        if(file_exists($this->path)){
            //extract($this->data);
            ob_start();

            $data = $this->data;

            include $this->path;
            $buffer = ob_get_contents();
            @ob_end_clean();
            
            $this->addLessParser();

            return $buffer;
        }
        else{
            echo "error v šabloně - neexistující soubor se šablonou";
            die;
        }
    }


    public function resolveComponentFilePath($component_name){
		
		$name_arr = explode("_",$component_name);

		if(count($name_arr)==1){
			$c_name = $component_name;
			$c_layout = "default";
			$c_version = "v1";
		}
		elseif(count($name_arr)==2){
			$c_name = $name_arr[0];
			$c_layout = $name_arr[1];
			$c_version = "v1";
		}
		elseif(count($name_arr)==3){
			$c_name = $name_arr[0];
			$c_layout = $name_arr[1];
			$c_version = $name_arr[2];
		}

		return $c_name."/".$c_name."_".$c_layout."_".$c_version.".php";
	}

    

	public function getComponentFile($component_name){
		$component_file_name = $this->resolveComponentFilePath($component_name);
		

		$component_webengine_path = $this->getWebengineComponentPath()."".$component_file_name;
		$component_site_path = $this->getSiteComponentPath()."".$component_file_name ;

		//bd($component_webengine_path);
		//bd($component_site_path);
		//bd(file_exists($component_webengine_path));

		if(file_exists($component_site_path)){
			return $component_site_path;
		}
		elseif(file_exists($component_webengine_path)){
			return $component_webengine_path;
		}
		else{
			wire()->error("Komponenta $component_name nemá požadovaný soubor šablony.");
		}


	}

    public function getWebengineComponentPath($component_name = null){
		return wire()->modules->get('webengine')->webengine_path."templates/components/";
	}

	public function getSiteComponentPath($component_name = null){
		return wire()->modules->get('webengine')->site_path."templates/components/";
	}



}

