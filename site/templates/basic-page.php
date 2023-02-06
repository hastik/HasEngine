<?php namespace ProcessWire; 

// Template file for pages using the “basic-page” template

	//sleep(3);


?>


<div id="content" style = "border: 3px dashed black; padding: 2rem;">
	Basic page content <br>

	<?php bd($page); ?>

		
	<?php foreach($page->get("_hasUrlSegments") as $segment): ?>
		<?=$segment?>,
	<?php endforeach; ?>

			<br>
			
	<?php foreach($page->get("_hasGet") as $key => $value ): ?>
		<?=$key?> = <?=$value?>,
	<?php endforeach; ?>



	<br><br>

</div>	

