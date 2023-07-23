<?php

$wire->addHook('/api/users', function ($event) {
    $event->return = "Hello, this is the /api/users route!";
});

$wire->addHook('/hello/world', function($event) {
    return 'Hello World';
  }); 