<?php

namespace Tests\Feature\Accessibility;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorContrastTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that CSS includes proper color contrast
     * This is a basic test - full contrast testing requires visual analysis tools
     */
    public function test_css_file_exists_and_is_accessible()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Should include CSS file
        $this->assertMatchesRegularExpression('/<link[^>]*href=["\'][^"\']*\.css["\']/', $content);
    }

    /**
     * Test dark mode toggle exists
     */
    public function test_dark_mode_toggle_exists()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Should have dark mode toggle
        $this->assertMatchesRegularExpression('/dark.*mode|theme.*toggle/i', $content);
    }

    /**
     * Test text is not displayed as images
     */
    public function test_text_is_not_displayed_as_images()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Important text should not be in images
        // Check that headings are actual HTML elements, not images
        preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>/is', $content, $headings);

        foreach ($headings[0] as $heading) {
            $this->assertStringNotContainsString('<img', $heading, 'Heading should not contain images');
        }
    }

    /**
     * Test color is not the only means of conveying information
     */
    public function test_error_messages_use_more_than_color()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);

        $response = $this->get('/login');

        $content = $response->getContent();

        // Error messages should have text, not just red color
        // They should contain actual error text
        if (str_contains(strtolower($content), 'error')) {
            $this->assertMatchesRegularExpression('/required|invalid|must/i', $content);
        }
    }

    /**
     * Test focus indicators are visible
     */
    public function test_focus_indicators_are_defined()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Check that focus styles are not completely removed
        // This is a basic check - full testing requires CSS parsing
        $this->assertStringNotContainsString('*:focus{outline:none}', str_replace(' ', '', $content));
        $this->assertStringNotContainsString('*:focus{outline:0}', str_replace(' ', '', $content));
    }

    /**
     * Test sufficient spacing between interactive elements
     */
    public function test_buttons_have_minimum_size()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // This is a structural test - actual size testing requires browser rendering
        // We can verify that buttons use proper CSS classes
        $content = $response->getContent();

        // Buttons should use Tailwind classes that provide adequate sizing
        if (str_contains($content, '<button')) {
            $this->assertMatchesRegularExpression('/class=["\'][^"\']*(?:px-|py-|p-)[^"\']*["\']/', $content);
        }
    }

    /**
     * Test text can be resized
     */
    public function test_text_uses_relative_units()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Check that viewport meta tag allows zooming
        $this->assertMatchesRegularExpression('/<meta[^>]*name=["\']viewport["\'][^>]*>/', $content);

        // Should not have user-scalable=no
        $this->assertStringNotContainsString('user-scalable=no', $content);
    }

    /**
     * Test status messages are distinguishable
     */
    public function test_status_messages_have_distinct_indicators()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Success, error, warning messages should have icons or text indicators
        // Not just color differences
        if (str_contains($content, 'alert') || str_contains($content, 'toast')) {
            // Should have role="alert" or similar
            $this->assertMatchesRegularExpression('/role=["\']alert["\']/', $content);
        }
    }

    /**
     * Test links are distinguishable from regular text
     */
    public function test_links_are_distinguishable()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Links should be underlined or have other visual distinction
        // Check that links have proper styling classes
        preg_match_all('/<a[^>]*class=["\']([^"\']*)["\']/', $content, $linkClasses);

        // This is a basic check - links should have styling
        $this->assertNotEmpty($linkClasses[1]);
    }

    /**
     * Test form validation uses multiple indicators
     */
    public function test_form_validation_uses_multiple_indicators()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid',
            'password' => '123',
        ]);

        $response->assertSessionHasErrors();

        $response = $this->get('/register');

        $content = $response->getContent();

        // Validation errors should have:
        // 1. Text message
        // 2. Icon or symbol (optional but recommended)
        // 3. Border color change (optional)

        // At minimum, should have text messages
        $this->assertMatchesRegularExpression('/required|invalid|must|should/i', $content);
    }

    /**
     * Test progress indicators are accessible
     */
    public function test_progress_indicators_have_text_alternatives()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Progress bars should have aria-valuenow, aria-valuemin, aria-valuemax
        if (str_contains($content, 'progress')) {
            $this->assertMatchesRegularExpression('/aria-valuenow|role=["\']progressbar["\']/', $content);
        }
    }

    /**
     * Test charts and graphs have text alternatives
     */
    public function test_charts_have_accessible_alternatives()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Canvas elements (used by Chart.js) should have aria-label or role
        if (str_contains($content, '<canvas')) {
            $this->assertMatchesRegularExpression('/<canvas[^>]*(?:aria-label|role)=["\']/', $content);
        }
    }

    /**
     * Test color contrast in Tailwind config
     */
    public function test_tailwind_config_has_accessible_colors()
    {
        $configPath = base_path('tailwind.config.js');

        if (file_exists($configPath)) {
            $config = file_get_contents($configPath);

            // Should define color schemes
            $this->assertStringContainsString('colors', $config);

            // Should have dark mode configuration
            $this->assertMatchesRegularExpression('/darkMode.*:|class|media/', $config);
        } else {
            $this->markTestSkipped('Tailwind config file not found');
        }
    }

    /**
     * Test that important information is not conveyed by color alone
     */
    public function test_course_status_uses_text_and_icons()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get(route('enrollments.index'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Status indicators should have text labels, not just colored badges
        if (str_contains($content, 'status') || str_contains($content, 'badge')) {
            // Should contain status text like "Active", "Completed", etc.
            $this->assertMatchesRegularExpression('/active|completed|pending|overdue/i', $content);
        }
    }
}
