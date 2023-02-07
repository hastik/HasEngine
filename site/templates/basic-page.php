<?php namespace ProcessWire; 

// Template file for pages using the “basic-page” template

	//sleep(3);


?>


<div id="content" style = "border: 3px dashed black; padding: 2rem;">
	Basic page content <br>


	<?php
	
		if($input->get()["generate"]){
			$count = $input->get()["generate"];
			echo "generuji stránek: ". $count;

			$parentPage = wire("pages")->get("/test");
			if($parentPage){
				for($i=0; $i<$count;$i++){
					$p = new Page(); // create new page object
					$p->template = 'basic-page'; // set template
					$p->parent = $parentPage; // set the parent
					$p->name = 'generated'.$i.microtime(); // give it a name used in the url for the page
					$p->title = 'Generováno s číslem '.$i." - ".microtime(); // set page title (not neccessary but recommended)
					$p->pocet = rand(1,100);
					$p->save();
				}
			}
			

		}

	?>
		
	<?php foreach($page->get("_hasUrlSegments") as $segment): ?>
		<?=$segment?>,
	<?php endforeach; ?>

			<br>
			
	<?php foreach($page->get("_hasGet") as $key => $value ): ?>
		<?=$key?> = <?=$value?>,
	<?php endforeach; ?>



	<br><br>

</div>	

