<?php namespace ProcessWire;  ?>

<?php 

?>


<tr class="page" >
    <td></td>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td><a hx-get="<?=$page->url?>/r-test_table-row-edit" hx-target="closest tr" hx-swap="outerHTML"  href="x">Editovat</a></td>
</tr>