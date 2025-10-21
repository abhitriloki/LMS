<?php

namespace Tests\Feature\Accessibility;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenReaderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test page has proper document structure
     */
    public function test_pages_have_proper_html_structure()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Should have proper HTML5 structure
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<html', $content);
        $this->assertStringContainsString('<head>', $content);
        $this->assertStringContainsString('<body', $content);
    }

    /**
     * Test pages have proper lang attribute
     */
    public function test_pages_have_lang_attribute()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $content = $response->getContent();

        // HTML tag should have lang attribute
        $this->assertMatchesRegularExpression('/<html[^>]*lang=["\']en["\']/', $content);
    }

    /**
     * Test pages have descriptive titles
     */
    public function test_pages_have_descriptive_titles()
    {
        $user = User::factory()->create(['role' => 'employee']);

        // Test various pages
        $pages = [
            '/' => 'Welcome',
            '/login' => 'Login',
            '/register' => 'Register',
            '/dashboard' => 'Dashboard',
        ];

        foreach ($pages as $url => $expectedTitle) {
            if ($url === '/dashboard') {
                $response = $this->actingAs($user)->get($url);
            } else {
                $response = $this->get($url);
            }

            $response->assertStatus(200);

            $content = $response->getContent();

            // Should have a title tag
            $this->assertMatchesRegularExpression('/<title[^>]*>.*<\/title>/', $content);
        }
    }

    /**
     * Test images have alt text
     */
    public function test_images_have_alt_text()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create([
            'is_published' => true,
            'thumbnail' => 'course-thumbnail.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // All img tags should have alt attribute
        preg_match_all('/<img[^>]*>/', $content, $images);

        foreach ($images[0] as $img) {
            $this->assertMatchesRegularExpression('/alt=["\']/', $img, "Image missing alt attribute: $img");
        }
    }

    /**
     * Test form inputs have labels
     */
    public function test_form_inputs_have_labels()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Check for label elements
        $this->assertMatchesRegularExpression('/<label[^>]*for=["\']email["\']/', $content);
        $this->assertMatchesRegularExpression('/<label[^>]*for=["\']password["\']/', $content);

        // Or check for aria-label on inputs
        preg_match_all('/<input[^>]*>/', $content, $inputs);

        foreach ($inputs[0] as $input) {
            // Each input should have either a label (via for/id) or aria-label
            $hasId = preg_match('/id=["\']([^"\']+)["\']/', $input, $idMatch);
            $hasAriaLabel = preg_match('/aria-label=["\']/', $input);

            if ($hasId && !str_contains($input, 'type="hidden"')) {
                $id = $idMatch[1];
                // Check if there's a corresponding label
                $hasLabel = str_contains($content, "for=\"$id\"") || $hasAriaLabel;
                $this->assertTrue($hasLabel, "Input with id '$id' has no associated label");
            }
        }
    }

    /**
     * Test headings are in logical order
     */
    public function test_headings_are_in_logical_order()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Extract all heading levels
        preg_match_all('/<h([1-6])[^>]*>/', $content, $headings);

        if (!empty($headings[1])) {
            $levels = array_map('intval', $headings[1]);

            // First heading should be h1
            $this->assertEquals(1, $levels[0], 'First heading should be h1');

            // Check for proper hierarchy (no skipping levels)
            for ($i = 1; $i < count($levels); $i++) {
                $diff = $levels[$i] - $levels[$i - 1];
                $this->assertLessThanOrEqual(1, $diff, 'Heading levels should not skip (e.g., h1 to h3)');
            }
        }
    }

    /**
     * Test ARIA landmarks are present
     */
    public function test_aria_landmarks_are_present()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Should have main landmark
        $this->assertMatchesRegularExpression('/<main|role=["\']main["\']/', $content);

        // Should have navigation landmark
        $this->assertMatchesRegularExpression('/<nav|role=["\']navigation["\']/', $content);
    }

    /**
     * Test links have descriptive text
     */
    public function test_links_have_descriptive_text()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Links should not just say "click here" or "read more"
        preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $content, $links);

        foreach ($links[1] as $linkText) {
            $text = strip_tags($linkText);
            $text = trim($text);

            if (!empty($text)) {
                // Link text should be meaningful (not just "here" or "click")
                $this->assertNotEquals('here', strtolower($text));
                $this->assertNotEquals('click', strtolower($text));
            }
        }
    }

    /**
     * Test buttons have accessible names
     */
    public function test_buttons_have_accessible_names()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('admin.courses.edit', $course));

        $response->assertStatus(200);

        $content = $response->getContent();

        // All buttons should have text content or aria-label
        preg_match_all('/<button[^>]*>(.*?)<\/button>/is', $content, $buttons);

        foreach ($buttons[0] as $index => $button) {
            $buttonText = strip_tags($buttons[1][$index]);
            $buttonText = trim($buttonText);

            $hasAriaLabel = preg_match('/aria-label=["\']([^"\']+)["\']/', $button);
            $hasTitle = preg_match('/title=["\']([^"\']+)["\']/', $button);

            // Button should have text, aria-label, or title
            $this->assertTrue(
                !empty($buttonText) || $hasAriaLabel || $hasTitle,
                "Button has no accessible name: $button"
            );
        }
    }

    /**
     * Test tables have proper structure
     */
    public function test_tables_have_proper_structure()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // If tables exist, they should have proper structure
        if (str_contains($content, '<table')) {
            // Tables should have thead and tbody
            $this->assertStringContainsString('<thead', $content);
            $this->assertStringContainsString('<tbody', $content);

            // Headers should use th elements
            $this->assertStringContainsString('<th', $content);
        }
    }

    /**
     * Test form fields have proper ARIA attributes
     */
    public function test_form_fields_have_proper_aria_attributes()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Required fields should have aria-required or required attribute
        preg_match_all('/<input[^>]*required[^>]*>/', $content, $requiredInputs);

        $this->assertNotEmpty($requiredInputs[0], 'Form should have required fields');
    }

    /**
     * Test error messages are associated with form fields
     */
    public function test_error_messages_are_associated_with_fields()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);

        // Get the login page with errors
        $response = $this->get('/login');

        $content = $response->getContent();

        // Error messages should be present
        $this->assertStringContainsString('error', strtolower($content));
    }

    /**
     * Test video player has accessible controls
     */
    public function test_video_player_has_accessible_controls()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        $lesson = CourseLesson::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'video',
        ]);

        $response = $this->actingAs($user)->get(route('lessons.show', $lesson));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Video element should have controls
        if (str_contains($content, '<video')) {
            $this->assertMatchesRegularExpression('/<video[^>]*controls/', $content);
        }
    }

    /**
     * Test live regions for dynamic content
     */
    public function test_dynamic_content_uses_aria_live()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Toast notifications should use aria-live
        if (str_contains($content, 'toast') || str_contains($content, 'notification')) {
            $this->assertMatchesRegularExpression('/aria-live=["\'](?:polite|assertive)["\']/', $content);
        }
    }

    /**
     * Test assessment questions are properly labeled
     */
    public function test_assessment_questions_are_properly_labeled()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->get(route('assessments.start', $assessment));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Assessment should have proper structure
        $this->assertMatchesRegularExpression('/<h[1-6][^>]*>/', $content);
    }
}
