<?php namespace ProcessWire;  ?>

<?php 

$resource = clone $page->_hypermedia;



$resource->setRouter("basic-page/test/table-row-edit")->setEditVal($resource->hash.$page->id);
$euid = wire("hypermedia")->resources["main"]->euid;
$currentid = $resource->hash.$page->id; // dát do atributu třídy

?>


<?php if($euid == $resource->hash.$page->id):?>
    
    <?php include "table-row-edit.php" ?>

<?php else: ?>

<tr class="page" >
    <td><?=$resource->hash.$page->id?> == <?=$euid?></td>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td>
        <?php if (wire("user")->isLoggedin()): ?>    
            <a hx-get="<?=$resource->getLiveUrl()?>/r-basic-page_test_table-row-edit/q-e_eq_3?d=s" hx-target="closest tr" hx-swap="outerHTML"  href="<?=$resource->getCastedUrl()?>">Editovat</a></td>
        <?php endif; ?>
</tr>

<?php endif; ?>