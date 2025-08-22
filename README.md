# Comprehensive documentation for my Newsletter API

A robust PHP-based Newsletter API built with MySQLi for managing email subscriptions. This API provides complete CRUD operations for newsletter management with a beautiful admin interface.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Configuration](#database-configuration)
- [API Endpoints](#api-endpoints)
- [Usage Examples](#usage-examples)
- [Admin Interface](#admin-interface)
- [Error Handling](#error-handling)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)

## ✨ Features

- **Email Subscription Management**: Subscribe, view, and delete newsletter subscribers
- **MySQL Database Integration**: Secure data storage with MySQLi
- **Admin Dashboard**: Beautiful HTML interface for managing subscribers
- **CSV Export**: Export subscriber data for external use
- **Statistics Dashboard**: View subscription statistics and analytics
- **IP Address Tracking**: Track subscriber IP addresses for analytics
- **CORS Support**: Cross-origin resource sharing enabled
- **Error Logging**: Comprehensive error logging and debugging
- **Responsive Design**: Mobile-friendly admin interface
- **Authentication System**: Secure admin login system

## 🔧 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- MySQLi PHP extension
- Web server (Apache/Nginx)

## 📦 Installation

1. **Clone or download the files**:
   ```bash
   git clone <repository-url>
   cd newsletter-api
   ```

2. **Upload files to your web server**:
   - `newsletter.php` - Main API file
   - `subscribers.html` - Admin interface
   - `login.php` - Authentication handler (if using admin features)

3. **Set file permissions**:
   ```bash
   chmod 644 newsletter.php
   chmod 644 subscribers.html
   ```

## 🗄️ Database Configuration

### 1. Update Database Credentials

Edit the database configuration in `newsletter.php` (lines 20-25):

```php
$db_config = [
    'host' => 'localhost',        // Your MySQL host
    'username' => 'your_username', // Your MySQL username
    'password' => 'your_password', // Your MySQL password
    'database' => 'your_database'  // Your MySQL database name
];
```

### 2. Database Schema

The API will automatically create the required table:

```sql
CREATE TABLE newsletter_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 🚀 API Endpoints

### Base URL
```
https://yourdomain.com/newsletter.php
```

### 1. Subscribe to Newsletter

**POST** `/newsletter.php`

Subscribe a new email address to the newsletter.

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Te-ai abonat cu succes!",
    "subscriber_id": 123
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "Acest email este deja abonat"
}
```

### 2. View All Subscribers (Admin)

**GET** `/newsletter.php?admin=view`

Retrieve all newsletter subscribers.

**Response:**
```json
{
    "success": true,
    "emails": [
        {
            "id": 1,
            "email": "user@example.com",
            "created_at": "2024-08-22 10:30:45",
            "ip_address": "192.168.1.100"
        }
    ],
    "total": 1
}
```

### 3. Export Subscribers as CSV

**GET** `/newsletter.php?export=csv`

Download all subscribers as a CSV file.

**Response:** CSV file download with headers:
- Email
- Data Abonarii (Subscription Date)

### 4. Get Statistics

**GET** `/newsletter.php?stats`

Get newsletter subscription statistics.

**Response:**
```json
{
    "success": true,
    "stats": {
        "total_subscribers": 150,
        "this_week": 12,
        "this_month": 45,
        "growth_rate": 8.0,
        "recent_subscribers": [
            {
                "email": "recent@example.com",
                "created_at": "2024-08-22 09:15:30"
            }
        ]
    }
}
```

### 5. Delete Subscriber (Admin)

**GET** `/newsletter.php?delete=1&id={subscriber_id}`

Delete a subscriber by ID.

**Parameters:**
- `id` (integer): Subscriber ID to delete

**Response:**
```json
{
    "success": true,
    "message": "Abonatul a fost șters cu succes"
}
```

### 6. Test API Connection

**GET** `/newsletter.php?test`

Test the API connection and view system information.

**Response:**
```json
{
    "success": true,
    "message": "Newsletter API funcționează!",
    "server_info": {
        "php_version": "8.1.0",
        "mysqli_version": "8.0.30",
        "database": "newsletter_db",
        "host": "localhost"
    }
}
```

## 📖 Usage Examples

### JavaScript/AJAX Subscription

```javascript
// Subscribe to newsletter
async function subscribeToNewsletter(email) {
    try {
        const response = await fetch('newsletter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email
            })
        });

        const data = await response.json();
        
        if (data.success) {
            console.log('Subscription successful!', data.message);
        } else {
            console.error('Subscription failed:', data.message);
        }
    } catch (error) {
        console.error('Network error:', error);
    }
}

// Usage
subscribeToNewsletter('user@example.com');
```

### HTML Form Integration

```html
<form id="newsletter-form">
    <input type="email" id="email" placeholder="Enter your email" required>
    <button type="submit">Subscribe</button>
</form>

<script>
document.getElementById('newsletter-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    
    const response = await fetch('newsletter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    });
    
    const data = await response.json();
    alert(data.message);
});
</script>
```

### PHP cURL Example

```php
<?php
// Subscribe via PHP cURL
function subscribeToNewsletter($email) {
    $data = json_encode(['email' => $email]);
    
    $ch = curl_init('https://yourdomain.com/newsletter.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Usage
$result = subscribeToNewsletter('user@example.com');
print_r($result);
?>
```

## 🎨 Admin Interface

The included `subscribers.html` provides a complete admin dashboard with:

### Features:
- **Subscriber Management**: View, search, and delete subscribers
- **Real-time Statistics**: Live subscriber counts and growth metrics
- **CSV Export**: One-click export functionality
- **Responsive Design**: Works on desktop and mobile devices
- **Authentication**: Secure login system integration

### Setup:
1. Upload `subscribers.html` to your web server
2. Update the API URL in the JavaScript (line 199):
   ```javascript
   this.apiUrl = 'newsletter.php'; // Update path if needed
   ```
3. Access via: `https://yourdomain.com/subscribers.html`

### Screenshots:
- Modern, professional interface
- Real-time data updates
- Mobile-responsive design
- Smooth animations and transitions

## ❌ Error Handling

The API returns standardized error responses:

### HTTP Status Codes:
- `200`: Success
- `400`: Bad Request (invalid input)
- `409`: Conflict (email already exists)
- `404`: Not Found (subscriber not found)
- `405`: Method Not Allowed
- `500`: Internal Server Error

### Error Response Format:
```json
{
    "success": false,
    "message": "Error description",
    "debug_info": {
        "additional_context": "when_applicable"
    }
}
```

### Common Errors:

| Error | Cause | Solution |
|-------|-------|----------|
| "Extensia MySQLi nu este instalată" | MySQLi extension missing | Install php-mysqli |
| "Nu se poate conecta la baza de date" | Wrong DB credentials | Check database config |
| "Adresa de email nu este validă" | Invalid email format | Use valid email address |
| "Acest email este deja abonat" | Duplicate subscription | Email already exists |

## 🔒 Security Features

### Input Validation:
- Email format validation using `filter_var()`
- SQL injection prevention with prepared statements
- XSS protection through proper encoding
- Input sanitization and trimming

### Database Security:
- Prepared statements for all queries
- Connection charset set to UTF-8
- Proper error handling without exposing system details

### CORS Configuration:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

## 🔧 Troubleshooting

### Common Issues:

#### 1. Database Connection Failed
```
Error: "Nu se poate conecta la baza de date MySQL"
```
**Solution:**
- Verify database credentials in `$db_config`
- Check if MySQL server is running
- Ensure database exists
- Verify user permissions

#### 2. Table Creation Failed
```
Error: "Nu s-a putut crea tabela newsletter_emails"
```
**Solution:**
- Check database user has CREATE privileges
- Verify database charset supports utf8mb4
- Check available disk space

#### 3. MySQLi Extension Missing
```
Error: "Extensia MySQLi nu este instalată pe server"
```
**Solution:**
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli

# CentOS/RHEL
sudo yum install php-mysqli

# Restart web server
sudo systemctl restart apache2
```

#### 4. Permission Denied
**Solution:**
```bash
# Set proper file permissions
chmod 644 newsletter.php
chmod 644 subscribers.html

# Set directory permissions
chmod 755 /path/to/newsletter/directory
```

### Debug Mode:
Enable detailed error reporting by keeping these lines in `newsletter.php`:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```
**Note:** Disable in production for security.

### Logging:
Check server error logs for detailed information:
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP-FPM
tail -f /var/log/php-fpm/error.log
```

## Support

### Testing Your Installation:
1. Test API: `https://yourdomain.com/newsletter.php?test`
2. View subscribers: `https://yourdomain.com/newsletter.php?admin=view`
3. Access admin: `https://yourdomain.com/subscribers.html`

### File Structure:
```
newsletter-api/
├── newsletter.php      # Main API file
├── subscribers.html    # Admin interface
├── login.php          # Authentication (optional)
├── README.md          # This documentation
└── .htaccess          # Apache configuration (optional)
```
