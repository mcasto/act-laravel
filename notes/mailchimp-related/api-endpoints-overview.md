The Mailchimp Marketing API offers a wide range of endpoints to manage various aspects of your email marketing efforts. Below is a categorized list of key endpoints along with brief descriptions:

**1. Lists/Audiences**
- **Get Lists** (`GET /lists`): Retrieve information about all lists (audiences) in your account.
- **Add List** (`POST /lists`): Create a new list in your Mailchimp account.
- **Get List Info** (`GET /lists/{list_id}`): Retrieve details of a specific list.
- **Update List** (`PATCH /lists/{list_id}`): Modify settings for a specific list.
- **Delete List** (`DELETE /lists/{list_id}`): Remove a list from your account.

**2. Members**
- **List Members** (`GET /lists/{list_id}/members`): Get information about members in a specific list.
- **Add Member** (`POST /lists/{list_id}/members`): Add a new member to a list.
- **Get Member Info** (`GET /lists/{list_id}/members/{subscriber_hash}`): Retrieve details of a specific list member.
- **Update Member** (`PATCH /lists/{list_id}/members/{subscriber_hash}`): Update information for a specific list member.
- **Delete Member** (`DELETE /lists/{list_id}/members/{subscriber_hash}`): Archive a list member.

**3. Campaigns**
- **List Campaigns** (`GET /campaigns`): Retrieve all campaigns in your account.
- **Add Campaign** (`POST /campaigns`): Create a new Mailchimp campaign.
- **Get Campaign Info** (`GET /campaigns/{campaign_id}`): Retrieve details of a specific campaign.
- **Update Campaign** (`PATCH /campaigns/{campaign_id}`): Modify settings for a specific campaign.
- **Delete Campaign** (`DELETE /campaigns/{campaign_id}`): Remove a campaign from your account.
- **Send Campaign** (`POST /campaigns/{campaign_id}/actions/send`): Send a Mailchimp campaign immediately.
- **Schedule Campaign** (`POST /campaigns/{campaign_id}/actions/schedule`): Schedule a campaign for future delivery.
- **Unschedule Campaign** (`POST /campaigns/{campaign_id}/actions/unschedule`): Unschedule a previously scheduled campaign.
- **Send Test Email** (`POST /campaigns/{campaign_id}/actions/test`): Send a test email for a campaign.

**4. Reports**
- **List Campaign Reports** (`GET /reports`): Retrieve reports for all campaigns.
- **Get Campaign Report** (`GET /reports/{campaign_id}`): Retrieve report details for a specific campaign.
- **List Unsubscribed Members** (`GET /reports/{campaign_id}/unsubscribed`): Get information about members who unsubscribed from a specific campaign.
- **List Click Details** (`GET /reports/{campaign_id}/click-details`): Retrieve information about clicks on specific links in your campaigns.
- **List Open Details** (`GET /reports/{campaign_id}/open-details`): Retrieve detailed information about campaign emails that were opened by list members.

**5. Automations**
- **List Automations** (`GET /automations`): Retrieve all automation workflows in your account.
- **Get Automation Info** (`GET /automations/{workflow_id}`): Retrieve details of a specific automation workflow.
- **List Automation Emails** (`GET /automations/{workflow_id}/emails`): Retrieve a summary of emails in a specific automation workflow.
- **Get Automation Email Info** (`GET /automations/{workflow_id}/emails/{workflow_email_id}`): Retrieve details of a specific email in an automation workflow.
- **Start All Emails in Automation** (`POST /automations/{workflow_id}/actions/start-all-emails`): Start all emails in an automation workflow.
- **Pause All Emails in Automation** (`POST /automations/{workflow_id}/actions/pause-all-emails`): Pause all emails in an automation workflow.

**6. Templates**
- **List Templates** (`GET /templates`): Retrieve all templates in your account.
- **Add Template** (`POST /templates`): Create a new template.
- **Get Template Info** (`GET /templates/{template_id}`): Retrieve details of a specific template.
- **Update Template** (`PATCH /templates/{template_id}`): Modify a specific template.
- **Delete Template** (`DELETE /templates/{template_id}`): Remove a template from your account.

**7. E-commerce**
- **List Stores** (`GET /ecommerce/stores`): Retrieve all e-commerce stores connected to your account.
- **Add Store** (`POST /ecommerce/stores`): Add a new e-commerce store to your account.
- **Get Store Info** (`GET /ecommerce/stores/{store_id}`): Retrieve details of a specific store.
- **Update Store** (`PATCH /ecommerce/stores/{store_id}`): Modify a specific store.
- **Delete Store** (`DELETE /ecommerce/stores/{store_id}`): Remove a store and its associated data from your account.

**8. Webhooks**
- **List Webhooks** (`GET /lists/{list_id}/webhooks`): Retrieve all webhooks configured for a specific list.
- **Add Webhook** (`POST /lists/{list_id}/webhooks`): Create a new webhook for a specific list. 
