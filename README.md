# __HayagSync Backend and Web Portal (Laravel)__

This project uses WSL2 + Ubuntu + nginx + opcache + Docker machine.

---

## Setup Guide

A simple guide for groupmates who need to run the **HayagSync Laravel 13 project** on Windows.

> **NOTE:** You do not need to install PHP, MySQL, Redis, Composer, or Node.js directly on Windows. Docker will run these for you.

---

## I. What You Need to Install

Install these three things first:

1. **WSL2**
2. **Ubuntu** for WSL2
3. **Docker Desktop**

The easiest setup is:

**Windows → WSL2 → Ubuntu → Docker Desktop → HayagSync**

---

## II. Install WSL2

### Step 1 — Open PowerShell as Administrator

Search for:

> PowerShell → Run as administrator

Run:

```powershell
wsl --install
```

This normally installs WSL2 and Ubuntu.

### Step 2 — Restart your computer

After restarting, Ubuntu may open automatically.

Create your:

- Linux username
- Linux password

The Linux password is separate from your Windows password.

### Step 3 — Check WSL

Open **PowerShell** and run:

```powershell
wsl --status
```

You should see that WSL is using version 2.

You can also run:

```powershell
wsl -l -v
```

You should see something similar to:

```text
NAME      STATE     VERSION
Ubuntu    Running   2
```

If Ubuntu is using version 1, run:

```powershell
wsl --set-version Ubuntu 2
```

---

## III. Install / Update Ubuntu

Open **Ubuntu** from the Start Menu.

Run:

```bash
sudo apt update
sudo apt upgrade -y
```

Check that Ubuntu is working:

```bash
uname -a
```

It should show Linux/WSL2 information.

---

## IV. Install Docker Desktop

Download and install **Docker Desktop for Windows**.

During installation, make sure the option to use **WSL 2** is enabled.

After installation:

1. Open Docker Desktop.
2. Go to **Settings**.
3. Open **Resources → WSL Integration**.
4. Enable integration for your **Ubuntu** distribution.
5. Click **Apply & Restart**.

Docker Desktop must be **running** whenever you want to run HayagSync.

### Check Docker from Ubuntu

Open Ubuntu and run:

```bash
docker --version
docker compose version
```

Both commands should return a version number.

Then test:

```bash
docker run hello-world
```

If it finishes successfully, Docker is working.

---

# V. Get the HayagSync Project

You should have received the HayagSync project folder/repository from the group.

### Recommended location

Keep the project inside your WSL Ubuntu filesystem, for example:

```text
~/projects/hayagsync-web
```

Create the folder:

```bash
mkdir -p ~/projects
cd ~/projects
```

Then clone the repository:

```bash
git clone <YOUR-REPOSITORY-URL> hayagsync-web
```

Enter the project:

```bash
cd hayagsync-web
```

> If you already received the project as a ZIP, extract/copy it into your WSL project folder instead.

---

# VI. Check the Project Structure

The project should contain something similar to:

```text
hayagsync-web/
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── local.ini
│   │   └── opcache.ini
│   ├── nginx/
│   │   └── conf.d/
│   │       └── app.conf
│   └── mysql/
│       └── my.cnf
├── certs/
├── src/
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── composer.json
│   ├── package.json
│   └── .env
├── docker-compose.yml
└── .env
```

The Laravel application is inside:

```text
src/
```

---

# VII. Open the Project in VS Code

From the Ubuntu terminal, inside the project folder:

```bash
code .
```

If `code` is not recognized, install/use the **WSL extension** in VS Code and reopen the project through WSL.

You should see something similar in the VS Code bottom-left corner:

```text
WSL: Ubuntu
```

> **Important:** Work on the project through WSL rather than opening the project directly from a Windows-mounted folder when possible. This helps avoid Linux permission and file-performance problems.

---

# VIII. Create UID/GID Settings

From the **project root**:

```bash
echo "UID=$(id -u)" >> .env
echo "GID=$(id -g)" >> .env
```

This helps Docker create files with your Ubuntu user's permissions instead of root permissions.

> This `.env` is the Docker Compose environment file in the **project root**.  
> Laravel has its own `.env` inside `src/`.

---

# IX. Local HTTPS Certificate

HayagSync uses:

```text
https://hayagsync.test
```

The Docker setup uses `mkcert` for the local certificate.

Install it inside Ubuntu:

```bash
sudo apt update
sudo apt install -y libnss3-tools
```

Download mkcert:

```bash
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
```

Make it executable:

```bash
chmod +x mkcert-v*-linux-amd64
```

Move it:

```bash
sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert
```

Install the local certificate authority:

```bash
mkcert -install
```

Create the HayagSync certificate:

```bash
mkcert \
  -cert-file certs/localhost.pem \
  -key-file certs/localhost-key.pem \
  localhost 127.0.0.1 ::1 hayagsync.test
```

Add the local domain:

```bash
echo "127.0.0.1 hayagsync.test" | sudo tee -a /etc/hosts
```

---

# X. Check Laravel `.env`

Make sure the Laravel environment file exists:

```text
src/.env
```

The important Docker database settings should look like:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=hayagsync
DB_USERNAME=hayagsync
DB_PASSWORD=secret
```

Redis:

```dotenv
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=
REDIS_PORT=6379
```

Sessions/cache/queues:

```dotenv
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

Application URL:

```dotenv
APP_URL=https://hayagsync.test
```

Sanctum:

```dotenv
SANCTUM_STATEFUL_DOMAINS=hayagsync.test,localhost
```

> **Do not commit real API keys, email passwords, Twilio credentials, or other secrets to Git.** Ask the project owner for the correct local `.env` values if they are not already provided.

---

# XI. Start HayagSync

From the project root:

```bash
docker compose up -d --build
```

The first build can take a while.

Check the containers:

```bash
docker compose ps
```

You should see services such as:

```text
app
nginx
mysql
redis
queue
reverb
node
```

---

# XII. Install Laravel Dependencies

Run:

```bash
docker compose exec app composer install
```

Then generate the application key if needed:

```bash
docker compose exec app php artisan key:generate
```

Run database migrations:

```bash
docker compose exec app php artisan migrate
```

Create the storage link:

```bash
docker compose exec app php artisan storage:link
```

---

# XIII. Install Frontend Dependencies

Run:

```bash
docker compose exec node npm install
```

The `node` container is used for the React/Inertia/Vite frontend.

---

# XIV. Open HayagSync

Open your browser:

```text
https://hayagsync.test
```

If everything is working, the HayagSync web application should appear.

---

# XV. Useful Docker Commands

## Start the project

```bash
docker compose up -d
```

## Start and rebuild

Use this after changing the Dockerfile or Docker configuration:

```bash
docker compose up -d --build
```

## Stop the project

```bash
docker compose down
```

## See running containers

```bash
docker compose ps
```

## See all logs

```bash
docker compose logs
```

## Follow Laravel/PHP logs

```bash
docker compose logs -f app
```

## Follow queue logs

```bash
docker compose logs -f queue
```

## Follow Reverb logs

```bash
docker compose logs -f reverb
```

## Enter the Laravel container

```bash
docker compose exec app bash
```

Then you can run:

```bash
php artisan migrate
php artisan tinker
php artisan route:list
```

Exit:

```bash
exit
```

---

# XVI. Common Laravel Commands in Docker

Because Laravel is inside Docker, use:

```bash
docker compose exec app php artisan <command>
```

Examples:

```bash
docker compose exec app php artisan migrate
```

```bash
docker compose exec app php artisan migrate:fresh
```

```bash
docker compose exec app php artisan cache:clear
```

```bash
docker compose exec app php artisan config:clear
```

```bash
docker compose exec app php artisan route:list
```

```bash
docker compose exec app php artisan storage:link
```

---

# XVII. Common Problems

## A. `docker: command not found`

Make sure:

1. Docker Desktop is installed.
2. Docker Desktop is running.
3. Ubuntu is enabled in Docker Desktop's **WSL Integration**.
4. Restart Docker Desktop.

Then test:

```bash
docker --version
```

---

## B. `docker compose` does not work

Run:

```bash
docker compose version
```

If it does not work, check Docker Desktop and WSL integration.

Use:

```bash
docker compose
```

not the older:

```bash
docker-compose
```

---

## C. Website gives `502 Bad Gateway`

Check PHP:

```bash
docker compose logs app
```

Also check:

```bash
docker compose ps
```

Make sure `app` is running.

You can restart it:

```bash
docker compose restart app nginx
```

---

## D. Database connection error

Check MySQL:

```bash
docker compose logs mysql
```

Then check:

```bash
docker compose exec app php artisan migrate:status
```

The Laravel `.env` must use:

```dotenv
DB_HOST=mysql
```

**Do not use `localhost` for MySQL inside the Laravel container.**

---

## E. Redis connection error

Check:

```bash
docker compose logs redis
```

Laravel should use:

```dotenv
REDIS_HOST=redis
```

not:

```dotenv
REDIS_HOST=localhost
```

---

## F. Vite/React is not updating

Check the Node container:

```bash
docker compose logs node
```

Restart it:

```bash
docker compose restart node
```

If necessary:

```bash
docker compose exec node npm install
```

---

## G. Permission errors

If you see errors involving `storage`, `bootstrap/cache`, or files being owned by root, check:

```bash
id -u
id -g
```

Then make sure the project-root `.env` contains:

```dotenv
UID=1000
GID=1000
```

Use your actual values from:

```bash
id -u
id -g
```

Then rebuild:

```bash
docker compose down
docker compose up -d --build
```

---

## H. HTTPS certificate warning

If the browser says the certificate is not trusted, run:

```bash
mkcert -install
```

If you are using a Windows browser, you may also need to install `mkcert` on Windows and run:

```powershell
mkcert -install
```

Then restart the browser.

---

# XVIII. Important: Do Not Delete Docker Volumes

The MySQL database is stored in a Docker volume.

Normally:

```bash
docker compose down
```

**does not delete your database.**

However, this command deletes the volumes:

```bash
docker compose down -v
```

That can delete the local MySQL/Redis data.

### Avoid this unless you intentionally want to reset the database.

---

# XIX. If You Pull New Changes From Git

When a groupmate pushes new code:

```bash
git pull
```

Then, depending on what changed:

### PHP dependencies changed

```bash
docker compose exec app composer install
```

### JavaScript dependencies changed

```bash
docker compose exec node npm install
```

### Database migrations were added

```bash
docker compose exec app php artisan migrate
```

### Dockerfile changed

```bash
docker compose up -d --build
```

### Docker Compose configuration changed

```bash
docker compose up -d
```

---

# XX. Quick Setup — Copy/Paste Version

After WSL2, Ubuntu, and Docker Desktop are already installed and working:

```bash
# Go to your project
cd ~/projects/hayagsync-web

# Check Docker
docker --version
docker compose version

# Set your WSL UID/GID
echo "UID=$(id -u)" >> .env
echo "GID=$(id -g)" >> .env

# Start/build Docker
docker compose up -d --build

# Install Laravel dependencies
docker compose exec app composer install

# Generate key if needed
docker compose exec app php artisan key:generate

# Database
docker compose exec app php artisan migrate

# Storage
docker compose exec app php artisan storage:link

# Frontend dependencies
docker compose exec node npm install

# Check containers
docker compose ps
```

Then open:

```text
https://hayagsync.test
```

---

# XXI. How the Setup Works

You do **not** need to manually install every technology.

Docker runs the project's services for you:

```text
                    Your Windows PC
                          │
                    Docker Desktop
                          │
                        WSL2
                          │
                       Ubuntu
                          │
                  HayagSync Docker
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
      Nginx            PHP/Laravel        Node/Vite
        │                 │
        │          ┌──────┴──────┐
        │          │             │
        │        MySQL          Redis
        │
        └──────── Reverb / WebSocket
```

### Main services

| Service | Purpose |
|---|---|
| `app` | Laravel/PHP application |
| `nginx` | Web server and HTTPS |
| `mysql` | HayagSync database |
| `redis` | Cache, sessions, and queues |
| `queue` | Processes Laravel background jobs |
| `reverb` | Real-time WebSocket communication |
| `node` | React/Inertia/Vite development server |

---

# 22. Additinal Debugging

If HayagSync does not work, send these outputs to the project owner:

```bash
docker compose ps
```

and:

```bash
docker compose logs --tail=100 app
```

If the problem involves the frontend:

```bash
docker compose logs --tail=100 node
```

If it involves the database:

```bash
docker compose logs --tail=100 mysql
```

This makes debugging much easier.

---

# 23. Final Checklist

Before saying **"it doesn't work"**, check:

- [ ] WSL2 is installed
- [ ] Ubuntu is installed and uses WSL2
- [ ] Docker Desktop is installed
- [ ] Docker Desktop is running
- [ ] Ubuntu is enabled under Docker Desktop → WSL Integration
- [ ] `docker --version` works
- [ ] `docker compose version` works
- [ ] Project is inside WSL
- [ ] Project `.env` exists
- [ ] `src/.env` exists
- [ ] `docker compose up -d --build` completed
- [ ] `docker compose ps` shows the services running
- [ ] `composer install` completed
- [ ] `php artisan migrate` completed
- [ ] `npm install` completed
- [ ] `https://hayagsync.test` opens

If all of these are checked, the HayagSync Laravel Docker environment should be ready.
