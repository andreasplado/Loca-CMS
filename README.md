# 🏗️ Locawork CMS

Locawork is a lightweight, high-performance PHP CMS featuring a **drag-and-drop block editor**, a robust **plugin architecture**, and a clean, modern administrative interface powered by Tailwind CSS.



---

## 🚀 Features

* **Block-Based Editor**: Build pages dynamically using text, image, and grid blocks.
* **Live Preview**: Real-time iframe previewing of page changes.
* **Plugin System**: Modular architecture allowing for ZIP uploads and dynamic "Smart Loading" of functionalities.
* **Clean URLs**: Native support for extension-less URLs (e.g., `/admin` instead of `/admin.php`).
* **Role-Based Access**: Permission-gate system for Admins vs. Viewers.
* **Modern UI**: Fully responsive sidebar and workspace built with Tailwind CSS and FontAwesome.

---

## 🛠️ Technical Stack

* **Backend**: PHP 8.x
* **Database**: MySQL / MariaDB (PDO)
* **Styling**: Tailwind CSS
* **Interactivity**: Vanilla JS & SortableJS
* **Server Config**: Apache `.htaccess` (Rewrite Engine)

---

## 📦 Installation

1.  **Clone the repository**:
    ```bash
    git clone [https://github.com/andreasplado/locawork-cms.git](https://github.com/andreasplado/locawork-cms.git)
    ```

2.  **Database Setup**:
    * Create a new database.
    * Import the schema from `core/schema.sql` (if provided) or manually create the `pages` and `plugins` tables.
    * Configure your credentials in `core/config.php`.

3.  **Permissions**:
    Ensure the `plugins/` and `uploads/` directories are writable:
    ```bash
    chmod -R 775 plugins/
    ```

4.  **Server Config**:
    Ensure `mod_rewrite` is enabled on your Apache server to support clean URLs.

---

## 🔌 Plugin Development

Locawork uses a **Smart Loader** system. To create a plugin:

1.  Create a folder in `/plugins/` (e.g., `my-plugin`).
2.  Add a `.php` file (e.g., `main.php`).
3.  The CMS will automatically include any `.php` file inside an active plugin's folder.



---

## 📂 Project Structure

```text
├── admin.php          # Main administrative entry point
├── core/
│   ├── config.php     # DB connection & Smart Plugin Loader
│   └── auth.php       # Session management
├── plugins/           # Uploaded plugin directories
├── admin_pages.php    # Page management UI
├── admin_plugins.php  # Plugin manager & ZIP uploader
└── .htaccess          # URL rewriting rules