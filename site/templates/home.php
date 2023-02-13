<?php namespace ProcessWire;
  
?>

<style>

	.topbar{
		background: #17a2b8;
		color: white;
	}

	.main-navigation{
		background: white;
	}

	html, body{
		background: #F2F7FD;
	}

	.included{
	
	}

</style>

<div class="topbar">
	<div class="logoname">AdminPanel</div>
</div>
<div class="navigation">
	<div class="main-navigation">
		<a href="/home-test">Dashboard</a>
		<a href="/home-test">AI</a>
		<a href="/home-test">Tests</a>
		<a href="/home-test">3rd parties</a>
	</div>

	<div class="sub-navigation">
		<a href="/home-test">Přehled</a>
		<a href="/home-test">Úkoly</a>
		<a href="/home-test">Projekty</a>
		<a href="/home-test">Firmy</a>
	</div>
</div>


<div class="included">
	<?php include_once 'home_test.php'; ?>
</div>

