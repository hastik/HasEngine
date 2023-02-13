<?php namespace ProcessWire;

?>
<html>
    <head>
        <?=Templater::sectionInsert("header"); ?>
    </head>

    <body>
        <?=Templater::sectionInsert("afterBodyStart"); ?>

        <?=Templater::sectionInsert("body"); ?>

        <?=Templater::sectionInsert("beforeBodyEnd"); ?>
    </body>

    <?=Templater::sectionInsert("beforeHtmlEnd"); ?>
</html>
