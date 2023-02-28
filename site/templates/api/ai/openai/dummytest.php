<?php namespace ProcessWire; 
    use OpenAI;
?>



<?php Templater::partialBegin("content"); ?>

<?php 
    if(wire("input")->is("POST")){

        

        dump(wire("input")->post("message"));

        $openai = new HasOpenAI();
        $result = $openai->fetch(wire("input")->post("message"));
        dump($result);

        dump($result['choices'][0]['text']); // an open-source, widely-used, server-side scripting language.

        /*$p = wire('pages')->get(wire("input")->post("pageid"));
        $p->status = Page::statusUnpublished;
        $p->save();*/
    
    }
?>

<form method="post">
    <div><textarea id="message" name="message" cols="100" rows="4" required>Napiš tři největší města s počtem obyvatel ve formátu JSON.</textarea></div>
    <div><input type="submit" class="button" value="Odeslat"></div>
</form>

<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../../corpus.template.php"; ?>                   