<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Login page: exactly one show / hide password button, which never submits the form. */
class LoginPasswordToggleTest extends TestCase
{
    public function test_the_password_field_has_one_accessible_eye_button()
    {
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1');
        $html = $this->get('http://127.0.0.1/' . env('BACKEND_PATH', 'admin') . '/login')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="lg-pwd-toggle"'), 'one toggle button');
        $this->assertMatchesRegularExpression('/<button type="button" class="lg-pwd-toggle"[^>]*aria-label="Show password"/', $html);
        $this->assertMatchesRegularExpression('/<svg id="pwd-icon-eye-off"[^>]*hidden>/', $html, 'only the eye is visible at first');
        $this->assertStringNotContainsString('tabindex="-1"', substr($html, strpos($html, 'lg-pwd-toggle'), 300), 'reachable with the keyboard');
    }
}
