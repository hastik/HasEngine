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

// Template file for pages using the “basic-page” template

	//sleep(3);

	//$hp = wire("pages")->get("/");

	$pagex = wire("hypermedia")->getWired("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60");
	dump($pagex);
	dump("Basic page");
	echo $pagex->render();
	

	$pageii = wire("pages")->getByPath("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60",['allowUrlSegments' => true, 'allowGet' => true]);
	$pagei = wire("hypermedia")->getWiredFromPage("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60",$pageii);
	dump($pagei);


	$pageraw = wire("pages")->getRaw("/test/generated0","title,url,pocet",['allowUrlSegments' => true, 'allowGet' => true]);
	$pageiraw = wire("hypermedia")->getWiredFromArray("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60",$pageraw)->render();
	dump($pageiraw);
?>


<div id="content" style = "border: 3px dashed black; padding: 2rem;">
	Basic page content ... <br>


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