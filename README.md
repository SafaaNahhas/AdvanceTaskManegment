# Task Management API

## Introduction
This project is a Task Management API built using Laravel. It offers a comprehensive system to manage tasks, including features like task dependencies, advanced filtering, real-time notifications, and robust security protections. The system is designed for role-based access control and uses JWT for authentication. It also includes reporting and file attachment functionalities.

## Prerequisites
- PHP >= 8.0
- Composer
- Laravel >= 9.0
- MySQL or any database supported by Laravel
- Postman for API testing


## Project Structure
- `AuthController.php`: Manages authentication-related requests (login, register, JWT token handling).
- `TaskController.php`: Handles task management requests (create, update, delete, assign, reassign, comments, attachments).
- `RoleController.php`: Manages roles and permissions for users.
- `UserController.php`: Manages user-related requests (create, update, delete).
- `AttachmentController.php`: Manages file attachments (upload, download, delete, restore).
- `TaskStatusUpdateController.php`: Handles task status updates.
- `ReportController.php`: Generates various task reports (daily, overdue, completed tasks).
- `TaskService.php`: Contains business logic for task-related operations.
- `UserService.php`: Handles user management business logic.
- `RoleService.php`: Contains logic for role and permission management.
- `AuthService.php`: Handles authentication-related business logic, including JWT token generation and validation.
## Key Features
- Task Management: Create, assign, update, and delete tasks. Manage dependencies between tasks.
- Role-Based Access Control: Assign and manage user roles with specific permissions (e.g., create tasks, assign roles).
- JWT Authentication: Secure API access using JWT tokens.
- Advanced Filtering: Filter tasks by type, status, priority, assigned user, and due date.
- Task Dependencies: Track and manage task dependencies to block tasks until prerequisites are completed.
- Task Reassignment: Reassign tasks and manage user-specific tasks.
- Reporting: Generate detailed reports such as daily tasks, overdue tasks, completed tasks, and tasks by user.
- File Attachments: Upload, download, and delete task-related files.
- Error Handling: Custom error handling to return clear and structured error messages.
## Security Features:
- CSRF Protection: Prevent CSRF attacks by ensuring all state-changing requests require a valid token.
- XSS & SQL Injection Protection: Built-in protections against XSS and SQL injection.
- Rate Limiting: Protects the API from DDoS attacks by limiting the number of requests per minute.
## Technologies Used
- Policies: Used to manage access control and ensure proper permissions for users and roles in the application.
- Spatie: Utilized for role and permission management within the Laravel application, allowing for flexible user access control.
- Factories: Implemented to generate test data for models in the application, ensuring efficient testing processes.
- VirusTotal: Integrated for scanning files for potential security threats, ensuring that uploaded files are safe.

## Postman
https://documenter.getpostman.com/view/34501481/2sAXxWYToR
