<?php namespace ProcessWire;

?>
<?php Templater::partialBegin("content"); ?>
                            <h3>Dashboard Dynamic</h3>
                            <div class="card">
                                <?php 
                                    $listIncluded = wire("hypermedia")->getWired("/test/r-basic-page_test_table-included/q-limit=20&count=40?count=90");
                                    $out = $listIncluded->render();
                                    $link = $listIncluded->ahref();
                                    goto test;
                                    echo "Ahoj";
                                    test:
                                    bd($link);
                                    echo $listIncluded->timeReport();
                                    echo "<div>$link</div>";
                                    echo $out;

                                ?>
                            </div>    
<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../corpus.template.php"; ?>
                   