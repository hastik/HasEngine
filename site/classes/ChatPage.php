<?php namespace ProcessWire;


class ChatPage extends DefaultPage {

    public function test(){
        bd("test");
    }

    public function getMessages(){
        return $this->children("template=message");
    }

}

