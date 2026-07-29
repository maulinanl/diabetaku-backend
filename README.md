# Diabetaku Backend

A Laravel-based REST API backend service for the Diabetaku Health Monitoring System.

Diabetaku Backend provides API services to support diabetes health monitoring, including authentication, user management, health data management, patient-caregiver relationships, doctor monitoring, and notification services.

This repository contains the **backend/API application** developed using Laravel.

---

# 📌 About Diabetaku

Diabetaku is a digital health application developed to support diabetes management through technology-based health monitoring.

The system connects:

- **Doctors** to review patient health information and provide healthcare recommendations.
- **Patients** to manage and monitor personal health conditions.
- **Caregivers** to assist patient health monitoring.
- **Administrators** to support system management and user verification.

The backend acts as the core service that manages:

- Data processing
- Business logic
- Authentication
- API communication
- Database management

The backend communicates with the Flutter mobile application through REST API.

---

# 🔗 Related Repository

This repository is part of the **Diabetaku Health Monitoring System**.

The system consists of:

- **Frontend/Mobile Application** → Flutter-based mobile application
- **Backend API** → Laravel-based REST API service

## Frontend Repository

[Diabetaku Mobile App](https://github.com/maulinanl/diabetaku_app)

## Backend Repository

[Diabetaku Backend](https://github.com/maulinanl/diabetaku-backend)

---

# ✨ Backend Features

## 🔐 Authentication

The authentication system manages user access and role-based authorization.

Features:

- User registration
- User login
- Email verification
- Password management
- Token-based authentication
- Role-based access control

Supported roles:

- Doctor
- Patient
- Caregiver
- Administrator

---

## 👤 User Management

Features:

- Manage user information
- Manage user profiles
- Manage user roles
- Support account verification process

---

## 👨‍⚕️ Doctor Management

Features:

- Manage doctor information
- Manage connected patients
- Access patient health information
- Manage clinical notes
- Manage prescriptions
- Provide recommendations

---

## 👤 Patient Management

Features:

- Manage patient information
- Store patient health records
- Manage patient monitoring data
- Support patient-caregiver relationships
- Support patient-doctor relationships

---

## 🤝 Caregiver Management

Features:

- Manage caregiver information
- Manage patient connections
- Provide patient monitoring access

---

## 🩺 Health Monitoring Management

Features:

- Blood glucose data management
- Medication management
- Activity tracking
- Physiological data management
- Health history management

---

## 🔔 Notification Management

Features:

- Manage application notifications
- Support medication reminders
- Deliver health-related notifications

---

# 🛠 Technology Stack

## Backend Application

| Technology | Description |
|---|---|
| Laravel | Backend framework |
| PHP | Programming language |
| REST API | Application communication |
| Laravel Sanctum | API authentication |
| MySQL | Database management |

---

# 🏗 Backend Architecture

The backend follows Laravel MVC architecture.

```
app/
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── Patient/
│   │   │   ├── Caregiver/
│   │   │   ├── Doctor/
│   │   │   └── Admin/
│   │
│   └── Middleware/
│
├── Models/
│
├── Services/
│
└── Providers/


routes/
│
├── api.php
└── web.php


database/
│
├── migrations/
├── seeders/
└── factories/
```

---

# 🔄 System Flow

```
              Flutter Mobile App
                      |
                      |
                  REST API
                      |
                      |
              Laravel Backend
                      |
        --------------------------------
        |              |               |
 Authentication   Business Logic   Database
                      |
                      |
                MySQL Database
```

---

# 🔐 API Authentication

The backend uses authentication mechanisms to secure API communication.

Authentication flow:

```
User Login
     |
     |
Authentication API
     |
     |
Generate Access Token
     |
     |
Access Protected API
```

---

# 🔗 API Integration

The backend provides REST API services consumed by the Flutter mobile application.

Main API services:

- Authentication API
- User Management API
- Patient API
- Caregiver API
- Doctor API
- Health Data API
- Notification API

Production server:

```
https://si.its.ac.id/labs/ikti/diabetaku/
```

---

# 🌐 Server Deployment

The Diabetaku backend system is deployed on the Institut Teknologi Sepuluh Nopember (ITS) server environment.

Application URL:

```
https://si.its.ac.id/labs/ikti/diabetaku/
```

The server provides backend services required by the Diabetaku Mobile Application.

Server environment details are managed by the laboratory server infrastructure.

---

# 🚀 Getting Started

## Prerequisites

Before running this project, make sure you have installed:

- PHP
- Composer
- Laravel
- MySQL
- Node.js and npm

---

# 📥 Installation

Clone this repository:

```bash
git clone https://github.com/maulinanl/diabetaku-backend.git
```

Navigate to project directory:

```bash
cd diabetaku-backend
```

Install dependencies:

```bash
composer install
```

---

# ⚙️ Configuration

Create environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure database connection in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=diabetaku
DB_USERNAME=root
DB_PASSWORD=
```

---

# 🗄 Database Setup

Run migration:

```bash
php artisan migrate
```

Run database seeder:

```bash
php artisan db:seed
```

---

# ▶️ Running Application

Start Laravel development server:

```bash
php artisan serve
```

Application URL:

```
http://localhost:8000
```

API endpoint:

```
http://localhost:8000/api
```

---

# 🔔 Notification System

The backend supports notification services for:

- Medication reminders
- Health updates
- Application notifications

Notification flow:

```
Laravel Backend
        |
        |
Firebase Cloud Messaging
        |
        |
Flutter Mobile Application
        |
        |
User Device
```

---

# 🧪 Testing

Run backend tests:

```bash
php artisan test
```

---

# 📌 Development Commands

| Command | Description |
|---|---|
| `composer install` | Install backend dependencies |
| `php artisan serve` | Run Laravel server |
| `php artisan migrate` | Run database migration |
| `php artisan db:seed` | Run database seeder |
| `php artisan test` | Run backend tests |
| `php artisan route:list` | View API routes |

---

# 👥 Contributors

## Developer

**Maulina Nur Laila**

## Supervisor

**Prof. Dr. Eng. Febriliyan Samopa, S.Kom., M.Kom.**

---

# 📄 License

This project is developed as part of the Final Project (Tugas Akhir) at Institut Teknologi Sepuluh Nopember (ITS).

Developed for academic and research purposes.
