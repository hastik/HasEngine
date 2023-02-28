<?php namespace ProcessWire;  ?>

<?php 

$resource = $page->cloneResource();
$resource->setRouter("app/testing/table-row-edit");
$resource->update();
$class = $page->resource->getTempVal("rowclass")

?>




<tr hx-ext="preload" class="page tablerow <?php if($class):?> edited <?php endif;?>" <?php if($class):?> _="init toggle .edited" <?php endif;?> >
    <td></td>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td>
        <?php if (wire("user")->isLoggedin() || true):  ?>    
            <a hx-get="<?=$resource->getLiveUrl()?>" hx-target="closest tr" hx-swap="outerHTML" href="x">Editovat</a>
        </td>
        <?php endif; ?>
</tr>
