<?php

use Bkfdev\FacebookPixel\Facades\FacebookPixel;
use Bkfdev\FacebookPixel\FacebookPixel as FacebookPixelService;

test('it resolves the correct underlying class from the facade', function () {
    $resolvedClass = FacebookPixel::getFacadeRoot();
    expect($resolvedClass)->toBeInstanceOf(FacebookPixelService::class);
});
