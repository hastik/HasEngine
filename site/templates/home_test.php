<?php namespace ProcessWire;
  //include_once 'hypermedia.php';

// Template file for “home” template used by the homepa



function printTime($time){
	echo "<div class='time'>$time</div>";
}


$hmx = wire("hmx"); 
bd($page);
echo $page->title;
echo $page->getTest();

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



//////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////





//////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////


// Template file for pages using the “basic-page” template

	//sleep(3);

	//$hp = wire("pages")->get("/");

	$pagex = wire("hypermedia")->getWired("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60");
	//dump($pagex);
	//dump("Basic page");
	echo $pagex->render();
	

	$pageii = wire("pages")->getByPath("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60",['allowUrlSegments' => true, 'allowGet' => true]);
	$pagei = wire("hypermedia")->getWiredFromPage("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60",$pageii);
	//dump($pagei);
	echo $pagei->include();


	$pageraw = wire("pages")->getRaw("/test/generated0","title,url,pocet",['allowUrlSegments' => true, 'allowGet' => true]);
	$pageiraw = wire("hypermedia")->getWiredFromArray("/test/generated0/r-basic-page_test_table-row/q-selector=template=basic-page&limit=1?selector=published%3D0%2Cchildren.count>0&onpage=50&limit=1000&cacshe=60",$pageraw);
	//dump($pageiraw);
	echo $pageiraw->include();
?>


<div class="grid">

	<div>
		<h3>Rendered - List of foreached pages</h3>
	
		<?php 
			$listForeached = wire("hypermedia")->getWired("/test/r-basic-page_test_table-foreached/q-cache=0?cache=0");
			$out = $listForeached->render();
			echo $listForeached->timeReport();
			echo $out;
		?>

	</div>


	<div>
		<h3>Rendered - List of included pages</h3>
	
		<?php 
			$listIncluded = wire("hypermedia")->getWired("/test/r-basic-page_test_table-included/q-cache=0?cache=0");
			$out = $listIncluded->render();
			echo $listIncluded->timeReport();
			echo $out;
		?>

	</div>

	<div>
		<h3>Cached Rendered - List of included pages</h3>
	
		<?php
		
			$time_start = microtime(true);
			if(wire("cache")->get("test")){
				$output =  wire("cache")->get("test");
				$time = microtime(true) - $time_start;
				echo "<div class=time>".round($time,4)."</div>".$output;
			}
			else{
				$listIncluded = wire("hypermedia")->getWired("/test/r-basic-page_test_table-included/q-cache=0?cache=0");
				$out = $listIncluded->render();
				echo $listIncluded->timeReport();
				echo $out;
				wire("cache")->save("test",$out,30);
			}
			
		?>

	</div>

	<div>
		<h3>Rendered - List of Rendered Pages</h3>
	
		<?php 
			$listWired = wire("hypermedia")->getWired("/test/r-basic-page_test_table-wire/q-cache=0?cache=0");
		    $out = $listWired->render();
			echo $listWired->timeReport();
			echo $out;
		?>

	</div>

	<div>
		<h3>Rendered - List of Included Arrays</h3>
	
		<?php 
			//$listArr = wire("hypermedia")->getWired("/test/r-basic-page_test_table-array/q-cache=0?cache=0");
		    //$out = $listArr->render();
			//echo $listArr->timeReport();
			//echo $out;
		?>

	</div>

</div>



<div id="content" style = "border: 3px dashed black; padding: 2rem;">
	Homepage content ... <br>


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