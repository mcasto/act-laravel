# Laravel Models File Access Summary

## Models with File Access

### Angel
| Property/Method | Type | File Path | Operations |
|----------------|------|-----------|------------|
| *(No file access)* | - | - | - |

### AngelLevel
| Property/Method | Type | File Path | Operations |
|----------------|------|-----------|------------|
| `benefits()` accessor | **Read** | `storage/app/angel-config/{id}.json` | Reads JSON config for each angel level |

**Notes**: The `benefits` attribute is appended to the model and dynamically loads benefit data from individual JSON files based on the angel level ID.

### Audition
| Property/Method | Type | File Path | Operations |
|----------------|------|-----------|------------|
| `getHtmlAttribute()` | **Read** | `storage/app/audition-html/{id}.html` | Reads HTML content from file |
| `setHtmlAttribute()` | **Write** | `storage/app/audition-html/{id}.html` | Stores HTML to attributes (file write handled by events) |
| `saveHtmlToFile()` | **Write/Delete** | `storage/app/audition-html/{id}.html` | Writes HTML to file or deletes if empty |
| `deleteHtmlFile()` | **Delete** | `storage/app/audition-html/{id}.html` | Removes HTML file |
| `createWithHtml()` | **Write** | `storage/app/audition-html/{id}.html` | Creates model and saves HTML file |
| `updateWithHtml()` | **Write** | `storage/app/audition-html/{id}.html` | Updates model and saves HTML file |
| Boot events | **Write/Delete** | `storage/app/audition-html/{id}.html` | Automatic file management on create/update/delete |

**Notes**: This model has sophisticated automatic file management. The `html` attribute is appended and never stored in the database - it's always read from/written to files. Model events (created, updated, deleted) automatically handle file operations.

### StandardButton
| Property/Method | Type | File Path | Operations |
|----------------|------|-----------|------------|
| *(No file access)* | - | - | - |

**Notes**: This model no longer directly manages files. File operations for popup text are handled in the StandardButtonsController instead.

## Models WITHOUT File Access

The following models interact exclusively with the database and do not access the filesystem:

- AuditionContact
- AuditionRole
- AuditionSession
- Contact
- Course
- CourseContact
- CourseSession
- Donation
- DonationLevel
- DonationPerk
- FixrWebhookResponse
- FlexPurchase
- GalleryImage
- MailchimpList
- MailchimpMember
- Patron
- PatronPaymentId
- PaymentMethod
- Performance
- PermissionLevel
- Show
- SiteConfig
- Skill
- StandardButton
- Ticket
- User
- UserPermission
- Volunteer
- VolunteerSkill

## Summary Statistics

- **Total Models Analyzed**: 31
- **Models with File Access**: 2
- **Models without File Access**: 29

## File Access Patterns

### Read Operations
- **AngelLevel**: Reads JSON configuration files per level ID
- **Audition**: Reads HTML content via accessor

### Write Operations
- **Audition**: Writes HTML files on create/update, automatic via events

### Delete Operations
- **Audition**: Deletes HTML file when model is deleted (automatic via events)

### File Naming Conventions
- **Angel levels**: `angel-config/{id}.json`
- **Auditions**: `audition-html/{id}.html`

## Special Behaviors

### Audition Model - Automatic File Management
The Audition model uses Laravel's model events to automatically manage HTML files:
- `created` event → saves HTML to file
- `updated` event → updates HTML file
- `deleted` event → removes HTML file

This ensures the filesystem stays in sync with database operations without manual intervention.

### AngelLevel Model - Dynamic JSON Loading
Loads JSON configuration files based on the model's ID, making benefits data easily editable without database migrations.
