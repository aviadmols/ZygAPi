# Railway - Simple Setup (3 Steps Only!)

## Step 1: Add MySQL to your project

1. Railway Dashboard → Your Project
2. Click **"New"** → **"Database"** → **"MySQL"**
3. Done - MySQL is created

## Step 2: Connect ZygAPI to MySQL

1. Click on **ZygAPI** service
2. Go to **Variables**
3. Click **"Add Variable Reference"** (or "Reference")
4. Select **MySQL** service
5. Add reference: **MYSQL_PUBLIC_URL** → Save as variable **MYSQL_PUBLIC_URL**
   - (Railway will add it automatically when you reference MySQL)

**OR** manually add this ONE variable:
- **Name:** `DB_URL`
- **Value:** Copy the full connection string from MySQL → Connect (looks like `mysql://root:xxx@host:3306/railway`)

## Step 3: Add these 3 variables to ZygAPI

| Name | Value |
|------|-------|
| `DB_CONNECTION` | `mysql` |
| `APP_KEY` | `base64:Zq/eMP4kkm2EUaImPy6D7wqAD2/J3eMdiI9EP+7avS4=` |
| `APP_URL` | `https://zygapi-production-e574.up.railway.app` |

**That's it!** The `start.sh` script will automatically:
- Run migrations (create tables)
- Start the server

## Redeploy

Click **Redeploy** on ZygAPI. Done!

---

## If Variable Reference doesn't work

Manually add these (get values from MySQL → Connect):

| Name | Value |
|------|-------|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | (from MySQL Connect) |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `railway` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | (from MySQL Variables) |
