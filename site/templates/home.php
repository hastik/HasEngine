<?php namespace ProcessWire;
  //include_once 'hypermedia.php';

// Template file for “home” template used by the homepage



$hypermedia = new Hypermedia;

?>
<script src="https://unpkg.com/htmx.org@1.8.5"></script>


<style>

	table td{
		border: 1px solid grey;
		padding: 1rem 2rem;
	}

</style>

<body hx-ext="preload" >
<div id="content">
	Homepage content 
</div>	

<div class="castedContent" style = "background: #f2f2f2; padding: 4rem;">


<?php 
	
	$pg = wire()->pages->get("/o-nas");
	$fragment = $hypermedia->setFragment("/o-nas/table/item",
	["selector" => "published=0,children.count>0"],["cache"=>20])->includeFragment($pg);
	//$cache->save("x",$fragment,5);
	//echo $fragment;
	echo $fragment;

?>

<div class="">


<hr>

<?php 
	$fragment = $hypermedia->setFragment("/produkty/table/item",
	["selector" => "published=0,children.count>0",
		"onpage" => "50",
		"page"=>"1"],["cache"=>20])->curlFragment(null);
	//$cache->save("x",$fragment,5);
	echo $fragment;
?>

<hr>

<table>


<?php

	if($cache->get("x") && false){
		echo $cache->get("x");
	}
	else {
		echo "neni cache";
		$fragment = $hypermedia->setFragment("/produkty/table/item",
		["selector" => "published=0,children.count>0",
			"onpage" => "50",
			"page"=>"1"],["cache"=>20])->fetch();
		//$cache->save("x",$fragment,5);
		echo $fragment;


		//$hypermedia->fragment("#tasklist","/produkty/table","selector>>published=0,children.count>0)",["cache"=>60])->render();
		
	}
	
	
?>

</table>
<br>

<hr>

<?php

	if($cache->get("y") && false){
		echo $cache->get("y");
	}
	else {

		//$fragment = $hypermedia->prepareFragment(); 
		//foreach($pages as $page){
			//$fragment->set(["id"=>$page->id])->fetchFile(); //todo fetch
			//$fragment->set(["id"=>$page->id])->include();//todo include
		//}

		$fragment = $hypermedia->setFragment("/produkty/table",
		["children" => "pocet>3",
			"method"=> "get",
			"approach"=> "onload",
			"onpage" => "50",
			"page"=>"1"],["cache"=>20],["name"=>"tableproduktynahp"])->fetch();
		//$cache->save("y",$fragment,5);
		echo $fragment;


		//$hypermedia->fragment("#tasklist","/produkty/table","selector>>published=0,children.count>0)",["cache"=>60])->render();
		
	}
	
	
?>

<hr>

<?php ?>
</div>

	<div class="xz"  style="margin-top:2rem; padding:2rem; background:yellow;">
	<a hx-trigger="click" hx-get="<?=$hypermedia->endpoint?>" hx-target="#ajx" hx-swap="beforeend" preload="mouseover">Načíst</a>
	
	<div id="ajx">

		</div>

		</div>


</div>
	

</div>