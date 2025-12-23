# Exchange Mini Engine

A Laravel-based exchange trading engine built with Inertia.js and Vue 3.

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm
- SQLite (default) or MySQL/PostgreSQL

## Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/usamamuneerchaudhary/exchange-mini-engine.git
   cd exchange-mini-engine
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Set up environment file**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   Update the Reverb keys in `.env`:
   - `REVERB_APP_ID`
   - `REVERB_APP_KEY`
   - `REVERB_APP_SECRET`

5. **Run migrations and seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Start the development server**
   ```bash
   composer dev
   ```

The `composer dev` command will start:
- Laravel development server
- Queue worker
- Log viewer (Pail)
- Vite dev server

Visit `http://localhost:8000` in your browser.

## Screenshots
![Screenshot 2025-12-23 at 13.44.32.png](screenshots/Screenshot%202025-12-23%20at%2013.44.32.png)

## Running Tests

```bash
php artisan key:generate --env=testing
composer test
```

