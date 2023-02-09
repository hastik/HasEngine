<?php namespace ProcessWire;
  //include_once 'hypermedia.php';

// Template file for “home” template used by the homepage


function printTime($time){
	echo "<div class='time'>$time</div>";
}


$hmx = wire("hmx"); 

?>
<script src="https://unpkg.com/htmx.org@1.8.5"></script>


<style>


</style>
<link rel="stylesheet" href="https://unpkg.com/@picocss/pico@1.*/css/pico.min.css">

<style>

	.time{
		background:yellow;
		color: black;
		padding:1em;
		font-size: 0.6em;
		font-weight: bold;
	}

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
	$limit = 1;
	$pg = wire()->pages->get("/o-nas");
	//$fragment = $hypermedia->setFragment("/o-nas/table/item",
	//["selector" => "published=0,children.count>0"],["cache"=>20])->includeFragment($pg);
	//$cache->save("x",$fragment,5);
	//echo $fragment;
	//echo $fragment;

?>






<div class="grid">

<div>


  <h2>Foreach Include</h2>


  <?php  

	/*$fragment = $hypermedia->get("/test/test/table-includes",
	["selector" => "published=0,children.count>0",
		"onpage" => "50",
		"page"=>"1"],"wire")->fetch();
	
	echo $hypermedia->printTime();
	echo $hypermedia->printUrl();

	echo $fragment;		
*/

	$pagex = wire("pages")->getByPath("/test",['allowUrlSegments' => true, 'allowGet' => true]);

	$fragment = $hmx->get("/test/r-test_table-includes/q-selector=template=basic-page&limit=$limit?cache=50",$pagex,"include");
	echo $fragment->printTime();
	echo $fragment->render();


?>







  </div>

  <div>
	<h2>Foreach One file</h2>


	<?php 

$fragment = $hmx->get("/test/r-test_table-foreach/q-selector=template=basic-page&limit=$limit?cache=50","wire");
bd($hmx);
echo $fragment->printTime();
echo $fragment->render();


?>





  </div>
  
  <div>
  <h2>Foreach Wire</h2>


  <?php  
  
	$fragment = $hmx->get("/test/r-test_table-wire/q-selector=template=basic-page&limit=$limit?cache=50","wire");
	bd($hmx);
	echo $fragment->printTime();
	echo $fragment->render();
	
	
	/*echo $hypermedia->printTime();
	echo $hypermedia->printUrl();
	echo $fragment;		
	*/


?>


  </div>


  <div>
  <h2>Foreach Onload->Foreach</h2>


  <?php 

	$fragment = $hmx->get("/test/r-test_table-onload/q-selector=template=basic-page&limit=$limit?cache=50","wire");
	bd($hmx);
	echo $fragment->printTime();
	echo $fragment->render();


	?>

 



  <?php 



	/*$time_start = microtime(true);
	$fragment = $hypermedia->get("/test/test/table-onload",
	["selector" => "published=0,children.count>0",
		"onpage" => "50",
		"cache"=>"60"],"wire")->fetch();
	
	//bd($hypermedia);
	
	echo $hypermedia->printTime();
	echo $hypermedia->printUrl();
	echo $fragment;		
*/

?>


  </div>
  
</div>



<hr>
