<?php namespace ProcessWire; 


$hm = $page->_hm;
$limit = $hm->getQueryData("limit");

?>


<table role="grid" id="swapper">

<thead>
	<tr>
		<td>Name</td>
		<td>Count</td>
		<td>Edit</td>
	</tr>
</thead>
	<tbody>
	<tr id="replace" 
		hx-get="/test/r-test_table-foreach/q-selector_eq_template_eq_basic-page_am_limit_eq_<?=$limit?>?cache=50"
		hx-trigger="load delay:1s"
		hx-target="closest table"
		hx-swap="outerHTML">
		<td colspan="3">
		<div style="text-align: center"><img class="my-indicator  htmx-indicator" src="https://media.tenor.com/wpSo-8CrXqUAAAAi/loading-loading-forever.gif" style="width:2em;"></div>
		</td>
	</tr>
	</tbody>

	</table>


