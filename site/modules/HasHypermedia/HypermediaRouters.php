<?php namespace ProcessWire;


trait HypermediaRouter{

    public $routes;

    public function __construct(){

        $this->registerRoute("openai_dashboard","/ai/openai/dashboard");
        $this->registerRoute("openai_threadstablerow","/ai/openai/threads_table-row");

        $router["openai/dashboard"] = ["router" => "app/ai/openai/"]
        
    }

    public function registerRoute($uid,$template_str){
        $this->routes[$uid]["template"] = $template_str;
    }
}
