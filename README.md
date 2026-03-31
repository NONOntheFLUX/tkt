# Simple CRM for Small Businesses

A PHP + MySQL CRM application for managing Leads, Contacts, Companies, Deals, Activities, and Notes. Features role-based access control, workflow status transitions with history logging, and in-app notifications.

## Requirements

- **WampServer** (or any Apache + PHP 8+ + MySQL environment)
- **phpMyAdmin** (included with WampServer)

## Installation & Setup

### 1. Create the Database

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`.
2. Click **"New"** in the left sidebar.
3. Enter database name: `simple_crm`
4. Select collation: `utf8mb4_unicode_ci`
5. Click **"Create"**.

### 2. Import the SQL Schema + Seed Data

1. In phpMyAdmin, select the `simple_crm` database.
2. Click the **"Import"** tab.
3. Click **"Choose File"** and select `crm/sql/schema.sql`.
4. Click **"Go"** to import.

This creates all tables and inserts demo data.

### 3. Update Database Configuration

Open `crm/config/database.php` and update if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'simple_crm');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default WampServer password is empty
```

### 4. Place Files in WampServer

Copy the `crm/` folder into your WampServer web root:

```
C:\wamp64\www\crm\
```

### 5. Access the Application

Open your browser and navigate to:

```
http://localhost/crm/public/index.php
```

## Demo Accounts

| Email | Password | Role |
|-------|----------|------|
| `admin@example.com` | `Admin123!` | Admin |
| `manager@example.com` | `Manager123!` | Sales Manager |
| `rep@example.com` | `Rep123!` | Sales Rep |
| `user@example.com` | `User123!` | User |

**Important:** After importing the SQL, run the demo password setup script once:

```
http://localhost/crm/public/setup_demo.php
```

This generates proper bcrypt hashes for all demo accounts. **Delete `setup_demo.php` after running it.**

## Folder Structure

```
crm/
  config/
    database.php          # PDO connection + credentials
  includes/
    auth.php              # Session management, login/logout, role checks
    csrf.php              # CSRF token generate/verify
    permissions.php       # Role-based access checks + workflow transitions
    helpers.php           # Flash messages, notifications, pagination, formatting
    header.php            # HTML header + navigation template
    footer.php            # HTML footer template
  models/
    Lead.php              # Lead CRUD + search/filter/sort
    Company.php           # Company CRUD
    Contact.php           # Contact CRUD
    Deal.php              # Deal CRUD + stage management
    Activity.php          # Activity CRUD
    Note.php              # Polymorphic notes (any object)
    Notification.php      # Notification list, read/unread
    User.php              # User CRUD + admin management
    Category.php          # Categories/tags reference data
  public/
    index.php             # Public landing page
    login.php             # Login form
    signup.php            # Registration form
    logout.php            # Logout handler
    dashboard.php         # User dashboard with stats
    profile.php           # User profile edit + password change
    leads.php             # Leads list with search/filter/sort
    lead_detail.php       # Lead detail + notes + status change
    lead_form.php         # Create/edit lead
    lead_delete.php       # Delete lead (confirmation)
    companies.php         # Companies list
    company_form.php      # Create/edit company
    company_detail.php    # Company detail
    contacts.php          # Contacts list
    contact_form.php      # Create/edit contact
    contact_detail.php    # Contact detail
    deals.php             # Deals list
    deal_form.php         # Create/edit deal
    deal_detail.php       # Deal detail + stage management
    deal_delete.php       # Delete deal
    activities.php        # Activities list
    activity_form.php     # Create/edit activity
    notifications.php     # Notifications center
    setup_demo.php        # One-time demo password setup (delete after use)
    css/
      style.css           # Main stylesheet
    admin/
      users.php           # Admin: user management
      user_form.php       # Admin: create/edit user
      user_toggle.php     # Admin: enable/disable user
      categories.php      # Admin: manage categories/tags
  sql/
    schema.sql            # Full database schema + seed data
```

## Roles & Permissions

| Role | Capabilities |
|------|-------------|
| **Visitor** | Browse leads catalog, view details, search/filter, sign up |
| **User** | Create/manage own records, add notes |
| **Sales Rep** | Manage assigned leads, workflow steps |
| **Sales Manager** | Assign leads, change stages, backward deal transitions |
| **Admin** | Full access, manage users, manage categories |

## Workflow Rules

### Lead Status Transitions
- `new` -> `contacted`
- `contacted` -> `qualified` or `lost`
- `qualified` -> `won` or `lost`
- `won` / `lost` -> (terminal states)

### Deal Stage Transitions
- `prospecting` -> `proposal`
- `proposal` -> `negotiation`
- `negotiation` -> `won` or `lost`
- Backward transitions: Sales Manager / Admin only

All status changes are logged in the `status_history` table.

## Key Features

- **Search & Filter**: Search leads by title/description, filter by status, sort by date
- **Role-Based Access**: Enforced on every action (create, edit, delete, status change)
- **CSRF Protection**: All POST forms use CSRF tokens
- **Password Security**: `password_hash()` / `password_verify()` with bcrypt
- **Status History**: Full audit trail for all status/stage changes
- **In-App Notifications**: Created on lead assignment, status change, and note addition
- **Collaboration**: Notes/comments on leads and other objects
