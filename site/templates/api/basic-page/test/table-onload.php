<?php namespace ProcessWire; ?>



<?php 

    if(isset($page->get("_hasGet")["static"])){
        
        //$pages = wire($pages)->findMany("template=basic-page");
        $pages = $page->children("limit=100");
    }
    
    $hypermedia = new Hypermedia; 


?>




    <?php  if(isset($page->get("_hasGet")["static"])): ?>


        



        <?php $i=0; foreach($pages as $currentpage): $i++; ?>

            <?php $outputs = $hypermedia->get($currentpage->url."/test/table-row?selector=published=0,children.count>0&onpage=50&page=1&cache=30","wire")->fetch(); 

                if($i==1){
                    //dump($output);
                }
                echo $outputs;
                //dump($outputs);
                
            
            ?>

        <?php endforeach; ?>


    <?php  else:?>

        <table role="grid">

    <thead>
        <tr>
            <td>Name</td>
            <td>Count</td>
            <td>Edit</td>
        </tr>
    </thead>
        <tbody>
        <tr id="replace" 
            hx-get="/test/test/table-includes"
            hx-select="#tbody"
            hx-trigger="load delay:1s"
            hx-target="closest tbody"
            hx-swap="outerHTML">
            <td colspan="3">
            <div style="text-align: center"><img class="my-indicator  htmx-indicator" src="https://media.tenor.com/wpSo-8CrXqUAAAAi/loading-loading-forever.gif" style="width:2em;"></div>
            </td>
        </tr>
        </tbody>

        </table>
    <?php  endif; ?>




    





