<?php namespace ProcessWire; ?>

<?php 

    if(wire("input")->is("POST")){
        
        $p = $page;
        $p->setAndSave([
            'note' => wire("input")->post("note")
          ]);
        wire("session")->redirect(wire("input")->post("redirect"));

    }

    
?>


<?php
    $chat = $page;    
?>



<?php if($chat->resource->getVal("edit")): ?>

    <?php    
        
        $resource = $chat->cloneResource()->update();
        
        $resource->removeVal("edit")->update();
        
        $link_back = $resource->getLiveUrl();
    ?>



    <div class="chat_note" id="note">
        <form hx-post="<?=$chat->resource->getLiveUrl()?>">
            <textarea name="note" id="note"><?=$chat->note?></textarea>
            <input type="hidden" name="redirect" value="<?=$link_back?>">
            <button>Uložit</button>
        </form>
        <a hx-get="<?=$link_back?>" hx-target="#note">Zpět</a>
    </div>

<?php else: ?>


    <?php    

    $resource = $chat->cloneResource();
    $resource->setGetVal("edit",1)->update();
    $link_edit = $resource->getLiveUrl();
    ?>

    <div class="chat_note" id="note">
        <div class="chat_note_inner"><?=$chat->note?></div>
        <a hx-get="<?=$link_edit?>" hx-target="#note">Odkaz na editaci</a>
    </div>

<?php endif; ?>