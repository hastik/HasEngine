<?php

    class Site {


        public static $conf;
        public static $data;

        public static function resolveSite(){

            $data = json_decode(file_get_contents("websites/sites.json"));
            //$data = get_object_vars($data);
            //var_dump($data);

            $sites = $data->sites;
            //var_dump($sites);




            foreach($sites as $site){
                //var_dump($site);
                if($_GET['web']==$site->getweb){
                    self::$conf = $site;
                    return $site;
                }
            }

            foreach($sites as $site){
                //var_dump($site);
                if($_SERVER['SERVER_NAME']==$site->servername){
                    self::$conf = $site;
                    return $site;
                }
            }

            self::$conf = false;
            return false;

        }

        public static function loadData(){
            $data = json_decode(file_get_contents(self::siteRootPath()."data.json"));
            //var_dump(self::siteRootPath()."data.json");
            self::$data = $data;
        }


        public static function siteRootPath(){
            return __DIR__."/websites/".self::$conf->folder."/";
        }

        public static function sitePath(){
            return __DIR__."/websites/".self::$conf->folder."/";
        }

        public static function siteComponentPath(){
            return __DIR__."/websites/".self::$conf->folder."/components/";
        }

        public static function rootPath(){
            return __DIR__;
        }

        public static function rootComponentPath(){
            return __DIR__."/components/";
        }


    }


    function component($name,$componentData = false){

        $names = explode(".",$name);
        $componentPath = Site::rootComponentPath().$names[0]."/";

        componentRender($name,$componentPath,$componentData);
    }

    function customComponent($name,$componentData = false){

        $names = explode(".",$name);
        $componentPath = Site::siteComponentPath().$names[0]."/";

        componentRender($name,$componentPath,$componentData);

    }

    function componentRender($name,$componentPath,$componentData){

        if(!$componentData){
            $dataFile =  $componentPath."/default.json";
            $data = json_decode(file_get_contents($dataFile));
        }
        else{
            global $data;
            extract(get_object_vars($componentData));
        }
        $path = $componentPath."/".$name.".php";

        include $path;

    }
