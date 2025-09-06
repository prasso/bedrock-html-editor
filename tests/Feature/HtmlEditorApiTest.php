<?php

namespace Prasso\BedrockHtmlEditor\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\SitePages;
use Prasso\BedrockHtmlEditor\Models\HtmlModification;
use Prasso\BedrockHtmlEditor\Models\HtmlTemplate;
use Laravel\Sanctum\Sanctum;

class HtmlEditorApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $site;
    protected $page;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Create a test site
        $this->site = Site::factory()->create([
            'site_name' => 'test-site',
            'description' => 'Test Site',
        ]);

        // Create a test page
        $this->page = SitePages::factory()->create([
            'fk_site_id' => $this->site->id,
            'section' => 'test-page',
            'title' => 'Test Page',
            'description' => '<html><body><h1>Test Page</h1></body></html>',
        ]);

        // Authenticate the user
        Sanctum::actingAs($this->user);
    }

    public function testModifyHtmlEndpoint()
    {
        // Mock the BedrockAgentService in the container
        $this->mock('Prasso\BedrockHtmlEditor\Services\BedrockAgentService', function ($mock) {
            $mock->shouldReceive('invokeAgent')
                ->once()
                ->andReturn([
                    'success' => true,
                    'completion' => '<html><body><h1>Modified Test Page</h1></body></html>',
                    'session_id' => 'test-session-123',
                ]);
        });

        // Make the API request
        $response = $this->postJson('/api/bedrock-html-editor/modify', [
            'html' => '<html><body><h1>Test Page</h1></body></html>',
            'prompt' => 'Change the heading to "Modified Test Page"',
            'site_id' => $this->site->id,
            'page_id' => $this->page->id,
            'title' => 'Modified Test Page',
        ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'html' => '<html><body><h1>Modified Test Page</h1></body></html>',
            ]);

        // Assert the database has the record
        $this->assertDatabaseHas('bhe_html_modifications', [
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'page_id' => $this->page->id,
            'title' => 'Modified Test Page',
        ]);
    }

    public function testCreateHtmlEndpoint()
    {
        // Mock the BedrockAgentService in the container
        $this->mock('Prasso\BedrockHtmlEditor\Services\BedrockAgentService', function ($mock) {
            $mock->shouldReceive('invokeAgent')
                ->once()
                ->andReturn([
                    'success' => true,
                    'html' => '<html><body><h1>New Test Page</h1></body></html>',
                    'session_id' => 'test-session-456',
                ]);
        });

        // Make the API request
        $response = $this->postJson('/api/bedrock-html-editor/create', [
            'prompt' => 'Create a page with heading "New Test Page"',
            'site_id' => $this->site->id,
            'title' => 'New Test Page',
        ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Assert the database has the record
        $this->assertDatabaseHas('bhe_html_modifications', [
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'title' => 'New Test Page',
        ]);
    }

    public function testGetModificationHistoryEndpoint()
    {
        // Create some test modifications
        HtmlModification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
        ]);

        // Make the API request
        $response = $this->getJson('/api/bedrock-html-editor/modifications?site_id=' . $this->site->id);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'modifications');
    }

    public function testGetModificationEndpoint()
    {
        // Create a test modification
        $modification = HtmlModification::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'title' => 'Test Modification',
        ]);

        // Make the API request
        $response = $this->getJson('/api/bedrock-html-editor/modifications/' . $modification->id);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'modification' => [
                    'id' => $modification->id,
                    'title' => 'Test Modification',
                ],
            ]);
    }

    public function testApplyModificationEndpoint()
    {
        // Create a test modification
        $modification = HtmlModification::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'modified_html' => '<html><body><h1>Applied Modification</h1></body></html>',
        ]);

        // Make the API request
        $response = $this->postJson('/api/bedrock-html-editor/modifications/' . $modification->id . '/apply', [
            'page_id' => $this->page->id,
        ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Assert the page was updated
        $this->page->refresh();
        $this->assertEquals('<html><body><h1>Applied Modification</h1></body></html>', $this->page->description);

        // Assert the modification was updated
        $modification->refresh();
        $this->assertEquals($this->page->id, $modification->page_id);
        $this->assertTrue($modification->is_published);
    }

    public function testTemplateEndpoints()
    {
        // Create a test template
        $template = HtmlTemplate::factory()->create([
            'name' => 'Test Template',
            'category' => 'landing-page',
            'html_content' => '<html><body><h1>Test Template</h1></body></html>',
            'created_by' => $this->user->id,
        ]);

        // Test get templates endpoint
        $response = $this->getJson('/api/bedrock-html-editor/templates');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'templates');

        // Test get template endpoint
        $response = $this->getJson('/api/bedrock-html-editor/templates/' . $template->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'template' => [
                    'id' => $template->id,
                    'name' => 'Test Template',
                ],
            ]);

        // Test create template endpoint
        $response = $this->postJson('/api/bedrock-html-editor/templates', [
            'name' => 'New Template',
            'category' => 'landing-page',
            'html_content' => '<html><body><h1>New Template</h1></body></html>',
        ]);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'template' => [
                    'name' => 'New Template',
                ],
            ]);

        // Test update template endpoint
        $response = $this->putJson('/api/bedrock-html-editor/templates/' . $template->id, [
            'name' => 'Updated Template',
        ]);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'template' => [
                    'name' => 'Updated Template',
                ],
            ]);

        // Test delete template endpoint
        $response = $this->deleteJson('/api/bedrock-html-editor/templates/' . $template->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
