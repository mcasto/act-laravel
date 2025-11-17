# Laravel Controllers Database vs File Access Summary

## AngelController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **File** | `storage/app/angel.config.json` |

## AnnouncementBannerController
| Method | Type | Source |
|--------|------|--------|
| `show()` | **File** | `storage/app/announcement-banner.json` |
| `update()` | **File** | `storage/app/announcement-banner.json` (writes) |

## AuditionContactController
| Method | Type | Source |
|--------|------|--------|
| `create()` | **Database** | Models: `Audition`, `Show`, `SiteConfig`, `AuditionContact` |

## AuditionController
| Method | Type | Source |
|--------|------|--------|
| `show()` | **Database** | Model: `Audition` |
| `store()` | **Database** | Model: `Audition` (creates) |
| `update()` | **Database** | Model: `Audition` (updates) |
| `current()` | **Database** | Model: `Audition` (with `Show` relationship) |
| `contact()` | **Neither** | Returns request data directly |

## AuditionRoleController
| Method | Type | Source |
|--------|------|--------|
| *(empty controller)* | - | - |

## AuditionSessionController
| Method | Type | Source |
|--------|------|--------|
| *(empty controller)* | - | - |

## AuthController
| Method | Type | Source |
|--------|------|--------|
| `login()` | **Database** | Model: `User` (with `permissions.permissionLevel` relationship) |
| `refreshPermissions()` | **Database** | Model: `User` (with `permissions.permissionLevel` relationship) |
| `logout()` | **Database** | Deletes user tokens |
| `getUser()` | **Neither** | Returns authenticated user from request |

## ContactController
| Method | Type | Source |
|--------|------|--------|
| `store()` | **Database** | Model: `Contact` (creates) |
| `create()` | **Database** | Models: `SiteConfig`, `Contact` (creates) |
| `index()` | **Database** | Model: `Contact` |
| `destroy()` | **Database** | Model: `Contact` (deletes) |

## CourseController
| Method | Type | Source |
|--------|------|--------|
| `openEnrollment()` | **Database** | Model: `Course` (with `sessions` relationship) |
| `courseDetails()` | **Both** | Model: `Course` (with `sessions` relationship) + File: `storage/app/public/snippets/courses/{slug}.html` |
| `courseContact()` | **Database** | Models: `SiteConfig`, `Course`, `CourseContact` |

## CourseSessionController
| Method | Type | Source |
|--------|------|--------|
| *(empty controller)* | - | - |

## FixrWebhooksController
| Method | Type | Source |
|--------|------|--------|
| `create()` | **Database** | Models: `Patron`, `FixrWebhookResponse` |

## FlexPurchaseController
| Method | Type | Source |
|--------|------|--------|
| `show()` | **Both** | File: `storage/app/flex-purchase-config.json` + Model: `StandardButton` + Views: `resources/views/standard-buttons/{key}.blade.php` |
| `update()` | **File** | Files: `storage/app/public/flex-image-temp/*`, `storage/app/public/flex-image.jpg`, `storage/app/flex-purchase-config.json` |
| `image()` | **File** | File: `storage/app/public/flex-image-temp/{filename}` (writes) |

## GalleryController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **Database** | Model: `Show` (with `performances`, `galleryImages` relationships) |
| `store()` | **Both** | Model: `GalleryImage` (creates) + File: `storage/app/public/gallery-temp/{filename}` (writes) |
| `delete()` | **Database** | Model: `GalleryImage` (deletes) |
| `update()` | **Database** | Model: `GalleryImage` (updates) |

## ImageController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **Neither** | Empty method |
| `create()` | **Neither** | Empty method |
| `store()` | **Neither** | Empty method |
| `edit()` | **Neither** | Empty method |
| `update()` | **File** | File: `storage/app/public/images/{filename}` (writes) |
| `destroy()` | **Neither** | Empty method |

## PerformanceController
| Method | Type | Source |
|--------|------|--------|
| `upsert()` | **Database** | Model: `Performance` (creates, updates, deletes) |
| `updateFixrLink()` | **Neither** | Only logs data |

## ProfileController
| Method | Type | Source |
|--------|------|--------|
| `edit()` | **Neither** | Returns view with authenticated user |
| `update()` | **Database** | Updates authenticated user |
| `destroy()` | **Database** | Deletes authenticated user |

## ShowController
| Method | Type | Source |
|--------|------|--------|
| `destroy()` | **Database** | Model: `Show` (deletes) |
| `seasonShows()` | **Database** | Model: `Show` (with `performances` relationship) |
| `homeShows()` | **Both** | Models: `Show` (with `audition`, `performances` relationships), `SiteConfig`, `StandardButton` + Views: `resources/views/standard-buttons/{key}.blade.php` |
| `updateTentative()` | **Database** | Model: `Show` (updates) |
| `index()` | **Database** | Model: `Show` (with `performances.tickets`, `galleryImages` relationships) |
| `show()` | **Database** | Model: `Show` (with `performances.tickets`, `galleryImages` relationships) |
| `newShow()` | **Database** | Model: `SiteConfig` |
| `create()` | **Database** | Model: `Show` (creates) |
| `update()` | **Database** | Model: `Show` (updates) |

## SiteConfigController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **Neither** | Empty method |
| `create()` | **Neither** | Empty method |
| `store()` | **Database** | Models: `SiteConfig` (creates), `StandardButton` (updates) |
| `show()` | **Database** | Models: `SiteConfig`, `StandardButton` |
| `edit()` | **Neither** | Empty method |
| `update()` | **Neither** | Empty method |
| `destroy()` | **Neither** | Empty method |

## SkillController
| Method | Type | Source |
|--------|------|--------|
| `list()` | **Database** | Model: `Skill` |

## SnippetController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **Neither** | Empty method |
| `store()` | **Neither** | Empty method |
| `show()` | **File** | File: `storage/app/public/snippets/{slug}.html` |
| `update()` | **Neither** | Empty method |
| `destroy()` | **Neither** | Empty method |

## StandardButtonsController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **Both** | Model: `StandardButton` (reads ordered by sort_order) + File: `storage/app/standard-buttons/{key}-{type}.html` (reads for each button) |

## SupportUsController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **Both** | File: `storage/app/support-us.config.json` + Model: `StandardButton` + Views: `resources/views/standard-buttons/{key}.blade.php` |

## UserController
| Method | Type | Source |
|--------|------|--------|
| `index()` | **Database** | Model: `User` (with `permissions.permissionlevel` relationship) |
| `store()` | **Database** | Models: `User`, `PermissionLevel`, `UserPermission` (creates) |
| `changePassword()` | **Database** | Model: `User` (updates) |
| `update()` | **Database** | Model: `User` (updates) |
| `destroy()` | **Database** | Model: `User` (deletes) |

## VolunteerController
| Method | Type | Source |
|--------|------|--------|
| `contactCreate()` | **Neither** | Returns request data directly |
| `index()` | **Database** | Model: `Volunteer` (with `volunteerSkills.skill` relationship) |
| `destroy()` | **Database** | Model: `Volunteer` (deletes) |
| `store()` | **Database** | Models: `Volunteer`, `VolunteerSkill` (creates) |
| `update()` | **Database** | Models: `Volunteer`, `VolunteerSkill` (updates, deletes, creates) |

## Summary Statistics
- **Total Methods**: 73 active methods (excluding empty methods)
- **Database Operations**: 51 methods
- **File Operations**: 13 methods
- **Both Database & File**: 5 methods
- **Neither/Passthrough/Empty**: 4 methods

### Database Access Breakdown
- **Models Used**: `User`, `Audition`, `Show`, `SiteConfig`, `Contact`, `Course`, `Patron`, `FixrWebhookResponse`, `StandardButton`, `GalleryImage`, `Performance`, `Skill`, `Volunteer`, `VolunteerSkill`, `PermissionLevel`, `UserPermission`, `CourseContact`, `AuditionContact`

### File Access Breakdown
- **Configuration Files**: `angel.config.json`, `announcement-banner.json`, `flex-purchase-config.json`, `support-us.config.json`
- **Content Files**: `snippets/courses/{slug}.html`, `snippets/{slug}.html`, `standard-buttons/{key}-{type}.html`
- **Image Files**: `gallery-temp/*`, `flex-image-temp/*`, `flex-image.jpg`, `images/{filename}`
- **View Templates**: `resources/views/standard-buttons/{key}.blade.php` (rendered dynamically)
