# 📚 PaperTrail

<div align="center">

**Streamline Your Thesis & Capstone Journey**

A web-based thesis management system designed to support students, advisers, and administrators through organized collaboration, progress tracking, consultations, and research workflow monitoring.

<br>

![Laravel](https://img.shields.io/badge/Laravel-12-red?style=for-the-badge\&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge\&logo=php)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3-38B2AC?style=for-the-badge\&logo=tailwindcss)
![Vite](https://img.shields.io/badge/Vite-Build-646CFF?style=for-the-badge\&logo=vite)
![OHV Cloud](https://getlogovector.com/wp-content/uploads/2021/05/ovhcloud-logo-vector.png)

</div>

---

## ✨ Overview

**PaperTrail** is a thesis and capstone management platform built to make academic research collaboration more structured, transparent, and efficient.

The system helps students manage research tasks, communicate with advisers, help to find the suitable adviser to their thesis, monitor thesis progress, and maintain an organized record of academic interactions. It also supports advisers by providing visibility into student activity and project development.

---

## 👥 User Roles

### 🦹 Leaders

Leaders can manage their groupmates, create a chat rooms, create schedules, can use ai assisted adviser matching, and also manage the todo tasked that they assigned to their members.

### 🎓 Students/Members

Students can collaborate with group members, submit thesis-related tasks, communicate with advisers, and track their project progress.

### 🧑‍🏫 Advisers

Advisers can monitor assigned groups, review submissions, participate in consultations, and guide students throughout the thesis or capstone process.

### 🛠️ Admins

Admins can manage users, adviser assignments, and system-level records.

---

## 🚀 Features

* 📌 Thesis and capstone workflow management
* 👥 Leader, Student, adviser, and admin roles
* ✅ Task submission and completion tracking
* 💬 Group communication and consultation support
* 📅 Meeting and interaction monitoring
* 📊 Progress visibility for advisers and leaders
* 🔍 AI-assisted adviser matching
* 🎨 Clean and responsive user interface
* ⚡ Laravel, Tailwind CSS, and Vite-powered frontend workflow

---

## 🧰 Tech Stack

| Technology                | Purpose                            |
| ------------------------- | ---------------------------------- |
| **Laravel**               | Backend framework                  |
| **PHP**                   | Main server-side application logic |
| **Blade**                 | Templating engine                  |
| **Tailwind CSS**          | Styling and responsive design      |
| **Vite**                  | Frontend asset bundling            |
| **MySQL**                 | Database management                |
| **Python**                | AI processing and analysis         |
| **Railway**               | Deployment platform                |

---

## 📦 Installation

### 1. Clone the repository

```bash
git clone https://github.com/reisther/papertrail.git
cd papertrail
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node dependencies

```bash
npm install
```

### 4. Create your environment file

```bash
cp .env.example .env
```

### 5. Generate the application key

```bash
php artisan key:generate
```

### 6. Configure your database

Update your `.env` file with your local MySQL database credentials.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=papertrail
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 7. Run database migrations

```bash
php artisan migrate
```

### 8. Start the development servers

In one terminal:

```bash
php artisan serve
```

In another terminal:

```bash
npm run dev
```

The app should now be running at:

```bash
http://127.0.0.1:8000
```

---

## 🔐 Environment Variables

Create a `.env` file based on `.env.example`.

Important variables may include:

```env
APP_NAME=PaperTrail
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=papertrail
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

```

The project uses AI services, store API keys securely:

```env
GEMINI_API_KEY=your_api_key_here
```

Never hardcode API keys directly into source files.

---

<div align="center">

### 📚 PaperTrail

**Organize. Track. Collaborate. Complete.**

</div>
