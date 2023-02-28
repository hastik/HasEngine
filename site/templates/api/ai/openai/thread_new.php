<?php namespace ProcessWire; ?>

<?php 
    if(wire("input")->is("POST")){
        
        //dump(wire("input")->post("userids"));exit;

        $p = new Page(); // create new page object
        $p->template = 'chat'; // set template
        $p->parent = $page; // set the parent
        $p->title = wire("input")->post("name"); // set page title (not neccessary but recommended)
        $p->participants = wire("input")->post("userids");
        $p->owner = wire("input")->post("owner");
        $p->note = wire("input")->post("note");
        $p->save();

        wire("session")->redirect($p->url."/r-ai_openai_thread_detail");

    }

  
?>



<?php
    
    $users = wire('users')->find("template=user");
    
?>





<?php Templater::partialBegin("content"); ?>



    <div class="card">
            <div class="card-inner" >
                <h4><?=$page->title?></h4>

                <div class="new_thread_form" id="newthread" >
                    <form method="post" hx-post="<?=$page->resource->getLiveUrl()?>" hx-target="#content" hx-select="#content" hx-swap="innerHTML" hx-include="#newthread">
                        <div class="formline">
                            <label>Pojmenování diskuzního vlákna: </label>
                            <input type="text" name="name" id="name" required>
                        </div>

                        <div class="formline">
                        <?php $i=0; foreach($users as $user): $i++;?>
                            <input type='checkbox' name='userids[]' 
                                value='<?=$user->id?>' 
                                id="option<?=$i?>"
                                <?=$user==wire('user') ? "checked" : ""?>
                                ><label for="option<?=$i?>"><?=$user->name?></label>
                        <?php endforeach; ?>
                        </div>
                        <div class="formline">
                            <textarea name="note" id="note"></textarea>
                        </div>
                        <div class="formline">
                            <input type="hidden" name="owner" value="<?=wire('user')?>">
                            <button type="submit" class="button">Odeslat</button>
                        </div>
                    </form>
                </div>


            </div>
    </div>

<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../../corpus.template.php"; ?>
                   