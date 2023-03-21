<?php namespace ProcessWire;

?>
<html>
    <head>
    
    
    <link href="/site/templates/style.css" rel="stylesheet" />

    <script src="https://unpkg.com/htmx.org@1.8.5"></script>
    <script src="https://unpkg.com/hyperscript.org@0.9.7"></script>
    

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
                            
                            <ul class="navul">                                
                                <li class="nav-li"> <a href="/" class="nav-li-a">Home</a> </li>
                                <li class="nav-li hide"> <a href="/" class="nav-li-a">Testing</a>
                                    <div class="subnav">
                                    <ul class="navul hide">
                                        <li class="nav-li"> <span class="navtitle">Odkazy</span></li>
                                        <li class="nav-li"> <a href="/novy/r-app_testing_dashboard-all" class="nav-li-a">Dashboard SSR</a> </li>
                                        <li class="nav-li"> <a href="/test/r-app_testing_table-included" class="nav-li-a">Table</a> </li>
                                        <li class="nav-li"> <a href="/kontakt/r-app_testing_static" class="nav-li-a">Static page</a> </li>
                                        <li class="nav-li"> <a href="/sluzby/r-app_testing_simple-page" class="nav-li-a">Simple page</a> </li>
                                        <li class="nav-li"> <a href="/o-nas/r-app_testing_inserted-simple-pages" class="nav-li-a">Simple pages</a> </li>
                                        <li class="nav-li"> <span class="navtitle">Boosty</span></li>
                                        <div hx-boost="true">
                                            <li class="nav-li"> <a href="/novy/r-app_testing_dashboard-all" class="nav-li-a">Dashboard SSR</a> </li>
                                            <li class="nav-li"> <a href="/test/r-app_testing_table-included" class="nav-li-a">Table</a> </li>
                                            <li class="nav-li"> <a href="/kontakt/r-app_testing_static" class="nav-li-a">Static page</a> </li>
                                            <li class="nav-li"> <a href="/sluzby/r-app_testing_simple-page" class="nav-li-a">Simple page</a> </li>
                                            <li class="nav-li"> <a href="/o-nas/r-app_testing_inserted-simple-pages" class="nav-li-a">Simple pages</a> </li>
                                        </div>
                                    </ul>
                                </li>
                                <li class="nav-li"> <a href="/" class="nav-li-a">OpenAI</a>
                                    <ul class="navul">
                                            <li class="nav-li"> <a href="/app/ai/openai/r-ai_openai_dashboard" class="nav-li-a">Dashboard</a></li>
                                            <li class="nav-li"> <a href="/app/ai/openai/chats/r-ai_openai_thread_new" class="nav-li-a">Nový chat</a></li>
                                            <li class="nav-li"> <a href="/app/ai/openai/simple-test/r-ai_openai_dummytest" class="nav-li-a">Simple test</a></li>
                                    </ul>
                                </li>
                                <li class="nav-li"> <a href="/" class="nav-li-a">I6</a>
                                    <ul class="navul">
                                            <li class="nav-li"> <a href="/app/i6/products/r-i6_products" class="nav-li-a">Produkty</a></li>
                                            <li class="nav-li"> <a href="/app/i6/categories/r-i6_categories_dashboard" class="nav-li-a">Virtuální stromy</a></li>
                                            <li class="nav-li"> <a href="/app/i6/parameters/r-i6_parameters" class="nav-li-a">Parametry</a></li>
                                    </ul>
                                </li>
                                <li class="nav-li hide"><a href="/" class="nav-li-a">AI</a></li>
                                <li class="nav-li hide"><a href="/" class="nav-li-a">I6</a></li>
                            </ul>
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
                        <div class="content-inner" >
                            <div class="replaceable" id="content">
                                <?=Templater::getPartial("content")?>
                            </div>
                        </div>    
                    </div>
                    <!-- ========== Content End ========== -->
                </div>
                <!-- ========== Main End ========== -->
            </div>
        </div>
    </body>
</html>
