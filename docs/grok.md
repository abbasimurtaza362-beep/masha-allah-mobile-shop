# MobiSaathi AI Support Setup

The website includes a server-side MobiSaathi customer support assistant. The browser never receives the xAI API key.

## Configure the API key securely

1. Preferred production option: set `XAI_API_KEY`, `GROK_API_KEY`, or `GROQ_API_KEY` in the Apache/XAMPP process environment outside the web root. Keys beginning with `gsk_` are automatically routed to the Groq OpenAI-compatible endpoint; other keys use xAI Responses.
2. Local XAMPP option: copy `config/xai.local.example.php` to `config/xai.local.php` and set its `api_key` value. This local file is ignored by Git and blocked from direct web access by the configuration access rules.
3. Never place a real key in `config/xai.php`, `config/.env.example`, a project ZIP archive, or a repository.
4. Restart Apache after changing the environment value or local configuration.
5. Open the website and click the round MobiSaathi button at the bottom-right.

The public endpoint accepts same-origin POST requests only and has persistent IP-based rate limiting. Treat the API key as a billable production secret: rotate it immediately if it is ever copied into a shared archive or repository.

## API

The integration routes xAI keys to the xAI Responses API and `gsk_` keys to Groq's OpenAI-compatible chat-completions API. The API call is made only by PHP on the server.
