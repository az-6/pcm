<?php

test('the home route points visitors to the demo dashboard', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/dashboard');
});
