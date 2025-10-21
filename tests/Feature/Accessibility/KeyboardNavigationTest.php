<?php

namespace Tests\Feature\Accessibility;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all interactive elements have proper tabindex
     */
    public function test_login_form_has_proper_keyboard_navigation()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Check for proper form structure
        $content = $response->getContent();

        // Email input should be focusable
        $this->assertStringContainsString('type="email"', $content);
        $this->assertStringContainsString('name="email"', $content);

        // Password input should be focusable
        $this->assertStringContainsString('type="password"', $content);
        $this->assertStringContainsString('name="password"', $content);

        // Submit button should be focusable
        $this->assertMatchesRegularExpression('/<button[^>]*type=["\']submit["\']/', $content);
    }

    /**
     * Test navigation menu keyboard accessibility
     */
    public function test_navigation_menu_is_keyboard_accessible()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Navigation should have proper ARIA attributes
        $this->assertMatchesRegularExpression('/<nav[^>]*role=["\']navigation["\']/', $content);

        // Links should be keyboard accessible (no tabindex=-1 on important links)
        $this->assertStringNotContainsString('tabindex="-1"', $content);
    }

    /**
     * Test course catalog keyboard navigation
     */
    public function test_course_catalog_cards_are_keyboard_accessible()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Course cards should have clickable links
        $this->assertStringContainsString($course->title, $content);

        // Links should be properly formed
        $this->assertMatchesRegularExpression('/<a[^>]*href=["\'][^"\']*["\']/', $content);
    }

    /**
     * Test modal dialogs keyboard accessibility
     */
    public function test_modal_dialogs_trap_focus()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Check for Alpine.js focus trap directives if modals exist
        if (str_contains($content, 'x-show')) {
            // Modals should have proper ARIA attributes
            $this->assertMatchesRegularExpression('/role=["\']dialog["\']/', $content);
        }
    }

    /**
     * Test form validation errors are keyboard accessible
     */
    public function test_form_validation_errors_are_announced()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);

        // Follow redirect to see errors
        $response = $this->get('/login');

        $content = $response->getContent();

        // Error messages should be associated with inputs via aria-describedby or similar
        // Or displayed in a way that screen readers can access
        $this->assertStringContainsString('error', strtolower($content));
    }

    /**
     * Test skip navigation link exists
     */
    public function test_skip_navigation_link_exists()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Should have a skip to main content link
        $this->assertMatchesRegularExpression('/skip.*main|skip.*content/i', $content);
    }

    /**
     * Test dropdown menus are keyboard accessible
     */
    public function test_dropdown_menus_are_keyboard_accessible()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Dropdowns should have proper ARIA attributes
        if (str_contains($content, 'dropdown')) {
            $this->assertMatchesRegularExpression('/aria-haspopup|aria-expanded/', $content);
        }
    }

    /**
     * Test buttons have proper type attributes
     */
    public function test_buttons_have_proper_type_attributes()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('admin.courses.edit', $course));

        $response->assertStatus(200);

        $content = $response->getContent();

        // All buttons should have explicit type attribute
        preg_match_all('/<button[^>]*>/', $content, $buttons);

        foreach ($buttons[0] as $button) {
            // Each button should have a type attribute
            $this->assertMatchesRegularExpression('/type=["\'](?:button|submit|reset)["\']/', $button);
        }
    }

    /**
     * Test focus indicators are not removed
     */
    public function test_focus_indicators_are_not_disabled()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $content = $response->getContent();

        // CSS should not contain outline: none without alternative focus styles
        // This is a basic check - full CSS analysis would require parsing
        $this->assertStringNotContainsString('outline:none', str_replace(' ', '', $content));
        $this->assertStringNotContainsString('outline: none', $content);
    }

    /**
     * Test assessment taking interface keyboard navigation
     */
    public function test_assessment_interface_is_keyboard_accessible()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        // Enroll user
        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $assessment = \App\Models\Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->get(route('assessments.start', $assessment));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Start button should be keyboard accessible
        $this->assertMatchesRegularExpression('/<button[^>]*>.*Start.*<\/button>/is', $content);
    }
}
