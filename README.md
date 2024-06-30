# Visitor Tracker

A simple PHP script to log visitor information and send notifications when a visitor accesses your site. The script stores visitor data in a JSON file and optionally sends email notifications.

## Features

- Logs visitor information (IP address, visit date, time, user agent, referrer URL, visit duration)
- Stores log data in a JSON file within a protected directory
- Rate limiting to prevent abuse
- Sends email notifications with optional CC
- Creates a `.htaccess` file to prevent direct access to the log directory

## Installation

1. Clone the repository or download the script.
2. Place the `visitors.php` file in your desired directory on your web server.
3. Ensure the directory where you place the script has write permissions.

## Configuration

Open `visitors.php` and configure the following constants as needed:

- `LOG_FILE_NAME`: Name of the log file (default: `visitors.json`)
- `NOTIFICATION`: Enable or disable email notifications (default: `true`)
- `EMAIL_TO`: Email address to send notifications to
- `EMAIL_CC_ENABLED`: Enable or disable CC notifications (default: `true`)
- `EMAIL_CC`: Email address to CC notifications to
- `EMAIL_SUBJECT`: Subject of the notification email
- `EMAIL_MESSAGE`: Body of the notification email
- `EMAIL_FROM`: Sender email address for the notifications
- `LOG_DIR`: Directory to store the log file (default: `__DIR__ . '/logs'`)
- `RATE_LIMIT`: Maximum requests per hour (default: `100`)
- `RATE_LIMIT_WINDOW`: Time window in seconds for rate limiting (default: `3600`)

## Usage

Include the script in your PHP files where you want to track visitors:

```php
<?php
include_once 'path/to/visitors.php';
?>
```

Ensure you replace 'path/to/visitors.php' with the actual path to the visitors.php file.

## Security
The script ensures that the logs directory is protected by creating a .htaccess file to deny direct access via the browser. Ensure your web server supports .htaccess files and is configured to respect these settings.

## Error Handling
The script includes a custom error handler that logs errors and displays a user-friendly message without exposing sensitive information.

## License
This project is licensed under the **[MIT license](https://github.com/ot2i7ba/PHPVisitors/blob/main/LICENSE)**, providing users with flexibility and freedom to use and modify the software according to their needs.

## Disclaimer
This project is provided without warranties. Users are advised to review the accompanying license for more information on the terms of use and limitations of liability.
