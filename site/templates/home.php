<?php namespace ProcessWire;

// Template file for “home” template used by the homepage

class Hypermedia{

	public $name;
	public $url;
	public $query;
	public $get;
	public $endpoint;

	function setFragment($name,$url,$query){
		$this->name = $name;
		$this->query = $query;
		$this->url = $url;

		$this->get = http_build_query($query);

		$this->endpoint = $url."?".$this->get;

		return $this;
	}

	function renderFragment($name,$url,$query){

		$this->setFragment($name,$url,$query)->render();

	}

	function fetch(){
		return wire()->pages->getByPath($this->endpoint, ['allowUrlSegments' => true, 'allowGet' => true])->render().$this->fetchHelpers();
	}


	function render(){
	
		echo $this->fetch();
		$castedPage = wire()->pages->getByPath($this->endpoint, ['allowUrlSegments' => true, 'allowGet' => true]);
		echo $castedPage->render();
		echo $this->fetchHelpers();
		
		return $this;
	}

	function fetchHelpers(){
		
		$helpers = "";
		$helpers .="<br>***************<br>";
		$helpers .="<div><a href='".$this->endpoint."'>Odkaz ".$this->endpoint."</a> </div>";
		$helpers .= "<br>***************<br>";

		return $helpers;
		
	}

}



$hypermedia = new Hypermedia;

?>
<script src="https://unpkg.com/htmx.org@1.8.5"></script>
<script src="https://unpkg.com/htmx.org/dist/ext/preload.js"></script>


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


<div class="">


<table>


<?php

	if($cache->get("x")){
		echo $cache->get("x");
	}
	else {
		echo "neni cache";
		$fragment = $hypermedia->setFragment("testprodukt","/produkty/table/item",
		["selector" => "published=0,children.count>0",
			"onpage" => "50",
			"page"=>"1"],["cache"=>20])->fetch();
		$cache->save("x",$fragment,5);
		echo $fragment;


		//$hypermedia->fragment("#tasklist","/produkty/table","selector>>published=0,children.count>0)",["cache"=>60])->render();
		
	}
	
	
?>

</table>
<br>

<hr>

<?php

	if($cache->get("y")){
		echo $cache->get("y");
	}
	else {

		//$fragment = $hypermedia->prepareFragment(); 
		foreach($pages as $page){
			//$fragment->set(["id"=>$page->id])->fetchFile(); //todo fetch
			//$fragment->set(["id"=>$page->id])->include();//todo include
		}

		$fragment = $hypermedia->setFragment("/produkty/table",
		["children" => "pocet>3",
			"method"=> "get",
			"approach"=> "onload",
			"onpage" => "50",
			"page"=>"1"],["cache"=>20],["name"=>"tableproduktynahp"])->fetch();
		$cache->save("y",$fragment,5);
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