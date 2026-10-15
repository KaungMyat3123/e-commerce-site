# MMart — PHP + SQL Server E-Commerce

A full PHP 8 / Microsoft SQL Server e-commerce application per `project_specifications_and_architecture.md`.

## Tech Stack
- PHP >= 8.1 with `pdo_sqlite` (default) or `pdo_sqlsrv` (SQL Server mode)
- SQLite file database (`database/ecommerce.db`) — created and seeded automatically on first request
- Microsoft SQL Server schema also provided (`database/schema.sql`) for the spec's MSSQL stack
- Bootstrap 5 (CDN) for the frontend

## Requirements
- **SQLite mode (default):** any PHP 8.1+ with `pdo_sqlite` (enabled out of the box). Nothing else to install.
- **MSSQL mode:** the Microsoft Drivers for SQL Server (`php_pdo_sqlsrv` / `php_sqlsrv`) extension enabled.
  - On Windows + XAMPP/WampServer: download the DLLs matching your PHP version/Architecture (NTS/TS) from https://github.com/microsoft/msphpsql, place them in `php\ext`, and uncomment in `php.ini`:
    ```ini
    extension=pdo_sqlsrv
    extension=sqlsrv
    ```
- For MSSQL mode you also need a running SQL Server instance (Express or higher).

## Setup
1. **SQLite (default):** nothing to do — the database file `database/ecommerce.db` is created and seeded from `database/schema.sqlite.sql` on the first page request.
2. **MSSQL (optional, per spec):** set `DB_DRIVER=sqlsrv` in `includes/config.php` or via environment, then run `database/schema.sql` in SSMS (or `sqlcmd -S "MISKMN-T14\SQLEXPRESS" -i database\schema.sql`).
3. **Connection settings** live in `includes/config.php` (env-overridable):
   | Variable | Default | Notes |
   |---|---|---|
   | `DB_DRIVER` | `sqlite` | `sqlite` or `sqlsrv` |
   | `DB_SERVER` | `MISKMN-T14\SQLEXPRESS` | sqlsrv only |
   | `DB_DATABASE` | `EcommerceDB` | sqlsrv only |
   | `DB_USER` | *(empty)* | empty = Windows Authentication; set for SQL Auth (e.g. `sa`) |
   | `DB_PASSWORD` | *(empty)* | |
4. **Serve the app** — point Apache/Nginx/IIS at this folder, or for a quick test run:
   ```
   php -S localhost:8000
   ```
4. Visit `http://localhost:8000/` for the storefront and `http://localhost:8000/admin/login.php` for the panel.

## Default Admin Login
- Username: `admin`
- Password: `Admin@123`

Change this immediately after first login (update the `Admins` table with a new `password_hash('YourNewPassword', PASSWORD_DEFAULT)` value).

## Project Structure
```
admin/            Admin panel (login, dashboard, categories, products, orders, order-detail, payments, inquiries)
assets/           css/, js/, uploads/ (product images)
database/         schema.sql — full T-SQL script + seed data
includes/         db.php (PDO + helpers), config.php, cart.php, auth.php, header/footer templates
index.php         Home — hero banner, categories, featured products
about.php         Company background, mission & vision
contact.php       Inquiry form -> ContactInquiries table
shop.php          Listing with search, category/price filters, sorting, pagination
product-detail.php Single product view + related items
cart.php          Session-based cart (add/update/remove)
checkout.php      Validated order placement in a DB transaction, stock decrement
invoice.php       Printable invoice/voucher (add ?print=1 for a clean print view)
```

## Notes
- Bootstrap/Bootstrap-Icons are loaded from the jsDelivr CDN; download and self-host them (with Subresource Integrity hashes) if you need offline or hardened deployments.
- Checkout runs in a transaction: order + items inserted and stock decremented atomically; overselling rolls back with an error.
- All form POSTs are CSRF-protected; all SQL uses prepared statements; all output is HTML-escaped.
