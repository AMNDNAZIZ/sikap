# SIKAP - Sistem Informasi Akademik

SIKAP is a comprehensive Academic Information System (SIAKAD) built with **CodeIgniter 4**. It is designed to manage various academic processes, including student data, staff management, and academic scheduling, with integration capabilities for Dapodik.

## 🚀 Features

- **Dapodik Integration**: Seamlessly synchronize student and staff (PTK) data from Dapodik.
- **Academic Calendar Management**: Tools to manage semester boundaries, holiday periods, and school events.
- **Staff Account Automation**: Automatically generate system profiles and accounts for teachers and educational personnel.
- **Academic Status Tracking**: Dynamic calculation of academic periods, including breaks and active semesters.
- **Reporting**: Integrated tools for generating academic reports, including PDF support via Dompdf.
- **License System**: Built-in verification for application activation and license management.

## 🛠️ Tech Stack

- **Backend**: PHP 8.1+ (CodeIgniter 4 framework)
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla CSS / PHP Templates
- **Key Libraries**:
  - `dompdf/dompdf`: For PDF generation.
  - `phpoffice/phpspreadsheet`: For spreadsheet processing.

## 📦 Installation

1. **Clone the Project**:
   ```bash
   git clone <repository-url>
   cd sikap
   ```

2. **Install Dependencies**:
   Ensure you have Composer installed, then run:
   ```bash
   composer install
   ```

3. **Configure Environment**:
   - Edit the `.env` file in the root directory.
   - Configure your `database.default` settings (hostname, database, username, password).
   - Update `app.baseURL` to match your local setup.

4. **Database Setup**:
   - Create a new MySQL database.
   - Import the provided `sikap.sql` file to initialize the schema and base data.

## 🏃 Running the Application

Start the local development server using CodeIgniter's built-in Spark tool:

```bash
php spark serve
```

The application will be accessible at `http://localhost:8080` (or whichever port is assigned).

## 📄 License

This project is licensed under the MIT License - see the `composer.json` file for details.
