<?php namespace ProcessWire;  ?>

<?php 

 

    if(wire("input")->is("POST")){
        
        $page->setAndSave([
            'title' => wire("input")->post("title"),
            'pocet' => wire("input")->post("pocet")
        ]);

        wire("session")->redirect(wire("input")->post("redirect"));

    }


?>

<tr class="page" id="here">
    
    <td colspan="4">
    
        <form method="post">
            <input type="text" id="title" name="title" value="<?=$page->title?>">
            <input type="text" id="pocet" name="pocet" value="<?=$page->pocet?>">    
            <input type="hidden" id="redirect" name="redirect" value="<?="ds"?>">
            <input type="hidden" id="id" name="id" value="<?=$page->id?>">
            <input type="submit" hx-post="<?=wire("input")->url?>" hx-target="closest tr" hx-swap="outerHTML" hx-include="closest tr" value="Uložit" ></input>
        </form>

    </td>

    

        
    </td>
    
   
    

</tr>
