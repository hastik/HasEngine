<?php namespace ProcessWire;


	class hResourcer {

        public static $less_strings = array();
        public static $less_files = array();


        public static function addLessString($string,$id){
            
            if(!$id){
                self::$less_strings["global"].=$string; 
            }
            else{
                if(isset(self::$less_strings[$id])){
                    self::$less_strings[$id].=$string;
                }
                else{
                    self::$less_strings[$id]=$string;
                }
                
            }
            
        }
        public static function addLessFile($file_path,$id){
            self::$less_files[$id] = $file_path;
        }

        public static function parseAllLess(){
            bd(self::$less_strings);
            foreach(self::$less_strings as $string){
                Templater::$less->addStr($string);
            }
            foreach(self::$less_files as $file){
                Templater::$less->addFile($file);
            }
            
        }

        public static function getCss(){
            self::parseAllLess();
            return Templater::$less->getCss();
        }

    }