<?php

namespace Prasso\BedrockHtmlEditor\Services;

use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BedrockAgentService
{
    protected BedrockAgentRuntimeClient $client;
    protected array $config;

    public function __construct()
    {
        $this->config = config('bedrock-html-editor');
        $this->initializeClient();
    }

    /**
     * Initialize the Bedrock Agent Runtime client
     */
    protected function initializeClient(): void
    {
        $this->client = new BedrockAgentRuntimeClient([
            'region' => $this->config['aws']['region'],
            'version' => $this->config['aws']['version'],
            'credentials' => [
                'key' => $this->config['aws']['key'],
                'secret' => $this->config['aws']['secret'],
            ],
        ]);
    }

    /**
     * Send a prompt to Bedrock AgentCore and get the response
     *
     * @param string $prompt The prompt to send to the agent
     * @param string|null $sessionId Optional session ID for conversation continuity
     * @return array
     */
    public function invokeAgent(string $prompt, ?string $sessionId = null): array
    {
        try {
            $sessionId = $sessionId ?: $this->generateSessionId();

            $response = $this->client->invokeAgent([
                'agentId' => $this->config['bedrock']['agent_id'],
                'agentAliasId' => $this->config['bedrock']['agent_alias_id'],
                'sessionId' => $sessionId,
                'inputText' => $prompt,
            ]);

            return $this->processAgentResponse($response);

        } catch (AwsException $e) {
            Log::error('Bedrock Agent invocation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'type' => $e->getAwsErrorType(),
                'prompt_length' => strlen($prompt),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getAwsErrorCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error during Bedrock Agent invocation', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt),
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred',
            ];
        }
    }

    /**
     * Process the agent response and extract the completion
     *
     * @param mixed $response
     * @return array
     */
    protected function processAgentResponse($response): array
    {
        try {
            $completion = '';
            $eventStream = $response['completion'];

            foreach ($eventStream as $event) {
                if (isset($event['chunk']['bytes'])) {
                    $completion .= $event['chunk']['bytes'];
                }
            }

            return [
                'success' => true,
                'completion' => $completion,
                'session_id' => $response['sessionId'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Error processing Bedrock Agent response', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process agent response',
            ];
        }
    }

    /**
     * Send a direct model invocation (alternative to agent)
     *
     * @param string $prompt
     * @param string|null $sessionId Optional session ID for tracking
     * @return array
     */
    public function invokeModel(string $prompt, ?string $sessionId = null): array
    {
        try {
            // Create a BedrockRuntime client if needed
            if (!isset($this->runtimeClient)) {
                $sdk = new \Aws\Sdk([
                    'region' => $this->config['aws']['region'],
                    'version' => 'latest',
                    'credentials' => [
                        'key' => $this->config['aws']['key'],
                        'secret' => $this->config['aws']['secret'],
                    ]
                ]);
                
                $this->runtimeClient = $sdk->createBedrockRuntime();
            }
            
            // Log the request
            Log::info('Invoking Bedrock model directly', [
                'model_id' => $this->config['bedrock']['model_id'],
                'prompt_length' => strlen($prompt)
            ]);
            
            // Prepare the request body for Claude model
            $body = json_encode([
                'anthropic_version' => 'bedrock-2023-05-31',
                'max_tokens' => $this->config['bedrock']['max_tokens'],
                'temperature' => $this->config['bedrock']['temperature'],
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

            // Invoke the model directly
            $response = $this->runtimeClient->invokeModel([
                'modelId' => $this->config['bedrock']['model_id'],
                'contentType' => 'application/json',
                'accept' => 'application/json',
                'body' => $body,
            ]);

            // Process the response
            $responseBody = json_decode($response['body']->getContents(), true);
            
            // Log the response structure for debugging
            Log::info('Bedrock model response structure', [
                'keys' => array_keys($responseBody)
            ]);

            if (isset($responseBody['content'][0]['text'])) {
                return [
                    'success' => true,
                    'completion' => $responseBody['content'][0]['text'],
                    'session_id' => $sessionId
                ];
            }

            return [
                'success' => false,
                'error' => 'Invalid response format from model',
            ];

        } catch (AwsException $e) {
            Log::error('Bedrock Model invocation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'type' => $e->getAwsErrorType(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getAwsErrorCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error during Bedrock Model invocation', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred',
            ];
        }
    }

    /**
     * Generate a unique session ID for agent conversations
     *
     * @return string
     */
    public function generateSessionId(): string
    {
        return 'session-' . Str::uuid()->toString();
    }

    /**
     * Validate the Bedrock configuration
     *
     * @return array
     */
    public function validateConfiguration(): array
    {
        $errors = [];

        if (empty($this->config['aws']['key'])) {
            $errors[] = 'AWS access key is required';
        }

        if (empty($this->config['aws']['secret'])) {
            $errors[] = 'AWS secret key is required';
        }

        if (empty($this->config['bedrock']['agent_id'])) {
            $errors[] = 'Bedrock agent ID is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Test the connection to Bedrock
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $response = $this->invokeAgent('Hello, this is a connection test.');
            
            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'Connection successful' : 'Connection failed',
                'details' => $response,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
