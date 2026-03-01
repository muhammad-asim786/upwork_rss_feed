# Upwork RSS Feed Generator for Make.com

This PHP script is designed to work as part of a **Make.com (formerly Integromat)** automation scenario. It takes raw HTML content from an Upwork job search page, parses the data, and generates an RSS feed. If the feed is created for the first time, it also sends the feed URL to the user's email.

## 🔧 Features

- Designed specifically for **Make.com HTTP module**
- Parses Upwork job listings (title, description, publish date)
- Generates a valid RSS feed (XML)
- Sends RSS feed URL via email on first-time creation
- Fully dynamic paths & domain support
- **Vercel**: serverless deploy with Redis (Upstash) + Resend for email
- No external dependencies (pure PHP)

## 🤖 How It Works in Make.com

1. **HTTP Module #1**  
   Fetch HTML from an Upwork job search URL

2. **HTTP Module #2**  
   Send a POST request to this PHP script with:
   - `output` (the raw HTML from the first HTTP request)
   - `feed_name` (used to create a `.xml` file)
   - `email_address` (to email the feed URL)

## 📥 POST Parameters

| Parameter       | Required | Description                                 |
|----------------|----------|---------------------------------------------|
| `output`        | ✅       | The full HTML of the Upwork job listing page |
| `feed_name`     | ✅       | The name of the RSS feed file (without extension) |
| `email_address` | ✅       | The user's email address to send the feed link |

## 🚀 Deploy on Vercel (recommended)

Since the repo is on GitHub, you can deploy in one click:

1. **Import on Vercel**  
   Go to [vercel.com](https://vercel.com) → **Add New** → **Project** → Import your GitHub repo. Leave **Framework Preset** as "Other"; deploy (uses existing `vercel.json`).

2. **Add Redis**  
   In the project: **Storage** → **Create Database** → **Upstash Redis** (or connect existing). Link it to this project so env vars are injected.

3. **Environment variables**  
   **Settings** → **Environment Variables**  
   - Redis: usually auto-injected as `UPSTASH_REDIS_REST_URL` and `UPSTASH_REDIS_REST_TOKEN`.  
   - Email: add `RESEND_API_KEY` from [Resend](https://resend.com). Optional: `FROM_EMAIL` (e.g. `RSS Bot <rss@yourdomain.com>`); if unset, uses `onboarding@resend.dev`.

4. **Use in Make.com**  
   - Webhook: `https://YOUR_PROJECT.vercel.app/api/make_webhook`  
   - RSS feed URL: `https://YOUR_PROJECT.vercel.app/api/feed?name=YourFeedName`

## 📂 Installation (traditional PHP server)

1. Upload the script to your server.
2. Make sure the `/feed/` directory exists and is writable.
3. Use the URL to this script in Make.com's HTTP request module.

## 📧 Email Configuration

- **Vercel**: Uses [Resend](https://resend.com) (set `RESEND_API_KEY` and optionally `FROM_EMAIL`).
- **Traditional server**: Uses PHP's native `mail()`; ensure `mail()` is enabled and configured.

## 💬 Example Response

- `Check your email for the RSS feed URL` (if feed was created)
- `RSS feed updated successfully.` (if feed already existed)

---

## ❤️ Built for Automators

This script is ideal for freelancers, agencies, and developers who want to stay up-to-date with new jobs on Upwork — directly in their RSS reader, automatically via Make.com.
