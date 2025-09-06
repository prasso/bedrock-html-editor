<?php

namespace Prasso\BedrockHtmlEditor\Tests\Unit;

use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;
use Aws\Result;
use Mockery;
use PHPUnit\Framework\TestCase;
use Prasso\BedrockHtmlEditor\Services\BedrockAgentService;
use Illuminate\Support\Facades\Config;

class BedrockAgentServiceTest extends TestCase
{
    protected $bedrockAgentService;
    protected $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the AWS config
        Config::shouldReceive('get')
            ->with('bedrock-html-editor')
            ->andReturn([
                'aws' => [
                    'region' => 'us-west-2',
                    'version' => 'latest',
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
                'bedrock' => [
                    'agent_id' => 'test-agent-id',
                    'agent_alias_id' => 'test-agent-alias-id',
                    'model_id' => 'anthropic.claude-3-sonnet-20240229-v1:0',
                    'max_tokens' => 4096,
                    'temperature' => 0.7,
                ],
            ]);
        
        // Mock the BedrockAgentRuntimeClient
        $this->mockClient = Mockery::mock(BedrockAgentRuntimeClient::class);
        
        // Create a partial mock of BedrockAgentService to inject our mock client
        $this->bedrockAgentService = Mockery::mock(BedrockAgentService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        // Set the mock client
        $this->bedrockAgentService->shouldReceive('initializeClient')
            ->once()
            ->andReturn();
        
        // Set the mock client property
        $reflection = new \ReflectionClass($this->bedrockAgentService);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->bedrockAgentService, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testInvokeAgentSuccess()
    {
        // Sample prompt and session ID
        $prompt = 'Create a landing page for a coffee shop';
        $sessionId = 'test-session-123';
        
        // Create a mock response
        $mockResponse = new Result([
            'completion' => $this->createMockEventStream([
                ['chunk' => ['bytes' => 'Here is a landing page for a coffee shop']],
            ]),
            'sessionId' => $sessionId,
        ]);
        
        // Set up the mock client to return our mock response
        $this->mockClient->shouldReceive('invokeAgent')
            ->once()
            ->with([
                'agentId' => 'test-agent-id',
                'agentAliasId' => 'test-agent-alias-id',
                'sessionId' => $sessionId,
                'inputText' => $prompt,
            ])
            ->andReturn($mockResponse);
        
        // Call the method under test
        $result = $this->bedrockAgentService->invokeAgent($prompt, $sessionId);
        
        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals('Here is a landing page for a coffee shop', $result['completion']);
        $this->assertEquals($sessionId, $result['session_id']);
    }

    public function testInvokeAgentError()
    {
        // Sample prompt
        $prompt = 'Create a landing page for a coffee shop';
        
        // Set up the mock client to throw an exception
        $this->mockClient->shouldReceive('invokeAgent')
            ->once()
            ->andThrow(new \Exception('Test error'));
        
        // Call the method under test
        $result = $this->bedrockAgentService->invokeAgent($prompt);
        
        // Assert the result
        $this->assertFalse($result['success']);
        $this->assertEquals('An unexpected error occurred', $result['error']);
    }

    public function testGenerateSessionId()
    {
        // Call the method under test
        $sessionId = $this->bedrockAgentService->generateSessionId();
        
        // Assert the result
        $this->assertStringStartsWith('session-', $sessionId);
        $this->assertGreaterThan(10, strlen($sessionId));
    }

    public function testValidateConfiguration()
    {
        // Set up the config values
        $reflection = new \ReflectionClass($this->bedrockAgentService);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $property->setValue($this->bedrockAgentService, [
            'aws' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
            'bedrock' => [
                'agent_id' => 'test-agent-id',
            ],
        ]);
        
        // Call the method under test
        $result = $this->bedrockAgentService->validateConfiguration();
        
        // Assert the result
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateConfigurationWithErrors()
    {
        // Set up the config values with missing required fields
        $reflection = new \ReflectionClass($this->bedrockAgentService);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $property->setValue($this->bedrockAgentService, [
            'aws' => [
                'key' => '',
                'secret' => 'test-secret',
            ],
            'bedrock' => [
                'agent_id' => '',
            ],
        ]);
        
        // Call the method under test
        $result = $this->bedrockAgentService->validateConfiguration();
        
        // Assert the result
        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']);
        $this->assertContains('AWS access key is required', $result['errors']);
        $this->assertContains('Bedrock agent ID is required', $result['errors']);
    }

    /**
     * Helper method to create a mock event stream
     *
     * @param array $events
     * @return \Generator
     */
    private function createMockEventStream(array $events)
    {
        foreach ($events as $event) {
            yield $event;
        }
    }
}
