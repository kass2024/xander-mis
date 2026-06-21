# WhatsApp pre-screening templates (Meta Business Manager)

Create these in **WhatsApp Manager → Message templates**. The PHP code in `helpers/prescreening_whatsapp_flow.php` and `helpers/prescreening_notify.php` expects the exact names below.

## 1. Invite template (admin sends from Pre-screening page)

| Field | Value |
|--------|--------|
| **Name** | `xander_prescreening_invite` |
| **Category** | Marketing |
| **Language** | English (`en`) — must match `WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG` in `.env` |

**Body**

```
Hello {{1}}, Xander Global Scholars invites you to complete Quick Pre-screening on WhatsApp.

Reply START to begin (15 questions and documents). Type CANCEL to stop.
```

**Variables:** `{{1}}` = student full name (one text parameter).

**Buttons (Quick reply — Custom):**

| Button | Label |
|--------|--------|
| 1 | `START` |
| 2 | `CANCEL` |

### Student flow after invite

1. Admin sends invite → Meta delivers `xander_prescreening_invite`.
2. Student taps **START** (or types `START`) → VPS forwards to `api/prescreening-inbound.php`.
3. Bot asks contact info, then **Study Abroad (1)** or **Work Abroad (2)**.
4. Study: 15 questions + documents. Work: 3 questions + work documents.
5. Student taps **CANCEL** (or types `CANCEL`) anytime → session reset.

### Test invite (CLI)

```bash
php scripts/prescreening_wa_diag.php +250788000000 "Test Name" --send
```

## 2. Received template (after form submission — optional)

| Field | Value |
|--------|--------|
| **Name** | `xander_prescreening_received` |
| **Category** | Utility |
| **Language** | English (`en`) |

**Body**

```
Hello {{1}}, thank you for your pre-screening with Xander Global Scholars.
Reference: {{2}}. Our team will review your answers and documents and contact you soon.
```

**Variables:** `{{1}}` = name, `{{2}}` = reference (e.g. `PS-A1B2C3D4`).

If this template is not approved, the system falls back to session text within the 24-hour window.

## Webhook architecture (Meta URL unchanged)

| Step | What happens |
|------|----------------|
| Admin sends invite | cPanel → Meta Graph API (template) |
| Meta delivery status | Meta → webhook app → `api/prescreening-inbound.php` on cPanel |
| Student taps START | Meta → webhook app → cPanel pre-screening Q&A |

On the **webhook app** (where Meta callback is registered), set in `.env`:

```env
XANDER_PRESCREENING_URL=https://xanderglobalscholars.com/api/prescreening-inbound.php
PRESCREENING_FORWARD_SECRET=same-as-cPanel-PRESCREENING_FORWARD_SECRET
```

On **cPanel** `.env`:

```env
PRESCREENING_FORWARD_SECRET=XgsXander_Fwd_2026_vmi2771824
WHATSAPP_PHONE_NUMBER_ID=1157403454116116
WHATSAPP_ACCESS_TOKEN=...
```

Do **not** change the Meta Business webhook URL.

## SMTP (email invites + submission copies)

In `.env`:

```env
SMTP_HOST=mail.xanderglobalscholars.com
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USERNAME=admissions@xanderglobalscholars.com
SMTP_PASSWORD=your-mailbox-password
```

Test:

```bash
php scripts/smtp_test.php admissions@xanderglobalscholars.com
```

If authentication fails, reset the mailbox password in cPanel → Email Accounts and update `SMTP_PASSWORD`.
