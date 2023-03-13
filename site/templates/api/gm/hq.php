<?php namespace ProcessWire; 
?>

<?php 

    if($page->resource->getTempVal("tick") || $page->resource->getTempVal("loop")){

        $dresources = array();

        $buildings = $page->gm_costs;
        
        foreach($buildings as $building){            
            
            foreach($building->gm_resource_ref->gm_production as $production){
                
                $name = $production->gm_resource_ref->name;
                $val = $building->gm_num * $production->gm_num;                
                updateResource($name,$val,$dresources);
            }
        }

        //bd($dresources);

        updateGameplayResource($page,$dresources);
        
    }


    if($page->resource->getTempVal("build")){

        $name = $page->resource->getTempVal("build");

        $building = $page->parent("template=gm_game")->get("name=buildings")->get("name=$name");

        if($page->gm_costs->get("gm_resource_ref.name=$name")){
            $res = $page->gm_costs->get("gm_resource_ref.name=$name");
            $res->of(false);
            $res->gm_num += 1;
            $res->save();
        }

        else{

            $newbuilding = $page->gm_costs->getNew();
            
            $newbuilding->gm_resource_ref=$building;
            $newbuilding->gm_num = 1;
            $newbuilding->save();

        }

        
        foreach($building->gm_costs as $cost){
            
            $resname = $cost->gm_resource_ref->name;
            $res = $page->gm_production->get("gm_resource_ref.name=$resname");
            
            $res->of(false);
            $res->gm_num-=$cost->gm_num;
            $res->save();
        }
        
    }


    function updateResource($name,$val,&$dresources){
        if(isset($dresources[$name])){
            $dresources[$name]+=$val;
        }
        else{
            $dresources[$name]=$val;
        }
    }

    function updateGameplayResource($gameplay,$dresources){

        foreach($gameplay->gm_production as $resource){
            $name = $resource->gm_resource_ref->name;
            $resource->of(false);
            if(isset($dresources[$name])){
                $resource->gm_num += $dresources[$name];
                $resource->gm_dnum = $dresources[$name];
            }
            else{
                $resource->gm_dnum = 0;
            }
            $resource->save();
            unset($dresources[$name]);
        }
        
        foreach($dresources as $name => $val){            
            
            $originalresource = wire("page")->parent("template=gm_game")->get("name=resources")->children("name=$name");
            bd($originalresource);
            $newresource = $gameplay->gm_production->getNew();
            $newresource->gm_resource_ref = $originalresource;
            $newresource->gm_num = $val;
            $newresource->gm_dnum = $val;
            $newresource->save();
        }

    }
    

    function canByBuild(&$building,$gameplay){

        $needed = $building->gm_costs;


        $have = $gameplay->gm_production;


        $output = 1;

        foreach($needed as &$need){
            //$havei = $have->get("gm_resource_ref.title=$need->title");
            /*foreach($have as $havei){
                bd($havei->gm_resource_ref->name);
                bd($need->gm_resource_ref->name);
            }*/
            $eq = $need->gm_resource_ref->name;
            $havei = $have->get("gm_resource_ref.name=$eq");
           

            if($havei){
               
                if($havei->gm_num >= $need->gm_num){
                    $output *= 1;
                    $need->setQuietly("have",1);
                }
                else{
                    $output *= 0;    
                    $need->setQuietly("have",0);
                }
            }
            else{                
                $output *= 0;
                $need->setQuietly("have",0);
            }
        }

        $building->setQuietly("canbuild",$output);
        
    }

    function readyBuildings(&$buildings,$gameplay){
        foreach($buildings as &$building){            
            canByBuild($building,$gameplay);
            
        }        
    } 


    $resources = $page->gm_production;
 
    $buildings = $page->gm_costs;


    $available_buildings = $page->parent("template=gm_game")->get("name=buildings")->children();
    readyBuildings($available_buildings,$page);
    //bd($available_buildings);

?>

<html>

<head>

    <link href="/site/templates/game.css" rel="stylesheet" />

    <script src="https://unpkg.com/htmx.org@1.8.5"></script>
    <script src="https://unpkg.com/hyperscript.org@0.9.7"></script>

</head>



<body>



<div class="wrapper" id="app">
    <div class="wrapper-inner">

        <div class="grid">


            <div class="masterbar">
                <div class="resources">
                <?php foreach($resources as $resource): ?>

                    <div class="resource masterresource boxshadow cluster">
                        <span class="image"><img src="<?=$resource->gm_resource_ref->gm_image->url?>"></span>
                        <span class="name"><?=$resource->gm_resource_ref->title?></span>
                        <span class="val"><?=floor($resource->gm_num)?></span>
                        <span class="dval fs-1">(<?=$resource->gm_dnum?>)</span>
                    </div>

                <?php endforeach; ?>
                    
                </div>
            </div>


            <div class="main">


                <div class="another">
                    <div class="buildings">
                    <?php foreach($buildings as $building):?>
                        <div class="building boxshadow gallery">
                            <span class="image"><img src="<?=$building->gm_resource_ref->gm_image->url?>"></span>
                            <span class="name"><?=$building->gm_resource_ref->title?></span>
                            <span class="val"><?=$building->gm_num?></span>
                            <div class="process">
                                <?php 
                                    $origin = $building->gm_resource_ref;                                    
                                    $inputs = array();
                                    $outputs = array();
                                    foreach($origin->gm_production as $cost){                                        
                                        //bd($cost);
                                        if($cost->gm_num > 0){
                                            $outputs[] = ["num" => abs($cost->gm_num),"src" => $cost->gm_resource_ref->gm_image->url];
                                        }
                                        if($cost->gm_num < 0){
                                            $inputs[] = ["num" => abs($cost->gm_num),"src" => $cost->gm_resource_ref->gm_image->url];
                                        }
                                    }

                                    bd($inputs);
                                    bd($outputs);
                                ?>


                                <div class="inputs">

                                <?php foreach($inputs as $input): ?>
                                    <?php for($i=1;$i<=$input["num"];$i++): ?>
                                            <img src="<?=$input["src"]?>">
                                    <?php endfor;?>
                                <?php endforeach;?>
                                </div>

                                <div class="outputs">
                                <?php foreach($outputs as $input): ?>
                                    <?php for($i=1;$i<=$input["num"];$i++): ?>
                                            <img src="<?=$input["src"]?>">
                                    <?php endfor;?>
                                <?php endforeach;?>
                                    </div>
                            </div>
                            
                        </div>

                    <?php endforeach; ?>
                        
                    </div>

                    <div class="map">

                        <?php
                            $build[4]= "http://pwx.local/site/assets/files/2220/mine-removebg-preview.png";
                            $build[1]= "http://pwx.local/site/assets/files/2203/woodcutter.png";
                            $build[2]= "http://pwx.local/site/assets/files/2245/housecabin.png";
                            $build[3]= "http://pwx.local/site/assets/files/2251/plankhouse.png";

                            

                            function posLeft($i,$j){

                                $height = 5.8;
                                $width = 9.9;
                                $dim = 10;

                                $left = ($i * $width/2) - ($j * $width/2) + $dim*$width/2; 

                                $top = ($j * $height/2) + ($i * $height/2); 

                                return ["left" => $left, "top" => $top];
                            }

                        ?>

                        <?php for($i=0;$i<10;$i++):?>
                            <?php for($j=0;$j<10;$j++):?>
                                <div class="tile" data-row="<?=$i?>" data-column="<?=$j?>" style="position: absolute; left:<?=posLeft($j,$i)["left"]?>rem; top:<?=posLeft($j,$i)["top"]?>rem">
                                    <div class="tile-inner">
                                    <svg viewBox="0 0 21 13">
                                        <defs>
                                            <g id="pod">
                                                <polygon stroke="#000000" stroke-width="0" points="5,-9 -5,-9 -10,0 -5,9 5,9 10,0" />
                                            </g>
                                            <g id="tile">
                                                <polygon stroke="#000000" stroke-width="0" points="0,0 10,-6 0,-12 -10,-6" />
                                            </g>
                                        </defs>                                                            
                                        <g class="pod-wrap">                                
                                                <use xlink:href="#tile" transform="translate(10.5, 12.5)" />
                                        </g>                                
                                    </svg>
                                    <?php $rand =rand(1,10);  if(isset($build[$rand])): $style = rand(0,1) == 1 ? "transform: scaleX(-1);" : ""; ?>
                                        <img src="<?=$build[$rand]?>" style="<?=$style?>">
                                    <?php endif; ?>
                                </div>
                                </div>
                            <?php endfor; ?>
                        <?php endfor; ?>

                    </div>

                </div>

                <style>
                    use{
                        transition: 0.4s;
                        cursor: pointer;
                        fill: transparent;
                        fill: #8F9120;
                    }
                    .pod-wrap use:hover {
                        fill: #000000;
                    }
                    .map .tile svg{
                        width: 10rem;
                        clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
                        pointer-events: auto;
                    }

                    .tile{                        
                        width: 10rem;
                        text-align: center;
                        pointer-events: none;
                    }

                    .tile:hover img{
                        bottom:.8rem;

                    }

                    .tile-inner{
                        position: relative;
                        
                    }

                    .tile-inner img{
                        width: 80%;
                        position: absolute;
                        bottom: 0.3rem;
                        left:0.9rem;
                        pointer-events: none;
                        transition: all .3s ease-in-out;
                    }

                    .map{
                        position: relative;
                        height: 80vh;
                    }
                    .tile img:hover{
                        opacity: 1.9;
                    }
                </style>







                <div class="another">
                    <div class="abuildings">
                    <?php foreach($available_buildings as $building):?>

                        <div class="abuilding boxshadow gallery canbuild<?=$building->get("canbuild")?>">
                            <span class="image build"><img src="<?=$building->gm_image->url?>"></span>
                            <span class="name"><?=$building->title?></span>
                            

                            <?php foreach($building->gm_costs as $resource): 
                                ?>
                                
                                <div class="fs-1 mgl resource cluster have<?=$resource->get("have")?>">
                                    <span class="image"><img src="<?=$resource->gm_resource_ref->gm_image->url?>"></span>
                                    <span class="name"><?=$resource->gm_resource_ref->title?></span>
                                    <span class="val"><?=$resource->gm_num?></span>
                                </div>

                            <?php endforeach; ?>

                            <a class="button"
                                hx-get="<?=$page->cloneResource()->setTempVal("build",$building->name)->update()->getLiveUrl()?>"
                                href="<?=$page->cloneResource()->setTempVal("build",$building->name)->update()->getLiveUrl()?>"
                                hx-target="#app" hx-select="#app">Build</a>
                                
                        </div>

                    <?php endforeach; ?>
                        
                    </div>

                    <div class="control">
                        <?php if($page->resource->getTempVal("loop")):?>
                            <div class="lds-dual-ring "></div>
                            <div hx-get="<?=$page->cloneResource()->setTempVal("loop",1)->update()->getLiveUrl()?>" hx-preserve="#audio" hx-trigger="load delay:1s" hx-target="#app" hx-select="#app"></div>
                            <a class="button control" href="<?=$page->resource->setTempVal("tick",1)->update()->getLiveUrl()?>">Stop Loop</a>
                        <?php else: ?>
                            
                            <a class="button control" href="<?=$page->resource->setTempVal("tick",1)->update()->getLiveUrl()?>">Play Tick</a>
                            <a class="button control" 
                                href="<?=$page->resource->setTempVal("loop",1)->update()->getLiveUrl()?>"
                                hx-get="<?=$page->resource->setTempVal("loop",1)->update()->getLiveUrl()?>"
                                hx-target="#app" hx-select="#app">Play Loop</a>
                        <?php endif; ?>
                    </div>

                   

                </div>
            </div>
            


        </div>


        <div class="hmap">

<?php
    $build[4]= "http://pwx.local/site/assets/files/2220/mine-removebg-preview.png";
    $build[1]= "http://pwx.local/site/assets/files/2203/woodcutter.png";
    $build[2]= "http://pwx.local/site/assets/files/2245/housecabin.png";
    $build[3]= "http://pwx.local/site/assets/files/2251/plankhouse.png";

    

    function posHexLeft($i,$j){

        $height = 4.0;
        $width = 9;
        $dim = 10;

        $left = ($i * $width*3/4) - ($j * $width*3/4) + $dim*$width/2; 

        $top = ($j * $height*3/4) + ($i * $height*3/4); 

        return ["left" => $left, "top" => $top];
    }

?>

            <?php for($i=0;$i<10;$i++):?>
                <?php for($j=0;$j<10;$j++):?>
                    <div class="htile" data-row="<?=$i?>" data-column="<?=$j?>" style="position: absolute; left:<?=posHexLeft($j,$i)["left"]?>rem; top:<?=posHexLeft($j,$i)["top"]?>rem" >
                        <div class="htile-inner">
                        <svg viewBox="0 0 300 220">
                            <defs>
                                <g id="htile">
                                    <polygon stroke="#000000" stroke-width="0.5" points="300,120 225,220 75,220 0,120 75,20 225,20" />
                                </g>
                            </defs>                                                            
                            <g class="pod-wrap">                                
                                    <use xlink:href="#htile" />
                            </g>                                
                        </svg>
                        <?php $rand =rand(1,10);  if(isset($build[$rand])): $style = rand(0,1) == 1 ? "transform: scaleX(-1);" : ""; ?>
                            <img src="<?=$build[$rand]?>" style="<?=$style?>">
                        <?php endif; ?>
                    </div>
                    </div>
                <?php endfor; ?>
            <?php endfor; ?>

            </div>

            </div>

            <style>
            use{
            transition: 0.4s;
            cursor: pointer;
            fill: transparent;
            fill: #8F9120;
            transition: all 0.2s ease-in-out;
            }
            .pod-wrap use:hover {
                fill: #6A6C17;
            }
            .hmap .htile svg{
            
            
            pointer-events: auto;
            }

            .htile{                        
            width: 9rem;
            text-align: center;
            pointer-events: none;
            }

            .htile:hover img{
            bottom:.8rem;

            }

            .htile-inner{
            position: relative;

            }

            .htile-inner img{
            width: 80%;
            position: absolute;
            bottom: 0.3rem;
            left:0.9rem;
            pointer-events: none;
            transition: all .3s ease-in-out;
            }

            .hmap{
            position: relative;
            height: 80vh;
            }
            .htile img:hover{
            opacity: 1.9;
            }
        </style>



    </div>
</div>

<audio id="audio" controls autoplay>
                        <source src="/site/templates/bg.mp3" type="audio/mp3">
                    </audio>

</body>

</html>