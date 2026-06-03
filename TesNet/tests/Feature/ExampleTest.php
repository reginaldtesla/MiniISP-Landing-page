<?php

test('health endpoint returns ok', function () {
    $this->get('/up')->assertSuccessful();
});
