# CloudCart (PHP version)

A simple e-commerce website built with **plain PHP 8**, **MySQL (PDO)**, HTML, CSS, JavaScript and Bootstrap 5.
Built with PHP 8 and MySQL, tested locally and deployed successfully on AWS using EC2, RDS, an Application Load Balancer, and S3. 
No framework and no Composer packages are needed.

## Features

- Home page with featured products, Products page with search
- Register / Login / Logout (bcrypt password hashing, duplicate emails blocked)
- Shopping cart stored in MySQL per user (add, increase, decrease, remove, total)
- Checkout with shipping address; order saved to MySQL; product stock reduced
- Basic admin page to add, edit and delete products

## Project structure

```
cloudcart-php/
├── public/                 # the web root - the ONLY folder the web server exposes
│   ├── index.php           # home
│   ├── products.php        # product list + search
│   ├── register.php, login.php, logout.php
│   ├── cart.php            # cart page
│   ├── cart_action.php     # add / increase / decrease / remove
│   ├── checkout.php, order.php
│   ├── admin.php           # add / edit / delete products
│   ├── health.php          # load balancer health check
│   ├── css/style.css, js/script.js, images/
├── src/                    # PHP code that is NOT public
│   ├── bootstrap.php       # included by every page
│   ├── config.php          # reads .env / environment variables
│   ├── db.php              # PDO connection + helper functions
│   ├── DbSessionHandler.php# stores sessions in MySQL (needed for 2 servers)
│   ├── functions.php       # CSRF, login checks, cart helpers, formatting
│   └── header.php, footer.php, product_card.php
├── schema.sql              # tables + sample products
├── create_admin.php        # one-time command-line script to create an admin
├── .env.example            # copy to .env and edit
└── README.md
```

## Run it locally

### 1. Requirements
- **PHP 8.1 or newer** with the extensions `pdo_mysql` and `mbstring`
  (XAMPP / MAMP / Laragon include everything; on Ubuntu: `sudo apt install php-cli php-mysql php-mbstring`)
- **MySQL 8** (XAMPP's MariaDB also works)

Check with: `php -v` and `php -m` (look for `pdo_mysql` and `mbstring`).
If you installed PHP by hand on Windows, enable them in `php.ini` by removing the `;` before
`extension=pdo_mysql` and `extension=mbstring`.

### 2. Create the database

```bash
mysql -u root -p < schema.sql
```
(Or open `schema.sql` in MySQL Workbench / phpMyAdmin and run it.) This creates the `cloudcart` database,
all tables (`users`, `products`, `cart_items`, `orders`, `order_items`, plus `sessions`) and 8 sample products.
**Running it again deletes and recreates the tables.**

### 3. Configure

```bash
cp .env.example .env        # Windows: copy .env.example .env
```
Open `.env` and set `DB_PASSWORD` to your MySQL password (keep the double quotes).

### 4. Create an admin account

```bash
php create_admin.php
```
Enter an email, name and password. (No default admin is shipped on purpose.)

### 5. Start the app

```bash
php -S localhost:8000 -t public
```
Open **http://localhost:8000**

(With XAMPP you can instead put the folder in `htdocs` and point a virtual host's
`DocumentRoot` at the `public` folder.)

### 6. Quick test checklist
1. Register a new account, then log in. Registering the same email again shows an error.
2. On *Products*, search for `lamp`.
3. Add products to the cart; change quantities with **+ / −**; remove one; check the total.
4. Checkout: enter a shipping address and confirm. You see the order confirmation.
5. Check MySQL:
   ```sql
   USE cloudcart;
   SELECT * FROM orders;
   SELECT * FROM order_items;
   SELECT name, stock FROM products;   -- stock went down
   ```
6. Log in with the admin account and open **Admin** to add, edit and delete a product.

## Preparing for AWS later

The app is configured only through environment variables and keeps all state (cart, orders, **sessions**) in MySQL,
so nothing in the code has to change for AWS.

| Part of your architecture | What to do |
|---|---|
| **RDS (MySQL)** | Run `schema.sql` against the RDS endpoint, then set `DB_HOST` (RDS endpoint), `DB_USER`, `DB_PASSWORD`, `DB_NAME` in the `.env` on each EC2 instance (or as real environment variables). The RDS security group must allow port 3306 from the EC2 security group. |
| **Two EC2 instances** | Install PHP + a web server, copy the project to both servers and use the same `.env` database settings. PHP normally keeps sessions in files on one server, which would log users out when the load balancer switches servers. CloudCart stores sessions in the MySQL `sessions` table instead, so **no sticky sessions are needed**. |
| **Application Load Balancer** | Set the target group health check path to `/health.php` (`/health.php?db=1` also tests the database). Set `BEHIND_PROXY=true` so the session cookie is marked secure when the ALB serves HTTPS. Set `APP_DEBUG=false`. |
| **Web server** | Point the document root at the `public` folder. Example for Nginx + PHP-FPM: `root /var/www/cloudcart/public; index index.php; location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php-fpm.sock; }`. For Apache: `DocumentRoot /var/www/cloudcart/public`. Keep `.env` and `src/` **outside** the document root (they are, by default). |
| **S3 for images** | A product's `image_url` can already be a full URL. Upload the image to S3, then paste its URL in the Admin form (Store product images in S3 and serve them through CloudFront using Origin Access Control (OAC), keeping the S3 bucket private. Public access can be configured when appropriate, but is not required when using CloudFront OAC.). No code change needed. |

## Notes
- Prices are shown in `$`. Change the `money()` function in `src/functions.php` to use another currency symbol.
- All database queries use prepared statements, all output is escaped with `e()`, and every form has a CSRF token.
- Bootstrap is loaded from a CDN, so the pages need an internet connection to look right.
- Expired sessions are cleaned up automatically by PHP's garbage collector (rows in the `sessions` table).
