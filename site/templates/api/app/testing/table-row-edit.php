<?php namespace ProcessWire;  ?>

<?php 

 

    if(wire("input")->is("POST")){
        
        $page->setAndSave([
            'title' => wire("input")->post("title"),
            'pocet' => wire("input")->post("pocet")
        ]);
        wire("session")->redirect(wire("input")->post("redirect"));

    }

    $res = $page->cloneResource();
    $back = $res->setRouter("app/testing/table-row")->update()->getLiveUrl();
    $edited = $res->setTempVal("rowclass","edited")->update()->getLiveUrl();;

?>

<tr class="page" id="here">    
    <td colspan="4">    
        <form hx-post="<?=wire("input")->url?>" hx-target="closest tr" hx-swap="outerHTML" hx-include="closest tr">
            <input type="text" id="title" name="title" value="<?=$page->title?>"  required>
            <input type="number" min="1" max="1000" id="pocet" name="pocet" value="<?=$page->pocet?>">    
            <input type="hidden" id="redirect" name="redirect" value="<?=$edited?>">
            <input type="hidden" id="id" name="id" value="<?=$page->id?>">
            <button>Save</button>     
            <a href="x"  hx-get="<?=$back?>" hx-target="closest tr" hx-swap="outerHTML" >Zpět</a>       
        </form>
        
    </td>
</tr>
