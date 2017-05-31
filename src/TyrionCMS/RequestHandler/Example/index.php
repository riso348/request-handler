<?php

include __DIR__ . "/autoloader.php";

$request = new \TyrionCMS\RequestHandler\Request();

echo $request->get()->find("page")->getValue(false);    // Find Throw exception if not found

echo $request->get("page")->getValue();                 // get return null if not found

echo $request->post()->find("id")->getValue(false);

echo $request->get()->getValues();                      // Return all GET params as array