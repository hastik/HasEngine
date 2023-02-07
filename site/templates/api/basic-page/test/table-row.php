<?php namespace ProcessWire;  ?>

<?php 

?>


<tr class="page" >
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td><a hx-get="<?=$page->url?>/test/table-row-edit" hx-target="closest tr" hx-swap="outerHTML"  href="x">Editovat</a></td>
</tr>