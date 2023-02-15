<?php namespace ProcessWire;


trait HypermediaCaster{
    public $hyper_input_url;

    public function renderMe(){
        dump($this);
        $this->hyper_input_url = "Trait!"." ".$this->hyper_hash;
        echo $this->hyper_input_url;
        dump("halo");
    }
}
