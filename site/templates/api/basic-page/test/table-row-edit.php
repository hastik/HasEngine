<?php namespace ProcessWire;  ?>

<?php 

    if(wire("input")->is("POST")){

        
        $page->setAndSave([
            'title' => wire("input")->post("title"),
            'pocet' => wire("input")->post("pocet")
        ]);

        wire("session")->redirect($page->url."/test/table-row");

    }

?>

<tr class="page" id="here">
    <td><input type="text" id="title" name="title" value="<?=$page->title?>"></td>
    <td><input type="text" id="pocet" name="pocet" value="<?=$page->pocet?>"></td>
    <td>
        <input type="hidden" id="id" name="id" value="<?=$page->id?>">
        <a href="x" hx-post="<?=wire("input")->url?>" hx-target="closest tr" hx-swap="outerHTML" hx-include="closest tr" >Uložit</a>
    </td>
   
</tr>