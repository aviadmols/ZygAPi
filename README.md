# Shopify Tags Management System

מערכת ניהול תגיות להזמנות Shopify עם תמיכה ב-AI, multi-store, webhooks, ועיבוד בתור.

## תכונות

- ✅ ניהול מספר חנויות Shopify
- ✅ הגדרת טוקנים לכל חנות (Shopify + Recharge)
- ✅ יצירת חוקיות תגיות דינמיות עם AI (OpenRouter)
- ✅ תמיכה בתגיות מורכבות עם פונקציות (get, split, switch)
- ✅ עיבוד בתור (queue) לאלפי הזמנות
- ✅ ממשק ניהול מלא בעברית
- ✅ Webhook handler עם HMAC verification
- ✅ סרגל התקדמות בזמן אמת

## דרישות

- PHP 8.2+
- MySQL 5.7+ או MariaDB 10.3+
- Composer
- Node.js & NPM (ל-Breeze)
- Redis (מומלץ ל-queue) או Database queue

## התקנה

1. העתק את הפרויקט:
```bash
cd shopify-tags-system
```

2. התקן תלויות:
```bash
composer install
npm install
```

3. העתק קובץ סביבה:
```bash
cp .env.example .env
```

4. צור מפתח אפליקציה:
```bash
php artisan key:generate
```

5. הגדר את `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopify_tags
DB_USERNAME=your_username
DB_PASSWORD=your_password

QUEUE_CONNECTION=database  # או redis

OPENROUTER_API_KEY=your_openrouter_api_key
SHOPIFY_WEBHOOK_SECRET=your_webhook_secret
```

6. הרץ migrations:
```bash
php artisan migrate
```

7. צור משתמש ראשון:
```bash
php artisan migrate
# או דרך הממשק: /register
```

8. בנה assets:
```bash
npm run build
```

9. הפעל queue worker (בטרמינל נפרד):
```bash
php artisan queue:work --queue=order-processing
```

## שימוש

### 1. הוספת חנות

1. היכנס ל-"חנויות" בתפריט
2. לחץ על "חנות חדשה"
3. מלא את הפרטים:
   - שם החנות
   - Shopify Store URL (לדוגמה: your-store.myshopify.com)
   - Shopify Access Token
   - Recharge Access Token (אופציונלי)

### 2. יצירת חוקיות תגיות

#### דרך AI (מומלץ):

1. היכנס ל-"שיחות AI"
2. לחץ על "שיחה חדשה"
3. בחר חנות
4. הגדר את החוקיות בטקסט (לדוגמה: "אם יש פרופטי X אז תגית Y")
5. הכנס דוגמת הזמנה (JSON)
6. לחץ על "צור חוקיות מהשיחה"

#### ידנית:

1. היכנס ל-"חוקיות"
2. לחץ על "חוקיות חדשה"
3. מלא את הפרטים:
   - שם החוקיות
   - תבנית תגיות (עם ביטויים כמו `{{switch(...)}}`)

### 3. עיבוד הזמנות

1. היכנס ל-"עיבוד הזמנות"
2. בחר חנות
3. בחר חוקיות (או השאר ריק לכל החוקיות הפעילות)
4. הכנס מספרי הזמנות מופרדים בפסיק
5. בחר אם לדרוס תגיות קיימות
6. לחץ על "התחל עיבוד"
7. עקוב אחר ההתקדמות בסרגל ההתקדמות

### 4. הגדרת Webhook

1. ב-Shopify Admin → Settings → Notifications → Webhooks
2. צור webhook חדש:
   - Event: `Order creation`
   - Format: `JSON`
   - URL: `https://your-domain.com/webhooks/shopify/order-created`
   - API version: Latest
3. העתק את ה-Webhook Secret ל-`.env`:
   ```
   SHOPIFY_WEBHOOK_SECRET=your_secret_here
   ```

## תבניות תגיות

המערכת תומכת בביטויים מורכבים:

### דוגמה בסיסית:
```
Tag1, Tag2, Tag3
```

### עם ביטויים:
```
{{switch(12.Days + "-" + 12.Gram; "14D-50"; "A"; "14D-75"; "A"; "Unknown")}}
```

### פונקציות זמינות:

- `{{get(array, index)}}` - מחלץ אלמנט ממערך
- `{{split(string, delimiter)}}` - מחלק מחרוזת למערך
- `{{switch(value; case1; result1; case2; result2; ...; default)}}` - switch statement
- `{{12.Days}}` - גישה לשדות מההזמנה (line item index 12, field Days)

## API Endpoints

### Webhook
```
POST /webhooks/shopify/order-created
```

### Order Processing
```
POST /orders/process
GET /orders/progress/{job}
GET /orders/results/{job}
```

### Tagging Rules
```
POST /tagging-rules/{rule}/test
```

## פתרון בעיות

### Queue לא עובד:
```bash
php artisan queue:work --queue=order-processing
```

### Cache לא מתעדכן:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### שגיאת OpenRouter:
ודא שה-API key מוגדר נכון ב-`.env`:
```
OPENROUTER_API_KEY=sk-or-v1-...
```

## רישיון

MIT License
