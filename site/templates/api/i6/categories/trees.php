<?php namespace ProcessWire;

    $query = "template='category',role_text='base'";
    $trees = $page->children($query);
    
    $target = "table-".$page->resource->hash;
?>



<table role="grid" id="<?=$target?>" class="table table-striped">
    <thead>
        <tr>
            <td>Id</td>
            <td>Název</td>
            <td>Vytvořeno</td>
            <td>Akce</td>
            
        </tr>
    </thead>

    <tbody id="tbody">

    <?php foreach($trees as $tree): ?>

    <?php         
        $resource = $page->cloneResource();
        $resource->setRouter("i6/categories/r-i6_categories_tree")->setQueryVal("treeid",$tree->id);
        $resource->update();
    ?>

    <tr  class="page tablerow" >
        <td><?=$tree->id?></td>
        <td><?=$tree->title?></td>
        <td><?=hmDateTime($tree->created)?></td>
        <td class="action">    
            <a hx-get="<?=$resource->getLiveUrl()?>" hx-target="#content" hx-select="#content" hx-swap="innerHTML" hx-push-url="<?=$resource->getLiveUrl()?>" href="<?=$resource->getLiveUrl()?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye align-middle me-2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>        
        <input type="hidden" name="pageid" id="pageid" value="<?=$page->id?>">
        <a hx-confirm="Are you sure?" hx-post="<?=$page->resource->getLiveUrl()?>" hx-target="closest tr" hx-swap="outerHTML swap:1s" hx-include="#pageid" >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash align-middle"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a>
        
    </td>
</tr>


    <?php endforeach; ?>
</tbody>

</table>