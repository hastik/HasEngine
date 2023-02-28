<?php namespace ProcessWire; ?>

<?
    // START
?>

<?php Templater::partialBegin("content"); ?>

    <div class="card">
            <div class="card-inner">
                <h4>Seznam vláken</h4>
                <?=$page->newSourceFromUrl("/app/ai/openai/chats/r-ai_openai_threads_table")->include();?>
            </div>
    </div>

<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../../corpus.template.php"; ?>
                   