<?php namespace ProcessWire;  ?>

<?php 

$resource = $page->cloneResource();


$origin_live_url = $resource->getLiveUrl();
$origin_casted_url = $resource->getCastedUrl();

//dump($resource->master_data["get"]);

$resource->setTempVal("origin",$origin_casted_url);
//$resource->setTempVal("live_origin",$origin_live_url);
$resource->update();

//dump($resource->master_data["get"]);
//dump($resource->getCastedUrl());

$source_euid = $page->resource->hashhash.$page->id;
$url_euid =$page->resource->master_resource->euid ?? $page->resource->master_resource->euid ?? null;
//bd($url_euid);
//bd($page->resource);
?>


<?php if($url_euid==$source_euid):?>
    
    <?php include "table-row-edit.php" ?> POKRACovat ve ypracování postup - předání správného redirectu

<?php else: ?>

<tr class="page" >
    <td><?=$url_euid?> == <?=$source_euid?></td>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td>
        <?php if (wire("user")->isLoggedin()): $resource->setEditVal($source_euid); ?>    
            <a hx-get="<?=$resource->getLiveUrl()?>" hx-target="closest tr" hx-swap="outerHTML"  href="<?=$resource->getCastedUrl()?>">Editovat</a>
        </td>
        <?php endif; ?>
</tr>

<?php endif; ?>