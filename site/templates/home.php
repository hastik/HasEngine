<?php namespace ProcessWire;
  //include_once 'hypermedia.php';

// Template file for “home” template used by the homepage



$hypermedia = new Hypermedia; 

?>
<script src="https://unpkg.com/htmx.org@1.8.5"></script>


<style>


</style>
<link rel="stylesheet" href="https://unpkg.com/@picocss/pico@1.*/css/pico.min.css">

<style>

	table td{ font-size: 0.6em;}

	.htmx-indicator{
    display:none;
}
.htmx-request .my-indicator{
    display:inline;
}
.htmx-request.my-indicator{
    display:inline;
}
</style>

<body hx-ext="preload" >
<div id="content">
	Homepage content 
</div>	




<?php 
	
	//$pg = wire()->pages->get("/o-nas");
	//$fragment = $hypermedia->setFragment("/o-nas/table/item",
	//["selector" => "published=0,children.count>0"],["cache"=>20])->includeFragment($pg);
	//$cache->save("x",$fragment,5);
	//echo $fragment;
	//echo $fragment;

?>






<div class="grid">
  <div>
	<h2>Foreach One file</h2>


	<?php 

	wire("cache")->deleteAll();

	$time_start = microtime(true);
	$fragment = $hypermedia->get("/test/test/table-foreach",
	["selector" => "published=0,children.count>0",
		"onpage" => "50",
		"page"=>"1"],"wire")->fetch();
	
	bd($hypermedia);
	
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	dump($time);
	echo $fragment;		


?>





  </div>
  <div>


  <h2>Foreach Include</h2>


  <?php  
	$time_start = microtime(true);
	$fragment = $hypermedia->get("/test/test/table-includes",
	["selector" => "published=0,children.count>0",
		"onpage" => "50",
		"page"=>"1"],"wire")->fetch();
	
	//bd($hypermedia);
	
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	dump($time);
	echo $fragment;		


?>







  </div>
  <div>
  <h2>Foreach Wire</h2>


  <?php 
	$time_start = microtime(true);
	$fragment = $hypermedia->get("/test/test/table-wire",
	["selector" => "published=0,children.count>0",
		"onpage" => "50",
		"cache"=>"30"],"wire")->fetch();
	//$cache->save("x",$fragment,5);
	
	//bd($hypermedia);
	
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	dump($time);
	echo $fragment;		


?>


  </div>


  <div>
  <h2>Foreach WireOnload</h2>


  <?php 
	$time_start = microtime(true);
	$fragment = $hypermedia->get("/test/test/table-onload",
	["selector" => "published=0,children.count>0",
		"onpage" => "50",
		"cache"=>"60"],"wire")->fetch();
	
	//bd($hypermedia);
	
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	dump($time);
	echo $fragment;		


?>


  </div>
  
</div>



<?php


	$fragment = $hypermedia->get("/produkty/table/item?selector=published=0,children.count>0&onpage=50&page=1&cache=20","curl")->fetch();//->fetch();
	bd($hypermedia);

	echo $fragment;


	$produkt = wire()->pages->get("/kontakt");
	$fragment = $hypermedia->get("/kontakt/table/item?selector=published=0,children.count>0&onpage=50&page=1&cache=20",$produkt,"include")->fetch();//->fetch();
	bd($hypermedia);

	echo $fragment;

	
?>

<hr>
