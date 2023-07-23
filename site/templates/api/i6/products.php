<?php namespace ProcessWire; ?>

<?
    // START
?>

<?php Templater::partialBegin("content"); ?>

    <div class="card">
            <div class="card-inner">
                <h4>Seznam produktů</h4>
                <?php  ?>
                <?=$page->newSourceFromUrl(wire("appr")->get("products-table"))->include();?>
                <?php //$page->newSourceFromUrl("/app/api/chats/r-ai_openai_threads_table")->include();?>
                
                <?php // $page->hmCast("/messages/tablebody/",["project_id" => 4],null); ?>
                <?php // $page->hmCast("messages","tablebody",["project_id" => 4],null); ?>
                <?php //$page->hmCast("/messages/78/row-edit"); ?>
                <?php // $page->hmCast("/messages/154/table-row"); ?>
            </div>
    </div>

<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../corpus.template.php"; ?>
                   