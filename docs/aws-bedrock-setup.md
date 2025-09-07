# Setting Up Amazon Bedrock for HTML Editor

This guide explains how to set up Amazon Bedrock and obtain the necessary credentials for the Bedrock HTML Editor package.

## Prerequisites

1. An AWS account with access to Amazon Bedrock
2. AWS CLI installed and configured with appropriate permissions
3. Access to the AWS Management Console

## Step 1: Enable Amazon Bedrock Access

1. Sign in to the AWS Management Console
2. Navigate to the Amazon Bedrock service
3. If this is your first time using Bedrock, you'll need to request access to the models:
   - Click on "Model access" in the left navigation
   - Select the models you want to use (at minimum, select "Anthropic Claude")
   - Click "Request model access"
   - Wait for approval (usually immediate for most models)

## Step 2: Create a Bedrock Agent

1. In the Amazon Bedrock console, navigate to "Agents" in the left sidebar
2. Click "Create agent"
3. Configure your agent:
   - **Name**: Give your agent a descriptive name (e.g., "HTML Editor Agent")
   - **Description**: Add an optional description
   - **IAM Role**: Create or select an IAM role with appropriate permissions
   - **Foundation Model**: Select "Anthropic Claude" (preferably Claude 3 Sonnet or higher)
   - **Instructions**: Provide instructions for your agent, such as:
     ```
     You are an expert HTML/CSS developer. Your task is to modify or create HTML content based on user prompts.
     Always ensure the output is valid HTML with proper structure and semantic markup.
     When modifying HTML, maintain the original structure and functionality while implementing the requested changes.
     When creating HTML, create complete, valid HTML documents with proper structure and inline CSS styling.
     ```
    - **Advanced settings**: 
    - **Code Interpreter**: Set to **Enabled** - This allows the agent to write, run, test, and troubleshoot HTML, CSS, and JavaScript code in a secure environment, ensuring higher quality output
   - **User Input**: Set to **Enabled** - This allows the agent to ask clarifying questions when it doesn't have enough information to properly modify or create HTML content
  

4. Click "Create agent"

5. After creation, you'll be redirected to the agent details page
6. **Important**: Note the **Agent ID** displayed at the top of the page. It will look something like:
   ```
   ABCDEFGHIJ1234567890
   ```
   This is your `BEDROCK_AGENT_ID` value.

## Step 3: Create an Agent Alias

1. In your agent's details page, navigate to the "Aliases" tab
2. Click "Create alias"
3. Configure your alias:
   - **Name**: Give your alias a descriptive name (e.g., "Production")
   - **Description**: Add an optional description
   - **Agent version**: Select the version of your agent to use

4. Click "Create alias"

5. After creation, you'll see the alias in the list
6. **Important**: Note the **Alias ID** displayed in the table. It will look something like:
   ```
   ZYXWVUTSRQ0987654321
   ```
   This is your `BEDROCK_AGENT_ALIAS_ID` value.

## Step 4: Create Action Groups

Action Groups define specific functions that your agent can use to perform tasks. For an HTML editor agent, you should create at least one Action Group:

1. In your agent's details page, navigate to the "Action groups" tab
2. Click "Add" to create a new Action Group
3. Configure your Action Group:
   - **Name**: Give your action group a descriptive name (e.g., "HTMLEditorActions")
   - **Description**: Add a brief description (e.g., "Actions for HTML editing and creation")

4. For **Action group type**, select **"Define with API schemas"**
   - This option allows you to define a complete OpenAPI schema for your HTML editing functions

5. After selecting "Define with API schemas", you'll be asked to provide an OpenAPI schema in the next screen. Copy and paste the following schema into the schema editor:

```json
{
  "openapi": "3.0.0",
  "info": {
    "title": "HTML Editor API",
    "version": "1.0.0",
    "description": "API for modifying and creating HTML content"
  },
  "paths": {
    "/modifyHtml": {
      "description": "Endpoint for modifying existing HTML content",
      "post": {
        "summary": "Modify existing HTML content",
        "description": "Modifies HTML content based on user instructions",
        "operationId": "modifyHtml",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["html", "instructions"],
                "properties": {
                  "html": {
                    "type": "string",
                    "description": "The original HTML content to modify"
                  },
                  "instructions": {
                    "type": "string",
                    "description": "Instructions for how to modify the HTML"
                  }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Modified HTML content",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "modifiedHtml": {
                      "type": "string"
                    }
                  }
                }
              }
            }
          }
        }
      }
    },
    "/createHtml": {
      "description": "Endpoint for creating new HTML content",
      "post": {
        "summary": "Create new HTML content",
        "description": "Creates new HTML content based on user instructions",
        "operationId": "createHtml",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["instructions"],
                "properties": {
                  "instructions": {
                    "type": "string",
                    "description": "Instructions for what HTML to create"
                  },
                  "template": {
                    "type": "string",
                    "description": "Optional template to start with"
                  }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Created HTML content",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "html": {
                      "type": "string"
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
```

6. After adding the schema, you'll be asked for **Action group invocation**. Select **"Return control"**:
   - This option means the agent will prompt for function details in the test window
   - No Lambda function needs to be created or configured
   - This is ideal for testing and development with the Bedrock HTML Editor package

7. Click "Add" to create the Action Group

> **Note**: The Action Group defines what functions your agent can call, but you don't need to implement the actual Lambda function for testing purposes. The Bedrock HTML Editor package will handle the HTML processing directly through the BedrockAgentService.

## Step 5: Configure Your Environment Variables

Add the following to your `.env` file:

```
BEDROCK_AGENT_ID=your_agent_id        # Replace with the Agent ID from Step 2
BEDROCK_AGENT_ALIAS_ID=your_agent_alias_id  # Replace with the Alias ID from Step 3
```

## Step 5: Prepare Your Agent

After creating your agent, you need to "prepare" it to keep its details up to date and make it ready for use:

1. In your agent's details page, click the **Prepare** button in the top-right corner

2. The preparation process will start, which includes:
   - Validating your agent's configuration
   - Compiling the agent's knowledge base (if any)
   - Setting up the action groups
   - Preparing the agent for testing and deployment

3. Wait for the preparation to complete - this typically takes 1-3 minutes

4. You'll see a success message when the preparation is complete

> **Important**: You must prepare your agent whenever you make changes to its configuration, including:
> - Modifying the agent's instructions
> - Adding or updating action groups
> - Changing the foundation model
> - Updating any other settings

## Step 6: Test Your Agent

After preparing your agent, you should test it to ensure it's working correctly:

1. In your agent's details page, click the **Test** tab

2. In the test window, enter a prompt like: "Create a simple HTML page with a header and a paragraph about cats"

3. The agent will process your request and identify which function to call

4. For "Return control" action groups, you'll see a prompt like:
   ```
   Provide action group output
   Provide outputs for this action group function:
   /createHtml()
   ```
   or
   ```
   Provide action group output
   Provide outputs for this action group function:
   /modifyHtml()
   ```

5. When you see this prompt, you should provide a sample response that the function would return. For example:

   For `/createHtml()`, enter:
   ```json
   {
     "html": "<!DOCTYPE html>\n<html>\n<head>\n  <title>Cat Page</title>\n</head>\n<body>\n  <h1>All About Cats</h1>\n  <p>Cats are wonderful pets known for their independence and playful nature.</p>\n</body>\n</html>"
   }
   ```

   For `/modifyHtml()`, enter:
   ```json
   {
     "modifiedHtml": "<!DOCTYPE html>\n<html>\n<head>\n  <title>Modified Page</title>\n</head>\n<body>\n  <h1>Modified Content</h1>\n  <p>This HTML has been modified according to instructions.</p>\n</body>\n</html>"
   }
   ```

6. Click **Submit** after entering your response

7. The agent will continue the conversation using the HTML you provided

8. Verify that the agent correctly identifies when to use the `modifyHtml` or `createHtml` functions based on your prompts

## Step 7: Set Up AWS Credentials

Ensure your AWS credentials are properly configured:

```
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=us-east-1  # Or your preferred region where Bedrock is available
```

## Troubleshooting

### "Failed to fetch" Error During Testing

If you encounter a "Failed to fetch" error when testing your agent, try these solutions:

1. **Check your internet connection**
   - Ensure you have a stable internet connection
   - Try refreshing the page or restarting your browser

2. **Verify AWS region compatibility**
   - Make sure you're using a region where Bedrock is fully available (e.g., us-east-1, us-west-2)
   - Some regions may have limited Bedrock functionality

3. **Check IAM permissions**
   - The custom IAM user created by the Bedrock agent creation process needs the following policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "bedrock:InvokeAgent",
                "bedrock:InvokeModel",
                "bedrock:InvokeModelWithResponseStream"
            ],
            "Resource": [
                "arn:aws:bedrock:*:*:agent/*",
                "arn:aws:bedrock:*::foundation-model/*",
                "arn:aws:bedrock:*:*:inference-profile/*"
            ]
        }
    ]
}
```

   - To add this policy:
     1. Go to the IAM console
     2. Find the role created for your Bedrock agent
     3. Click "Add permissions" > "Create inline policy"
     4. Switch to the JSON editor and paste the policy above
     5. Name the policy (e.g., "BedrockAgentInvokePolicy") and click "Create policy"

4. **Re-prepare your agent**
   - To re-prepare your agent after changing IAM permissions:
     1. Navigate to the Amazon Bedrock console
     2. Click on "Agents" in the left sidebar
     3. From the agent list, click on "**Edit in Agent Builder**" for your agent
     4. In the Agent Builder interface, you'll see a **Prepare** button in the top-right corner of the page
     5. Click the **Prepare** button
     6. A preparation dialog will appear showing the progress
     7. Wait for the preparation to complete (usually 1-3 minutes)
     8. You'll see a success message when complete
   - If the preparation fails, check the error message for details about what went wrong
   
   - **If the Prepare button is disabled:**
     1. Make sure you've made at least one change to the agent configuration
     2. Check if there's an ongoing preparation process (look for status indicators)
     3. Try making a small change to the agent (e.g., edit and save the description)
     4. Ensure you have the necessary permissions to prepare the agent
     5. Try refreshing the page or logging out and back in
     6. As a last resort, create a new version of your agent by clicking "Create draft" or similar option

5. **Try a different browser**
   - Some browser extensions or settings might interfere with the AWS console

6. **Check AWS service health**
   - Visit the [AWS Service Health Dashboard](https://health.aws.amazon.com/health/status) to check if there are any ongoing issues with Bedrock

7. **Simplify your test prompt**
   - Start with a very simple prompt like "Create a basic HTML page"
   - Gradually increase complexity once basic functionality works

### Agent Not Found Error

If you receive an error like "Agent not found", check:
- The Agent ID is correct
- The Agent Alias ID is correct
- Your AWS credentials have permission to access the agent
- You're using the correct AWS region

### Permission Issues

If you encounter permission errors:
1. Verify your IAM role has the following permissions:
   - `bedrock:InvokeAgent`
   - `bedrock:InvokeModel`
   - `bedrock:ListFoundationModels`

2. Check your AWS credentials are correctly configured in your environment

### Model Access Issues

If you receive errors about model access:
1. Go to the "Model access" section in the Bedrock console
2. Verify you have requested and been granted access to the models you're trying to use

## Additional Resources

- [Amazon Bedrock Documentation](https://docs.aws.amazon.com/bedrock/)
- [Amazon Bedrock Agents Documentation](https://docs.aws.amazon.com/bedrock/latest/userguide/agents.html)
- [AWS IAM Documentation](https://docs.aws.amazon.com/IAM/latest/UserGuide/)
