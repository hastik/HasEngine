<?php namespace ProcessWire;



echo "<hr>";


$newResource = $page->newSourceFromUrl("/kontakt/r-app_testing_static");

$byurl = $page->newSourceFromUrl("/kontakt/r-app_testing_static");


?>

<h3>Simple page</h3>

<?=$newResource->include();?>
<?=$byurl->include();?>