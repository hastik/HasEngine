<?php namespace ProcessWire;


class DefaultPage extends Page {}

class HomePage extends DefaultPage{

    public $test;

    public function __construct(){
        $this->test = "test";
        $this->get("_hypermedia")->url = "test";
    }
    public function getTest(){
        return "test";
    }
}