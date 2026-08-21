# MYADS Social Auto Poster Plugin

> **Automated real-time publishing of forum topics, videos, products, news, and directory listings to Telegram Channels, Facebook Pages, X (Twitter), and Discord Webhooks.**

---

## 📌 Features

* **Telegram Bot Integration:**
  * Instant publishing to Telegram Channels and Supergroups using Telegram Bot API.
  * Direct photo cards (`sendPhoto`) with custom captions and interactive inline keyboard URL buttons.
  * 1-Click live connection test in admin control panel.

* **Facebook Pages API:**
  * Seamless publishing via Facebook Graph API (`v19.0`).
  * Support for Page feed posts and Photo cards with permanent Page Access Tokens.

* **X (Twitter) API v2:**
  * Automated tweet dispatch with OAuth 1.0a authentication and Bearer Tokens.
  * Smart 280-character trimming and dynamic hashtag formatting.

* **Discord Webhooks:**
  * High-definition Rich Embeds with thumbnail artwork, author metadata, color palettes, and direct links.

* **ADStn Network (adstn.ovh) Integration:**
  * Direct real-time publishing to **ADStn Platform** (`https://www.adstn.ovh`) via official Developer REST API v1 (`POST /api/developer/v1/me/content`).
  * Seamless OAuth Developer Access Token authentication (`user.content.write` scope).
  * 1-Click live account connection & verification test.

* **Smart Triggers & Eloquent Listeners:**
  * Auto-publish on creation for Forum Topics (`ForumTopic`), Videos & Clips (`Status`), Store Products (`Product`), News Articles (`News`), and Web Directory listings (`Directory`).

* **Interactive Admin Control Panel:**
  * Modern `@.superdesign` glassmorphic interface with 4-card live KPI statistics strip.
  * Live Manual Broadcast composer with real-time card preview simulator.
  * Complete transmission history log with status codes, error details, response payload viewer modal, single-click retry, and CSV export.

---

## 🚀 Installation & Activation

1. Place the plugin directory under `plugins/myads-social-auto-poster`.
2. Activate the plugin via **Admin Control Panel > Plugins** (`/admin/plugins`).
3. Navigate to **Social Auto Poster** from the admin sidebar menu (`/admin/social-auto-poster`).
4. Configure your desired social channel credentials and click **Save All Settings**.

---

## 📄 License

MIT License. Copyright (c) 2026 MrGhozzi / MYADS Platform.
