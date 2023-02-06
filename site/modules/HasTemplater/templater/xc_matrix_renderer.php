<?php namespace ProcessWire;

    include "xc_component.php";

    $indexFrom = 0;

	function repeaterItemsToTree($arr, $currentLevel = 0) {
        global $indexFrom;
		$root = array();
	  
        $i=0;
		foreach ($arr as $elem){
         
          if($i>=$indexFrom){
            if ($elem->depth == $currentLevel) {
                $root[] = $elem;
                $indexFrom++;
                
              } else if ($elem->depth == $currentLevel + 1) {
                $root[count($root)-1]->subitems = repeaterItemsToTree($arr,$elem->depth);      
              }
              else{
                  return $root;
              }
          }
		  
          $i++;
		}
	  
		return $root;
	  }

    function componentMatrixRender($matrix_field_name = "xcf_content_matrix"){
		$matrix_array = wire()->page->$matrix_field_name;
		$matrix_tree = repeaterItemsToTree($matrix_array);

        processMatrixArrayRender($matrix_tree);

	}


    function processMatrixArrayRender($array, $depth = 0){

        foreach($array as $item){
            xcComponent($item->getMatrixInfo()["type"],$item);
        }    
    }