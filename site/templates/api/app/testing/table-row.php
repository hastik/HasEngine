<?php namespace ProcessWire;  ?>

<?php 

$resource = $page->cloneResource();
$resource->setRouter("app/testing/table-row-edit");
$resource->update();

?>


<tr class="page" >
    <td></td>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td>
        <?php if (wire("user")->isLoggedin()):  ?>    
            <a hx-get="<?=$resource->getLiveUrl()?>" hx-target="closest tr" hx-swap="outerHTML" href="x">Editovat</a>
        </td>
        <?php endif; ?>
</tr>
