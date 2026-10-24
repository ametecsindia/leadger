<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Header Light / Dark switch: off = original (default), on = dark; saved per user and applied before the page draws. */
class ThemeToggleTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->user = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
    }

    public function test_header_has_the_switch_and_light_is_the_default()
    {
        $this->user->forceFill(['theme' => null])->save();
        $page = $this->actingAs($this->user)->get(route('adminHome'))->assertOk();
        $page->assertSee('id="lgThemeToggle"', false)->assertSee('role="switch"', false);
        $page->assertDontSee('id="lgThemeMenu"', false);   // the old palette menu is gone
        $page->assertSee('var t = "original"', false);     // never chosen: light
    }

    public function test_the_saved_choice_is_applied_on_every_page_and_only_known_themes_are_saved()
    {
        $this->actingAs($this->user)->postJson(route('userTheme'), ['theme' => 'dark'])->assertOk()->assertJson(['success' => true]);
        $this->assertSame('dark', $this->user->fresh()->theme);
        // a new request loads the user again (the test keeps one signed-in object, so reload it)
        $this->actingAs($this->user->fresh())->get(route('callReport'))->assertOk()->assertSee('var t = "dark"', false);

        $this->postJson(route('userTheme'), ['theme' => 'original'])->assertOk();
        $this->assertSame('original', $this->user->fresh()->theme);
        $this->postJson(route('userTheme'), ['theme' => 'purple'])->assertStatus(422);
        $this->assertSame('original', $this->user->fresh()->theme);
    }
}
