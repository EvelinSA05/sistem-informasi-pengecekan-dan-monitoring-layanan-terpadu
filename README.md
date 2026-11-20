# SISTEM INFORMASI PENGECEKAN DAN MONITORING LAYANAN TERPADU

**SISTEM INFORMASI PENGECEKAN DAN MONITORING LAYANAN TERPADU** adalah aplikasi web untuk memantau, memeriksa, dan mengevaluasi ketersediaan serta kualitas layanan digital secara terpadu dan berkala.

Sistem ini menjembatani administrator dan tester melalui mekanisme penugasan berbasis periode yang terstruktur, sehingga seluruh layanan terpadu dapat diperiksa secara berkala dari sisi uptime, fungsionalitas, dan penanganan kendala.

## Core Architecture

The application is built using a modern monolithic architecture with a reactive frontend:

- **Framework**: Laravel 11.x (PHP 8.2)
- **Frontend**: Vue.js 3 with Composition API
- **Routing & State**: Inertia.js
- **Styling**: Tailwind CSS
- **Database**: PostgreSQL

## Key Features

### Role-Based Access Control
- **Admin**: Full access to master data, user management, and testing periods.
- **Tester**: Focused interface for receiving assignments and submitting test results.

### Master Service Management
- Centralized repository of all integrated digital services.
- Real-time status tracking (Active, Inactive, Needs Confirmation).
- Categorization and hierarchical leveling for easier filtering.

### Dynamic Checking Periods
- Admins can create and schedule checking periods with strict deadlines.
- Automated service assignment to testers.
- Automated state locking when a period expires or is marked as completed.

### Tester Workflow
- Clean, intuitive dashboard for assigned tasks.
- Guided testing flow with integrated documentation and specific instructions.
- Distinct reporting mechanisms for normal operations versus encountered issues.

## System Requirements

- PHP >= 8.2
- Composer
- Node.js & npm
- PostgreSQL or MySQL
- Git

## Installation Guide

1. **Clone the repository**
   ```bash
   git clone https://github.com/masdimya/majadigi-checking-layanan.git
   cd majadigi-checking-layanan
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Configure your `.env` file with appropriate database credentials.

5. **Database Migration & Seeding**
   ```bash
   php artisan migrate --seed
   ```
   *Note: The seeder will populate the database with default roles, an admin account, and master service data.*

6. **Compile Assets**
   ```bash
   npm run build
   ```
   *For local development, you may use `npm run dev` instead.*

7. **Serve the Application**
   ```bash
   php artisan serve
   ```

## Development & Deployment

This project utilizes Vite for asset bundling. When making changes to the Vue components or Tailwind classes, ensure you rebuild the assets before pushing to production environments (such as Vercel) if they do not automatically run the build script.

```bash
# Development (Hot Module Replacement)
npm run dev

# Production Build
npm run build
```

## Security

If you discover any security-related issues, please report them directly to the repository maintainer. Security patches and updates are applied in alignment with the latest Laravel framework releases.

## License

This project is intended for internal use in operating **Sistem Informasi Pengecekan dan Monitoring Layanan Terpadu**. Unauthorized copying, modification, or distribution is prohibited.
