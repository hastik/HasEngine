<?php namespace ProcessWire;

if(!defined("PROCESSWIRE")) die();

include_once('./routes.php');



class HmRouter {

    private $router = array();

    public function registerRoute($string,$value){
        $this->router[$string] = $value;
    }

    public function get($string){
        return isset($this->router[$string]) ? $this->router[$string] : null;
    }

}

wire()->set("appr",new HmRouter);
wire("appr")->registerRoute("products-table","/app/i6/products/r-i6_products-table");

/** @var ProcessWire $wire */

/**
 * ProcessWire Bootstrap Initialization
 * ====================================
 * This init.php file is called during ProcessWire bootstrap initialization process.
 * This occurs after all autoload modules have been initialized, but before the current page
 * has been determined. This is a good place to attach hooks. You may place whatever you'd
 * like in this file. For example:
 *
 * $wire->addHookAfter('Page::render', function($event) {
 *   $event->return = str_replace("</body>", "<p>Hello World</p></body>", $event->return);
 * });
 *
 */