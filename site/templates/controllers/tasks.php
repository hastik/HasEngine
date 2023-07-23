<?php



 
    function taskTable(){

        $tasks = wire()->pages->find("parent=Tasks, template=prm_task,state=0");
        $tasks = prepareTaskToProcess($tasks);        

        $template = getLateTemplate("tasktemplate.latte",["tasks" => $tasks]);

        return $template->render();

    }


?>