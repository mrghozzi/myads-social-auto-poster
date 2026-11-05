# Changelog — Social Auto Poster Plugin

All notable changes to the `myads-social-auto-poster` plugin will be documented in this file.

## [1.1.0] - 2026-11-05
### Added / Enhanced
- **MYADS v4.7.0 Platform Compatibility**: Tested and verified multi-channel social publishing under Laravel 12.
- **Max Version Bump**: Updated `max_myads` requirement to `4.7.x`.
- **Navigation Integration**: Explicitly registered `settings_url` to `/admin/social-auto-poster`.

## [1.0.0] - 2026-08-22
### Added
* Initial release of **MYADS Social Auto Poster**.
* Modular architecture under `plugins/myads-social-auto-poster`.
* Telegram Bot API service with photo and inline URL button support.
* Facebook Graph API service for automated Page posts.
* X (Twitter) API v2 service with OAuth 1.0a signature support and 280-char formatting.
* Discord Webhook service with rich embed cards.
* Central Orchestrator service with custom placeholder template formatting (`{title}`, `{url}`, `{summary}`, `{sitename}`, `{author}`, `{hashtags}`).
* Model observers for `ForumTopic`, `Status` (videos/clips), `Product`, `News`, and `Directory`.
* Glassmorphic `@.superdesign` admin dashboard with 4 KPI metrics, live preview composer, channel test buttons, and transmission history log.
* Full Arabic (`ar`) and English (`en`) localization.
* Automated PHPUnit test coverage (`SocialAutoPosterTest.php`).
