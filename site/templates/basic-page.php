<?php namespace ProcessWire; 

// Template file for pages using the “basic-page” template

	//sleep(3);


?>


<div id="content" style = "border: 3px dashed black; padding: 2rem;">
	Basic page content <br>

	<?php var_dump($page); ?>

	<?php if($page->get("_urlSegments")): ?>
		
		<?php foreach($page->get("_urlSegments") as $segment): ?>
			<?=$segment?>,
		<?php endforeach; ?>

	<?php else: ?>

		<?php foreach($input->urlSegments() as $segment): ?>
			<?=$segment?>,
		<?php endforeach; ?>

	<?php endif; ?>
			<br>

	<?php if($page->get("_get")): ?>
		
		<?php foreach($page->get("_get") as $key => $value ): ?>
			<?=$key?> = <?=$value?>,
		<?php endforeach; ?>

	<?php else: ?>

		<?php foreach($input->get() as $key => $value): ?>
			<?=$key?> = <?=$value?>,
		<?php endforeach; ?>

	<?php endif; ?>
	


	<br><br>

</div>	

