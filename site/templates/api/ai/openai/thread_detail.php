<?php namespace ProcessWire; 
    
?>

<?php 

    if(wire("input")->is("POST")){
        //sleep(1);

        //dump($page->participants);exit;

        $p = new Page(); // create new page object
        $p->template = 'message'; // set template
        $p->parent = $page; // set the parent
        $p->title = 'message_'.microtime(true); // set page title (not neccessary but recommended)
        $p->text = wire("input")->post("text");
        $p->owner = wire("input")->post("owner");
        $p->save();

        

        if($page->participants->get("name=openai")){

            $openai_user = $page->participants->get("name=openai");
            $openai_result = wire("openai")->fetch(wire("input")->post("text"));

            $p = new Page(); // create new page object
            $p->template = 'message'; // set template
            $p->parent = $page; // set the parent
            $p->title = 'message_'.microtime(true); // set page title (not neccessary but recommended)
            $p->text = $openai_result['choices'][0]['text'];
            $p->owner = $openai_user;
            $p->save();
        }

        wire("session")->redirect($page->resource->getLiveUrl());

    }

    $res = $page->cloneResource();
    $back = $res->setRouter("app/testing/table-row")->update()->getLiveUrl();
    $edited = $res->setTempVal("rowclass","edited")->update()->getLiveUrl();;

?>



<?php
    $chat = $page;
    $messages = $page->getMessages();
    //bd($messages);


?>





<?php Templater::partialBegin("content"); ?>



    <div class="card nerrow">
            <div class="card-inner" id="messages">
                <h4><?=$chat->title?></h4>

                <h5>Poznámka</h5>
              
                <?=$note = $chat->newSourceFromRouterAndPage("ai/openai/thread/detail/note",$chat)->render()?>
          

                <div class="chat_participants participants">
                    <?php foreach($chat->participants as $user): ?>
                        <span class="participant"><?=$user->name?></span>
                    <?php endforeach; ?>
                </div>

                <div class="messages clearfix" >
                    <?php foreach($messages as $message): ?>
                    <?php $user_class = $message->owner == wire('user') ? "me" : "else"; ?>

                        <div class="messages_message message <?=$user_class?>">
                            <div class="message_info">
                                <span class="message_info_author"><?=$message->owner->name?></span>
                                <span class="message_info_date"><?=$message->created?></span>                                
                            </div>    
                            <div class="message_text"><?=$message->text?></div>
                            
                        </div>

                    <?php endforeach; ?>
                </div>
                
                <?php //foreach($page->getMessages() as $message): ?>

                <div class="message_form">                
                    <form method="post" hx-post="<?=$chat->resource->getLiveUrl()?>" hx-target="#messages" hx-select="#messages" hx-swap="outerHTML">
                        <div><textarea  id="text" name="text" rows="4" cols="100" required></textarea></div>
                        <input type="hidden" name="owner" value="<?=wire('user')?>">
                        
                        <div>                            
                        <button type="submit" class="button">                                                
                            Odeslat</button>
                        </div>
                        <img src="https://media4.giphy.com/media/3oEjI6SIIHBdRxXI40/giphy.gif?cid=ecf05e471890e3aqc1mvnarkfu7k65jg4qsjyv1ubujslm02&rid=giphy.gif&ct=g" id="spinner" class="htmx-indicator">
                    </form>
                </div>


            </div>
    </div>

<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../../corpus.template.php"; ?>
                   