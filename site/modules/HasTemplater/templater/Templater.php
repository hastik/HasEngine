<?php namespace ProcessWire;

	include "xc_matrix_renderer.php";
	
   
	class Templater {

		public static $sections = array();
		public static $partials = array();

		public static $partials_lifo = array();
		
		public static $slots = array();
		public static $slots_lifo = array();

		public static $current_partial;

		public static $less;

		public static function partialBegin($name){
			self::$partials_lifo[]=$name;
			sectionStart();
		}

		public static function setLess($sless){
			self::$less = $sless;
		}


		public static function currentPartial(){
			if(count(self::$partials_lifo)==0){
				return false;
			}
			else {
				return self::$partials_lifo[count(self::$partials_lifo)-1];
			}
		}

		public static function partialEnd(){
			$content = sectionEnd();

			$name = array_pop(self::$partials_lifo);
			self::$slots_lifo = array();
			//self::$slots = array();
			self::addPartial($name,$content);

			return $content;
		}

		public static function slotOpen($name){
			self::$slots_lifo[]=$name;
			sectionStart();
		}

		public static function slotClose(){
			
			$content = sectionEnd();
			$name = array_pop(self::$slots_lifo);

			self::addSlot($name,$content);

		}

		public static function addPartial($name,$content){
			self::$partials[$name] = $content; 
		}

		public static function removePartial($name){
			unset(self::$partials[$name]);
		}

		public static function getPartial($name){
			if(isset(self::$partials[$name])){
				return self::$partials[$name];
			}
			else return false;
			

		}

		public static function addSlot($name,$content){
			self::$slots[self::currentPartial()][$name] = $content; 
		}

		public static function getSlot($name){
			
			if(self::currentPartial()){
				if(isset(self::$slots[self::currentPartial()][$name])){
					return self::$slots[self::currentPartial()][$name];
				}
				else{
					return "Neexistující partial";
				}
			}
			else{
				return "Slot bez partialu";
			}
			

		}

	}

    
	function sectionStart(){
		ob_start();
	}

	function sectionEnd(){
		$buffer = ob_get_contents();
		@ob_end_clean();
		return $buffer;
	}


	Templater::setLess(wire()->modules->get('Less'));