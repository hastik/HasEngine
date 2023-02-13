<?php namespace ProcessWire;


$routes = array(
    "listofmytasks" => "/r-tasks_list_mytask/q-owner=34&limit=50",
    "sidebar" => "/r-app_fragments_sidebar?live",
    "topbar" => "v1/app/fragments/topbar",
    "topbar" => ["v1/app/fragments/topbar","owner=me,limit=40,published=1","live,cache=40"],
    "topbar" => wire("hypermedia")->newUrl()->template("v1/app/fragments/topbar")->query("owner=me,limit=40,published=1")->get("live,cache=40")->give(),
);