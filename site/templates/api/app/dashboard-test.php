<?php namespace ProcessWire;

use Processwire\HypermediaResource;

?>
<?php Templater::partialBegin("content"); ?>
                            <h3>Dashboard Dynamic</h3>
                            <div class="card">
                                <?php 
                                    
                                    
                                    $listIncluded = wire("hypermedia")->getWired("test/r-basic-page_test_table-included/q-limit=1?cache=super");
                                    dump($listIncluded->page);
                                    $listIncludedXX = wire("hypermedia")->getWired("test/r-basic-page_test_table-included/q-limit=1000?cache=velkySpatny");

                                    dump($listIncludedXX->page == $listIncluded->page);

                                    dump($listIncluded->page);
                                    dump($listIncludedXX->page);
                                    
                                    dump($listIncluded->uid);
                                    dump($listIncludedXX->uid);
                                    
                                    $out = $listIncluded->render();
                                    
                                    

                                    $link = $listIncluded->ahref();
                                    
                                    echo $listIncluded->timeReport();
                                    echo "<div>$link</div>";
                                    echo $out;

                                ?>
                            </div>    


                            <br><br>

<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../corpus.template.php"; ?>
                   