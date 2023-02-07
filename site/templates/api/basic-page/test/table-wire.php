<?php namespace ProcessWire; ?>



<?php 

    //$pages = wire($pages)->findMany("template=basic-page");
    $pages = $page->children("field=title|pocet");

    $hypermedia = new Hypermedia; 



?>


<table role="grid">

    <thead>
        <tr>
            <td>Name</td>
            <td>Count</td>
            <td>Edit</td>
        </tr>
    </thead>




<?php $i=0; foreach($pages as $currentpage): $i++; ?>

    <?php $outputs = $hypermedia->get($currentpage->url."/test/table-row?selector=published=0,children.count>0&onpage=50&page=1&cacshe=60","wire")->fetch(); 

        if($i==1){
            //dump($output);
        }
        echo $outputs;
        //dump($outputs);
        
    
    ?>

<?php endforeach; ?>


</table>