# Testing

The Bedrock HTML Editor package includes comprehensive tests to ensure its functionality works correctly. This document explains the available tests and how to run them.

## Test Structure

The tests are organized into two main categories:

1. **Unit Tests**: Test individual components in isolation
2. **Feature Tests**: Test the API endpoints and integration with Laravel

### Unit Tests

Unit tests are located in the `tests/Unit` directory and focus on testing individual services and methods in isolation.

#### HtmlProcessingServiceTest

Tests the HTML processing service functionality:

- `testModifyHtmlSuccess`: Tests modifying HTML based on prompts
- `testCreateHtmlSuccess`: Tests creating new HTML from prompts
- `testSaveHtml`: Tests saving HTML to storage
- `testExtractHtmlFromResponse`: Tests extracting HTML from AI responses
- `testValidateHtml`: Tests validating HTML structure

#### BedrockAgentServiceTest

Tests the Bedrock Agent service:

- `testInvokeAgentSuccess`: Tests successfully invoking the Bedrock agent
- `testInvokeAgentError`: Tests error handling when invoking the Bedrock agent
- `testGenerateSessionId`: Tests session ID generation
- `testValidateConfiguration`: Tests configuration validation
- `testValidateConfigurationWithErrors`: Tests configuration validation with errors

### Feature Tests

Feature tests are located in the `tests/Feature` directory and focus on testing the API endpoints and integration with Laravel.

#### HtmlEditorApiTest

Tests the API endpoints:

- `testModifyHtmlEndpoint`: Tests the HTML modification endpoint
- `testCreateHtmlEndpoint`: Tests the HTML creation endpoint
- `testGetModificationHistoryEndpoint`: Tests retrieving modification history
- `testGetModificationEndpoint`: Tests retrieving a specific modification
- `testApplyModificationEndpoint`: Tests applying a modification to a page
- `testTemplateEndpoints`: Tests template management endpoints

## Running Tests

### Prerequisites

Before running the tests, make sure you have:

1. A testing database configured in your `.env.testing` file
2. Mock AWS credentials for testing (the tests are designed to use mocks rather than actual AWS services)
3. The package dependencies installed via Composer

### Running All Tests

To run all tests for the package:

```bash
cd /Users/bobbiperreault/Sourcecode/faxt/prasso/prasso_api
php artisan test packages/prasso/bedrock-html-editor/tests
```

### Running Unit Tests Only

To run only the unit tests:

```bash
php artisan test packages/prasso/bedrock-html-editor/tests/Unit
```

### Running Feature Tests Only

To run only the feature tests:

```bash
php artisan test packages/prasso/bedrock-html-editor/tests/Feature
```

### Running a Specific Test File

To run a specific test file:

```bash
php artisan test packages/prasso/bedrock-html-editor/tests/Unit/HtmlProcessingServiceTest.php
```

### Running a Specific Test Method

To run a specific test method:

```bash
php artisan test packages/prasso/bedrock-html-editor/tests/Unit/HtmlProcessingServiceTest.php --filter=testModifyHtmlSuccess
```

## Writing Your Own Tests

If you want to extend the package with your own functionality, you should also write tests for it. Here's an example of how to write a unit test for a new method in the HtmlProcessingService:

```php
public function testMyNewMethod()
{
    // Mock the BedrockAgentService
    $bedrockAgentService = Mockery::mock(BedrockAgentService::class);
    
    // Mock the S3StorageService
    $s3StorageService = Mockery::mock(S3StorageService::class);
    
    // Create the HtmlProcessingService with mocked dependencies
    $htmlProcessingService = new HtmlProcessingService(
        $bedrockAgentService,
        $s3StorageService
    );
    
    // Set up expectations for the mocked services
    $bedrockAgentService->shouldReceive('someMethod')
        ->once()
        ->with('someArgument')
        ->andReturn('someResult');
    
    // Call the method under test
    $result = $htmlProcessingService->myNewMethod('someArgument');
    
    // Assert the result
    $this->assertEquals('expectedResult', $result);
}
```

## Test Coverage

The tests aim to cover all the main functionality of the package, including:

- HTML modification and creation
- Template management
- Component management
- S3 storage integration
- Bedrock agent integration
- API endpoints

If you find any bugs or issues, please write a test that reproduces the issue before fixing it. This will help ensure that the issue doesn't reappear in the future.
