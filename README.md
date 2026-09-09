# University Course Registration Portal

A web-based university course registration system designed to simplify course enrollment, prerequisite checking, section management, academic result management, and student scheduling.

## Project Overview

The **University Course Registration Portal** provides separate portals for **Students** and **Administrators**.

Students can view their academic information, check available courses, register for courses, view enrolled courses, class routines, results, and university notices.

Administrators can manage students, departments, batches, courses, sections, prerequisites, class schedules, results, enrollments, and notices from a centralized dashboard.

## Key Features

### Student Portal

* Secure student login and authentication
* Student dashboard
* View and update profile
* Course registration
* Prerequisite validation
* Current semester completion validation
* View enrolled courses
* View class routine
* View academic results
* GPA and CGPA calculation
* View university notices
* Change password
* Secure logout

### Admin Portal

* Admin authentication
* Admin dashboard
* Student management
* Department management
* Batch management
* Course management
* Section management
* Prerequisite management
* Class schedule management
* Result management
* Enrollment management
* Notice management
* Profile management
* Password management

### Security & Validation

* Laravel Sanctum authentication
* Role-based access control
* Protected frontend routes
* Admin-only and student-only route protection
* Case-sensitive User ID authentication
* Server-side request validation
* Secure password hashing
* Protected API endpoints

## Technology Stack

### Frontend

* Vue 3
* TypeScript
* Vite
* Vue Router
* Axios
* Lucide Vue Next
* HTML5
* CSS3

### Backend

* Laravel
* PHP
* Laravel Sanctum
* RESTful API

### Database

* MySQL

### Development Tools

* Git
* GitHub
* VS Code
* Laragon



## Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/Sohug6223/university-course-registration-portal.git
cd university-course-registration-portal
```

### 2. Frontend Setup

```bash
npm install
npm run dev
```

### 3. Backend Setup

```bash
cd backend
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database and other environment variables in `.env`.

Run migrations:

```bash
php artisan migrate
```

Run seeders if required:

```bash
php artisan db:seed
```

Start the Laravel server:

```bash
php artisan serve
```

## Environment Configuration

The `.env` file is intentionally excluded from version control.

Use `.env.example` as the configuration template.

## Project Status

**Core modules completed and tested.**

The system currently includes authentication, role-based route protection, student and admin portals, course registration, academic management, and notice management.

## Team

**Project:** University Course Registration Portal

**Course:** Software Engineering Laboratory

**University:** City University

### Student
- User ID: `0282320005101147`
- Password: Contact project team

### Admin
- User ID: `ADMIN001`
- Password: Contact project team

## License

This project is developed for academic purposes.
