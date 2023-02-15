<?php namespace ProcessWire;

    trait HypermediaObjectConstructor {

        public $char_table;

        public function initSelf(){
            $this->test = "Object Construktor initSelf";
        }

        public function initCharTable(){
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


    }