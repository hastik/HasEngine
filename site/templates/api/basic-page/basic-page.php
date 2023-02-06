<?php namespace ProcessWire; 

// Template file for pages using the “basic-page” template

wire->get("/basic-page/table?title%='a'")->render();

?>


<?php wire->get("/fragment/task/query/table/query")->render(); ?>

<div id="content">
	Basic page content from API folder

	<a href="/fragment/task/372/tablerow">
	<a href="/fragment/task/query/table/query">
	<a href="/ukoly/prvni-task/prvni-podtask/tablerow/edit">
	
	Testovací scénáře s X stránkami

	1. Klasický - zavolám si wire get a budu iterovat a rovnou vypisovat
	2. Alfa - zavolám wire get a budu iterovat rendery výsledků
	3. Beta - zavolám si wire get na všechny výsledky, kde jsem to iteroval a vypisoval
	4. Gama - zavolám si wire get na všechny výsledky, kde jsem iteroval rendery výsledků

	Otázky
	bere wire get i segmenty?
	bere wire get i otazník a get parametry?
	Jak volam fragment tabulky? Např. chci tabulku všech mých úkolů?
	<a href="/tasks/list/table?query='user=4732,finished=false'">

</div>	

