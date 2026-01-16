# Google Analytics Dashboard - Setup Instructions

## Overview
The Analytics dashboard is now ready to monitor both **CodigoAmigo.com** and **Casinuevo.com** using Google Analytics 4 (GA4) API.

## Setup Steps

### 1. Create Google Cloud Service Account

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable **Google Analytics Data API**
4. Go to **IAM & Admin** → **Service Accounts**
5. Click **Create Service Account**
6. Name it (e.g., "Analytics Dashboard")
7. Click **Create and Continue**
8. Grant role: **Viewer**
9. Click **Done**
10. Click on the service account → **Keys** tab
11. Click **Add Key** → **Create new key** → **JSON**
12. Download the JSON file

### 2. Grant Access to GA4 Properties

1. Go to [Google Analytics](https://analytics.google.com/)
2. Select your property (CodigoAmigo)
3. Go to **Admin** → **Property Access Management**
4. Click **+** → **Add users**
5. Paste the service account email (from JSON file)
6. Grant **Viewer** role
7. Repeat for Casinuevo property

### 3. Get Property IDs

1. In Google Analytics, go to **Admin**
2. Select property
3. Click **Property Settings**
4. Copy the **Property ID** (format: `properties/XXXXXXXXX`)
5. Repeat for second property

### 4. Configure the Application

1. Upload the service account JSON file to:
   ```
   /home/admin/web/codigoamigo.com/public_html/config/google-analytics-credentials.json
   ```

2. Edit `/home/admin/web/codigoamigo.com/public_html/myphp/GoogleAnalyticsService.php`:
   ```php
   private $propertyIds = [
       'codigoamigo' => 'properties/YOUR_CODIGOAMIGO_PROPERTY_ID',
       'casinuevo' => 'properties/YOUR_CASINUEVO_PROPERTY_ID'
   ];
   ```

3. Create the config directory if it doesn't exist:
   ```bash
   mkdir -p /home/admin/web/codigoamigo.com/public_html/config
   chmod 755 /home/admin/web/codigoamigo.com/public_html/config
   ```

### 5. Test the Dashboard

1. Navigate to: `https://www.codigoamigo.com/public/admin_analytics.php`
2. You should see KPIs and charts loading
3. Test switching between CodigoAmigo and Casinuevo
4. Test different date ranges (7d, 30d, 90d)

## Features

### KPI Cards
- **Usuarios**: Total active users
- **Sesiones**: Total sessions
- **Páginas Vistas**: Total pageviews
- **Duración Media**: Average session duration
- **Tasa de Rebote**: Bounce rate percentage
- **Conversiones**: Total conversions

### Charts
- **Tendencia de Usuarios**: Line chart showing users and sessions over time
- **Top 10 Páginas**: Bar chart of most viewed pages
- **Fuentes de Tráfico**: Doughnut chart of traffic sources
- **Dispositivos**: Pie chart of device breakdown (desktop/mobile/tablet)

### Controls
- **Site Selector**: Toggle between CodigoAmigo and Casinuevo
- **Date Range**: Select 7, 30, or 90 days

## Troubleshooting

### "No autorizado" error
- Check that your user ID is in the admin whitelist in both:
  - `public/admin_analytics.php`
  - `ajax/get_analytics_data.php`

### "Error fetching analytics data"
- Verify service account JSON file exists and is readable
- Check that property IDs are correct in `GoogleAnalyticsService.php`
- Ensure service account has Viewer access to both GA4 properties
- Check error logs: `/home/admin/web/codigoamigo.com/public_html/php_errors.log`

### Mock Data Displayed
- If credentials are not configured, the system shows mock data for development
- Once credentials are set up, real data will be displayed

## API Rate Limits

Google Analytics Data API has the following limits:
- **Requests per day**: 50,000
- **Requests per 100 seconds**: 2,000

The dashboard is designed to minimize API calls by:
- Fetching all data in a single request when possible
- Using client-side caching

## Security Notes

- Service account JSON file contains sensitive credentials
- Keep it outside the public web directory if possible
- Never commit it to version control
- Restrict file permissions: `chmod 600 google-analytics-credentials.json`
