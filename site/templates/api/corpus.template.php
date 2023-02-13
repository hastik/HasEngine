<?php namespace ProcessWire;

?>
<html>
    <head>
    
    <script src="https://unpkg.com/htmx.org@1.8.5"></script>
    <link href="/site/templates/style.css" rel="stylesheet" />

    </head>

    <body>
        <div class="body">
            <div class="wrapper">
                <!-- ========== Left Sidebar Start ========== -->
                <div class="sidebar">
                    <div class="sidebar_head global_head">
                            <div class="inner">
                                Head
                            </div>
                    </div>
                    <div class="sidebar-inner dark">
                        <h4>Sidebar</h4>
                        <!-- ========== Navigation Start ========== -->
                        <div class="nav">
                            Navigation
                            <li class="nav-li"><a href="/" class="nav-li-a">Home</a></li>
                            <li class="nav-li"><a href="/" class="nav-li-a">Test HP</a></li>
                            
                            <li class="nav-li"><a href="/" class="nav-li-a">AI</a></li>
                            <li class="nav-li"><a href="/" class="nav-li-a">I6</a></li>
                        </div>
                        <!-- ========== Navigation End ========== -->
                    </div>
                </div>
                <!-- ========== Left Sidebar End ========== -->

                <!-- ========== Main Start ========== -->
                <div class="main">
                    <!-- ========== Topbar Start ========== -->
                    <div class="topbar">
                        <div class="topbar_head global_head">
                            <div class="inner">
                                <div class="pdh fs-1">Head</div>
                            </div>
                        </div>
                    </div>
                    <!-- ========== Topbar End ========== -->
                   
                    <!-- ========== Content Start ========== -->
                    <div class="content">
                        <div class="content-inner">
                            <?=Templater::getPartial("content")?>
                        </div>    
                    </div>
                    <!-- ========== Content End ========== -->
                </div>
                <!-- ========== Main End ========== -->
            </div>
        </div>
    </body>
</html>
