<?php

use HTTP\Request\Router;

Router::get("/", "home@main")->name("main");
Router::group("/admin", function() {
    Router::redirect("/network", "/");
});

Router::dispatch();