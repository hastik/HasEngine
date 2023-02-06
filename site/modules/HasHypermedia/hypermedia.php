<?php namespace ProcessWire;

class Hypermedia{

    public $name;
    public $url;
    public $query;
    public $get;
    public $endpoint;

    function setFragment($name,$url,$query){
        $this->name = $name;
        $this->query = $query;
        $this->url = $url;

        $this->get = http_build_query($query);

        $this->endpoint = $url."?".$this->get;

        return $this;
    }

    function renderFragment($name,$url,$query){

        $this->setFragment($name,$url,$query)->render();

    }

    function fetch(){
        return wire()->pages->getByPath($this->endpoint, ['allowUrlSegments' => true, 'allowGet' => true])->render().$this->fetchHelpers();
    }


    function render(){

        echo $this->fetch();
        $castedPage = wire()->pages->getByPath($this->endpoint, ['allowUrlSegments' => true, 'allowGet' => true]);
        echo $castedPage->render();
        echo $this->fetchHelpers();
        
        return $this;
    }

    function fetchHelpers(){
        
        $helpers = "";
        $helpers .="<br>***************<br>";
        $helpers .="<div><a href='".$this->endpoint."'>Odkaz ".$this->endpoint."</a> </div>";
        $helpers .= "<br>***************<br>";

        return $helpers;
        
    }

}

?>

