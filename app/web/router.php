<?php

use HTTP\Request\Router;

Router::get("/", "home@main")->name("main");

Router::group("/admin", function() {
    Router::get("/home", function() {
        return "test";
    });
});

Router::api("/api", "test@update");
Router::redirect("/redirect", "/api");

Router::dispatch();