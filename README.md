<div align="center">

# Anka DB Manager

### Modern PHP Database Management Platform

A powerful, modular and extensible database management panel built with PHP.

[Features](#features) • [Installation](#installation) • [Supported Databases](#supported-databases) • [Security](#security) • [License](#license)

</div>

---

## Overview

Anka DB Manager is a modern PHP-based database management platform designed for developers, system administrators and database professionals.

The project provides a centralized interface for managing databases, tables, schemas, queries, migrations, backups, users and database relationships.

Its modular architecture makes it possible to extend the platform with additional database drivers, management tools and integrations.

## Features

### Database Management

* Multiple database connection support
* Database explorer
* Table management
* Table creation and modification
* Column management
* Primary key management
* Foreign key management
* Index management
* View management
* Trigger management
* Procedure and function management
* Database statistics

### Data Management

* Create, update and delete records
* Inline data editing
* Advanced filtering
* Sorting
* Pagination
* Search
* JSON data visualization
* NULL and boolean value handling
* Bulk operations

### SQL Console

* Full SQL query interface
* Query history
* Saved queries
* Syntax highlighting
* Query execution statistics
* Execution time monitoring
* Rows affected information
* EXPLAIN support
* Multiple query execution

### Query Builder

Build SQL queries through a visual interface without manually writing every part of the query.

Supported operations include:

* SELECT
* INSERT
* UPDATE
* DELETE
* WHERE
* ORDER BY
* GROUP BY
* JOIN
* LIMIT
* Aggregation

### Schema Designer

Visualize database structures and relationships through an interactive schema interface.

The schema designer can display:

* Tables
* Columns
* Primary keys
* Foreign keys
* Relationships
* Indexes

### ER Diagram

Automatically visualize database relationships and create a graphical representation of the database structure.

This makes complex database architectures easier to understand and maintain.

### Database Diff

Compare two database structures and detect differences between them.

Anka DB Manager can identify changes such as:

* New tables
* Removed tables
* New columns
* Removed columns
* Modified columns
* Index changes
* Foreign key changes

Database differences can be used as a foundation for generating migrations.

### Migration Manager

Manage database migrations directly from the management interface.

Features include:

* Migration creation
* Migration execution
* Migration history
* Rollback support
* Schema synchronization

### Backup Manager

Create and manage database backups.

Supported operations include:

* Create backup
* Download backup
* Restore backup
* Delete backup
* Backup history
* Server-side backup storage

### Query Profiler

Analyze SQL query performance with detailed execution information.

Displays information such as:

* Execution time
* Returned rows
* Affected rows
* Query execution details
* EXPLAIN output

### Audit Log

Track important database and administration operations.

Example:

```text
14:21  admin  UPDATE users
14:23  admin  DELETE products
14:24  admin  CREATE TABLE logs
```

This provides better visibility and accountability for administrative operations.

### Authentication and Authorization

Built-in authentication and authorization system.

Includes:

* Administrator login
* Password hashing
* Session management
* Permission management
* Role-based access control
* Administrative activity tracking

## Architecture

Anka DB Manager follows a modular architecture designed for maintainability and extensibility.

```text
Anka DB Manager
│
├── Core
│   ├── Database
│   ├── Schema
│   ├── Query
│   ├── Model
│   ├── Migration
│   └── Authorization
│
├── Database Management
│   ├── Explorer
│   ├── Tables
│   ├── Indexes
│   ├── Foreign Keys
│   └── Relationships
│
├── Tools
│   ├── Query Builder
│   ├── SQL Console
│   ├── Query Profiler
│   ├── Database Diff
│   └── ER Diagram
│
├── Backup
│   ├── Backup
│   ├── Restore
│   └── Backup Management
│
└── Authentication
    ├── Login
    ├── Sessions
    └── Permissions
```

## Supported Databases

Current database support includes:

* MySQL
* MariaDB
* SQLite

The architecture is designed to allow additional database drivers to be integrated in the future.

## Requirements

* PHP 8.1 or newer
* PDO
* PDO MySQL
* PDO SQLite
* Web server such as Apache or Nginx

Depending on the database driver being used, additional PHP extensions may be required.

## Installation

Clone the repository:

```bash
git clone https://github.com/k7codes/Anka-DB-Manager.git
cd Anka-DB-Manager
```

Configure the application according to your environment and make sure the required PHP extensions are enabled.

Then point your web server's document root to the project directory and open the application through your browser.

## Security

Anka DB Manager is intended to be deployed in controlled and trusted environments.

Recommended production practices:

* Use HTTPS
* Protect administrative endpoints
* Use strong administrator passwords
* Enable CSRF protection
* Restrict database access
* Keep backup files outside the public web root
* Apply appropriate filesystem permissions
* Disable verbose error output in production
* Regularly update PHP and database servers

## Project Structure

```text
Anka-DB-Manager/
│
├── cekirdek/
├── veri/
├── yedek/
├── kanvas/
├── zeuger/
├── index.php
├── cerceve.php
├── sorgu.php
├── yedekleme.php
└── ...
```

## Roadmap

Future development may include:

* PostgreSQL improvements
* Redis management
* MongoDB support
* Advanced database monitoring
* Real-time query monitoring
* Plugin system
* REST API
* Advanced role management
* Improved schema visualization
* Remote database management
* Database synchronization
* Automated migration generation

## Contributing

Contributions, suggestions and improvements are welcome.

Before submitting a pull request:

1. Test the changes locally.
2. Keep the existing architecture consistent.
3. Avoid unnecessary dependencies.
4. Document new functionality.
5. Make sure existing functionality is not broken.

## License

This project is licensed under the MIT License.

<div align="center">

## Anka DB Manager

Built by K7

</div>
