<?php namespace ProcessWire;  ?>


<?php 
    if(wire("input")->is("POST")){
        
        //dump(wire("input")->post("pageid"));

        $p = wire('pages')->get(wire("input")->post("pageid"));
        //$p = wire('pages')->get(2069);
        $p->status = Page::statusUnpublished;
        $p->save();
        return "";
        //wire("session")->redirect($p->url."/r-ai_openai_thread_detail");

    }

  
?>




<?php 

    $resource = $page->cloneResource();
    $resource->setRouter("ai/openai/thread/detail");
    $resource->update();

    bd($page->updated);

?>


<tr hx-ext="preload" class="page tablerow" >
<td><?=$page->id?></td>
    <td><?=$page->title?></td>
    <td><?=hmDateTime($page->created)?></td>
    <td><?=$page->updated ? hmDateTime($page->updated) : "";?></td>
    <td><?=$page->note?></td>
    <td class="action">    
        <a hx-get="<?=$resource->getLiveUrl()?>" hx-target="#content" hx-select="#content" hx-swap="innerHTML" hx-push-url="<?=$resource->getLiveUrl()?>" href="<?=$resource->getLiveUrl()?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye align-middle me-2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>        
        <input type="hidden" name="pageid" id="pageid" value="<?=$page->id?>">
        <a hx-confirm="Are you sure?" hx-post="<?=$page->resource->getLiveUrl()?>" hx-target="closest tr" hx-swap="outerHTML swap:1s" hx-include="#pageid" >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash align-middle"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a>
        
    </td>
</tr>
