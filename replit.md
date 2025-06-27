# Aplikasi Akuntansi

## Overview

This is a comprehensive accounting application built with Flask that implements a complete double-entry bookkeeping system. The application provides functionality for managing chart of accounts, journal entries, adjusting entries, closing entries, and generating financial reports including balance sheets, income statements, and trial balance.

## System Architecture

### Backend Architecture
- **Framework**: Flask (Python web framework)
- **Storage**: In-memory data structures (dictionaries and lists)
- **Session Management**: Flask sessions with server-side secret key
- **Authentication**: Simple username/password with hashed passwords using Werkzeug
- **Deployment**: Gunicorn WSGI server configured for production deployment

### Frontend Architecture
- **Template Engine**: Jinja2 (Flask's default)
- **CSS Framework**: Bootstrap 5 with dark theme
- **Icons**: Font Awesome 6.0
- **JavaScript**: Vanilla JavaScript for client-side functionality
- **Responsive Design**: Bootstrap responsive grid system

### Data Storage
- **Chart of Accounts**: Dictionary-based structure with account codes as keys
- **Journal Entries**: List-based storage with separate arrays for regular, adjusting, and closing entries
- **User Authentication**: Dictionary-based user storage with role-based access control

## Key Components

### Authentication System
- Role-based access control (admin/user)
- Password hashing using Werkzeug security
- Session management for user state
- Default demo accounts for testing

### Chart of Accounts Management
- Hierarchical account structure (Assets, Liabilities, Equity, Revenue, Expenses)
- Account codes with 4-digit numbering system
- Balance tracking using Python Decimal for precision
- Admin-only account creation functionality

### Journal Entry System
- Double-entry bookkeeping validation
- Multiple journal types: Regular, Adjusting, Closing
- Real-time balance calculation
- Date and description tracking for all entries

### Financial Reporting
- **Balance Sheet**: Assets, Liabilities, and Equity presentation
- **Income Statement**: Revenue and Expense analysis with net income calculation
- **Trial Balance**: Complete account listing with debit/credit balances
- **General Ledger**: Detailed transaction history per account
- **Equity Statement**: Changes in equity accounts over time

### User Interface
- Responsive design supporting mobile and desktop
- Dark theme implementation
- Intuitive navigation with dropdown menus
- Print functionality for financial reports
- Form validation and user feedback

## Data Flow

### Transaction Processing
1. User inputs journal entry through web form
2. Client-side validation ensures debit/credit balance
3. Server processes entry and updates account balances
4. Entry stored in appropriate journal array
5. Account balances recalculated using Decimal arithmetic

### Report Generation
1. System aggregates data from journal entries
2. Account balances calculated from all journal types
3. Financial statements generated using Jinja2 templates
4. Reports rendered with proper formatting and styling

### User Authentication Flow
1. Login form validates credentials against user database
2. Password verification using secure hashing
3. Session established with user information
4. Role-based access control enforced throughout application

## External Dependencies

### Python Packages
- **Flask**: Web framework and core functionality
- **Werkzeug**: Security utilities for password hashing
- **Gunicorn**: Production WSGI server
- **email-validator**: Email validation (prepared for future features)
- **psycopg2-binary**: PostgreSQL adapter (prepared for database migration)

### Frontend Dependencies
- **Bootstrap 5**: CSS framework with dark theme
- **Font Awesome 6**: Icon library
- **CDN-delivered**: All frontend assets loaded from CDNs

### System Dependencies
- **Python 3.11**: Runtime environment
- **OpenSSL**: Cryptographic functionality
- **PostgreSQL**: Database system (prepared for future migration)

## Deployment Strategy

### Development Environment
- Flask development server with debug mode
- Hot reloading for code changes
- In-memory data storage for rapid prototyping

### Production Environment
- Gunicorn WSGI server with multiple workers
- Autoscale deployment target on Replit
- Environment-based configuration for security
- Production-ready session management

### Configuration Management
- Environment variables for sensitive configuration
- Separate development and production settings
- Configurable session secrets and database connections

## Changelog
- June 27, 2025. Initial setup

## User Preferences

Preferred communication style: Simple, everyday language.