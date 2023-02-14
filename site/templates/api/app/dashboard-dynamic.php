<?php namespace ProcessWire;

use Processwire\HypermediaResource;

$fragment = wire("hypermedia")->registerResource("test")->setRouter("basic-page/test/table-included")->setQueryVal("limit",10)->setGetVal("cache",60);
//bd($fragment->getUrl());
$same = wire("hypermedia")->getRegisteredResource("test");
$same->setPageUrl("/test");
$new_fragment = wire("hypermedia")->getWiredResource($same);

bd($new_fragment);

$fragment2 = wire("hypermedia")->registerResource("testx")->setRouter("basic-page/test/table-included")->setQueryVal("limit",4)->setGetVal("cache",60);
                                    //bd($fragment2->getUrl());
                                    $samex = wire("hypermedia")->getRegisteredResource("testx");
                                    $samex->setPageUrl("/test");

                                    //bd(wire("hypermedia")->resources["test"]);
                                    $new_fragment2 = wire("hypermedia")->getWiredResource($samex);

?>
<?php Templater::partialBegin("content"); ?>
                            <h3>Dashboard Dynamic</h3>
                            <div class="card">
                                <?php 
                                    $listIncluded = wire("hypermedia")->getWired("test/r-basic-page_test_table-included/q-limit_eq_2?cache=60");
                                    $listIncludedXX = wire("hypermedia")->getWired("test/r-basic-page_test_table-included/q-limit_eq_1?cache=60");
                                    $out = $listIncluded->render();
                                    $link = $listIncluded->ahref();
                                    goto test;
                                    echo "Ahoj";
                                    test:
                                    //bd($link);
                                    echo $listIncluded->timeReport();
                                    echo "<div>$link</div>";
                                    echo $out;

                                ?>
                            </div>    


                            <br><br>

                            <div class="card">
                                <?php 

                                    
                                    //$listIncluded2 = wire("hypermedia")->getWired("test/r-basic-page_test_table-included/q-limit_eq_1?cache=60");                                    
                                    $listIncluded2 = wire("hypermedia")->getWired($new_fragment2->getUrl(false));
                                    $out = $listIncluded2->render();
                                    $link = $listIncluded2->ahref();
                                    //bd($link);
                                    echo $listIncluded2->timeReport();
                                    echo "<div>$link</div>";
                                    echo $out;

                                ?>
                            </div>  



                            <br><br>
                            <div class="card">
                                <?php 

                                    
                                    
                                    
                                    

                                    //bd(wire("hypermedia")->resources["test"]);
                                    
                                    bd($new_fragment);
                                    //dump($new_fragment);
                                    $out = $new_fragment->render();
                                    $link = $new_fragment->ahref();
                                    //bd($link);
                                    echo $new_fragment->timeReport();
                                    echo "<div>$link</div>";
                                    echo $out;

                                ?>
                            </div>    
<?php Templater::partialEnd(); ?>

<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");
include "./../corpus.template.php"; ?>
                   