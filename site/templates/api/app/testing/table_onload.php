<?php namespace ProcessWire; ?>

<?
    $full = $page->resource->getVal("full");

    $full_url = $page->cloneResource()->setQueryVal("full",1)->update()->getLiveUrl();
 
?>

<?php Templater::partialBegin("content"); ?>





<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../../corpus.template.php"; ?>
                   