# EduCore — Free App Store Submission Guide

## Overview

This guide covers submitting EduCore to **Amazon Appstore** and **Huawei AppGallery** — both free alternatives to Google Play Store.

---

## Amazon Appstore

**Cost:** Free | **Revenue Share:** 70/30 (80/20 for small businesses under $1M/year)

### Step 1: Create Developer Account

1. Go to [developer.amazon.com/apps-and-games](https://developer.amazon.com/apps-and-games)
2. Sign in with your Amazon account (or create one)
3. Complete the developer registration profile
4. No fees required

### Step 2: Prepare Your APK

The APK is already built. Verify it meets Amazon's requirements:

```bash
# Check APK is properly aligned
zipalign -v 4 app-release.apk app-release-aligned.apk
```

**Requirements:**
- ✅ 64-bit support (arm64-v8a) — already configured
- ✅ Max size: 2.5 GB — your APK is 58 MB
- ✅ Must be zipaligned
- ✅ No .obb expansion files needed

### Step 3: Create App Listing

1. Log in to Amazon Developer Console
2. Click **"Add New App"**
3. Fill in:
   - **App Name:** EduCore — School Management
   - **Category:** Education
   - **Description:** Use content from `PLAY_STORE_LISTING.md`

### Step 4: Upload Assets

| Asset | Dimensions | Format |
|-------|-----------|--------|
| Small Icon | 114 × 114 px | PNG |
| Large Icon | 512 × 512 px | PNG |
| Screenshots | 1920 × 1080 px (landscape) or 1080 × 1920 px (portrait) | PNG/JPEG |
| Feature Graphic | 1024 × 500 px | PNG/JPEG |

### Step 5: Configure Distribution

- **Countries:** Select Nigeria and other target markets
- **Devices:** Fire tablets, Fire TV (if applicable)
- **Pricing:** Free

### Step 6: Privacy & Compliance

- **Privacy Policy URL:** `https://educore.app/privacy`
- **Content Rating:** Complete the questionnaire (Education category)
- **Data Safety:** Declare data collection (name, email, location, camera)

### Step 7: Submit for Review

- Click **"Submit App"**
- Review typically takes **24-72 hours**
- You'll receive email notification of approval

---

## Huawei AppGallery

**Cost:** Free | **Revenue Share:** 80/20 (sometimes 90/10 for promotions)

### Step 1: Create Developer Account

1. Go to [developer.huawei.com/consumer/en](https://developer.huawei.com/consumer/en/)
2. Create a HUAWEI ID
3. Register as a developer
4. Complete identity verification (requires ID document)
5. **Approval time:** ~7 working days

### Step 2: Prepare Your AAB (Recommended) or APK

Huawei prefers AAB format. The workflow already builds both:

```bash
# AAB is already built at:
# mobile/build/app/outputs/bundle/release/app-release.aab
```

**Requirements:**
- ✅ AAB or APK accepted
- ✅ 64-bit support required
- ✅ Target SDK 30+ recommended

### Step 3: Create App in AppGallery Connect

1. Log in to [AppGallery Connect](https://developer.huawei.com/consumer/en/appgallery/)
2. Click **"My Apps" → "Add App"**
3. Fill in:
   - **App Name:** EduCore — School Management (3-64 characters)
   - **Category:** Education
   - **Brief Description:** Complete school management platform (max 80 chars)

### Step 4: Upload Assets

| Asset | Dimensions | Format |
|-------|-----------|--------|
| App Icon | 512 × 512 px | PNG |
| Screenshots | 1080 × 1920 px (portrait) | PNG/JPEG |
| Feature Image | 1280 × 720 px | PNG/JPEG |

**Required screenshots:** Minimum 3-5 per device type

### Step 5: Configure Distribution

- **Regions:** Nigeria, Africa, Global
- **Languages:** English
- **Pricing:** Free
- **Devices:** Phones, Tablets

### Step 6: Privacy & Compliance

- **Privacy Policy URL:** `https://educore.app/privacy`
- **Content Rating:** Complete questionnaire
- **Permissions Declaration:** Internet, Camera, Location

### Step 7: Submit for Review

- Click **"Submit for Review"**
- Review typically takes **1-3 working days**
- Monitor status in "Release" section

---

## Quick Comparison

| Feature | Amazon Appstore | Huawei AppGallery |
|---------|----------------|-------------------|
| **Account Fee** | Free | Free |
| **APK Support** | ✅ Yes | ✅ Yes |
| **AAB Support** | ✅ Yes | ✅ Preferred |
| **Review Time** | 24-72 hours | 1-3 days |
| **Revenue Share** | 70/30 (80/20 small biz) | 80/20 |
| **Nigeria Support** | ✅ Yes | ✅ Yes |
| **Privacy Policy** | Required | Required |

---

## Pre-Submission Checklist

- [ ] APK/AAB built and signed
- [ ] Privacy policy page live at `https://educore.app/privacy`
- [ ] Screenshots captured (minimum 4 per store)
- [ ] App icon (512 × 512 px) ready
- [ ] Feature graphic (1024 × 500 px) ready
- [ ] App description written (from `PLAY_STORE_LISTING.md`)
- [ ] Developer accounts created
- [ ] Identity verification completed

---

## Next Steps After Submission

1. **Monitor reviews** — Check email daily for approval/feedback
2. **Respond to feedback** — If rejected, fix issues and resubmit
3. **Update the landing page** — Add download links for Amazon and Huawei stores
4. **Track downloads** — Use store analytics to monitor installs
