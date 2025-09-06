<?php

namespace Prasso\BedrockHtmlEditor\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\TestCase;
use Prasso\BedrockHtmlEditor\Services\BedrockAgentService;
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;
use Prasso\BedrockHtmlEditor\Services\S3StorageService;

class HtmlProcessingServiceTest extends TestCase
{
    protected $bedrockAgentService;
    protected $s3StorageService;
    protected $htmlProcessingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the BedrockAgentService
        $this->bedrockAgentService = Mockery::mock(BedrockAgentService::class);
        
        // Mock the S3StorageService
        $this->s3StorageService = Mockery::mock(S3StorageService::class);
        
        // Create the HtmlProcessingService with mocked dependencies
        $this->htmlProcessingService = new HtmlProcessingService(
            $this->bedrockAgentService,
            $this->s3StorageService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testModifyHtmlSuccess()
    {
        // Sample HTML and prompt
        $html = '<html><body><h1>Hello World</h1></body></html>';
        $prompt = 'Change the heading to "Welcome to Prasso"';
        $sessionId = 'test-session-123';
        
        // Expected modified HTML
        $modifiedHtml = '<html><body><h1>Welcome to Prasso</h1></body></html>';
        
        // Mock BedrockAgentService invokeAgent method
        $this->bedrockAgentService->shouldReceive('invokeAgent')
            ->once()
            ->with(Mockery::any(), $sessionId)
            ->andReturn([
                'success' => true,
                'completion' => $modifiedHtml,
                'session_id' => $sessionId,
            ]);
        
        // Call the method under test
        $result = $this->htmlProcessingService->modifyHtml($html, $prompt, $sessionId);
        
        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals($html, $result['original_html']);
        $this->assertEquals($modifiedHtml, $result['modified_html']);
        $this->assertEquals($sessionId, $result['session_id']);
    }

    public function testCreateHtmlSuccess()
    {
        // Sample prompt
        $prompt = 'Create a simple landing page for a coffee shop';
        $sessionId = 'test-session-456';
        
        // Expected HTML
        $createdHtml = '<html><head><title>Coffee Shop</title></head><body><h1>Welcome to our Coffee Shop</h1></body></html>';
        
        // Mock BedrockAgentService invokeAgent method
        $this->bedrockAgentService->shouldReceive('invokeAgent')
            ->once()
            ->with(Mockery::any(), $sessionId)
            ->andReturn([
                'success' => true,
                'completion' => $createdHtml,
                'session_id' => $sessionId,
            ]);
        
        // Call the method under test
        $result = $this->htmlProcessingService->createHtml($prompt, $sessionId);
        
        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals($createdHtml, $result['html']);
        $this->assertEquals($sessionId, $result['session_id']);
    }

    public function testSaveHtml()
    {
        // Sample data
        $html = '<html><body><h1>Test Content</h1></body></html>';
        $filename = 'test-page.html';
        $siteId = 1;
        $metadata = ['title' => 'Test Page', 'author' => 'Test User'];
        
        // Expected result
        $expectedResult = [
            'success' => true,
            'path' => 'test-site/pages/test-page.html',
            'url' => 'https://example.com/storage/test-site/pages/test-page.html',
            'size' => strlen($html),
        ];
        
        // Mock S3StorageService storeHtml method
        $this->s3StorageService->shouldReceive('storeHtml')
            ->once()
            ->with($html, $filename, $siteId, $metadata)
            ->andReturn($expectedResult);
        
        // Call the method under test
        $result = $this->htmlProcessingService->saveHtml($html, $filename, $siteId, $metadata);
        
        // Assert the result
        $this->assertEquals($expectedResult, $result);
    }

    public function testExtractHtmlFromResponse()
    {
        // Sample response with markdown code blocks
        $response = "Here's the HTML code:\n\n```html\n<html><body><h1>Test</h1></body></html>\n```\n\nYou can use this HTML.";
        
        // Call the protected method using reflection
        $reflection = new \ReflectionClass($this->htmlProcessingService);
        $method = $reflection->getMethod('extractHtmlFromResponse');
        $method->setAccessible(true);
        $result = $method->invoke($this->htmlProcessingService, $response);
        
        // Assert the result
        $this->assertEquals('<html><body><h1>Test</h1></body></html>', $result);
    }

    public function testValidateHtml()
    {
        // Valid HTML
        $validHtml = '<!DOCTYPE html><html><head><title>Test</title></head><body><h1>Test</h1></body></html>';
        
        // Invalid HTML
        $invalidHtml = '<html><body><h1>Test</h1><div></body></html>';
        
        // Test valid HTML
        $validResult = $this->htmlProcessingService->validateHtml($validHtml);
        $this->assertTrue($validResult['valid']);
        $this->assertEmpty($validResult['errors']);
        
        // Test invalid HTML
        $invalidResult = $this->htmlProcessingService->validateHtml($invalidHtml);
        $this->assertFalse($invalidResult['valid']);
        $this->assertNotEmpty($invalidResult['errors']);
    }
}
