# 🏗️ Locawork Visual Builder

A high-performance, Elementor-inspired **Drag-and-Drop Page Builder** built with PHP, GridStack.js, and Tailwind CSS. This tool allows for real-time layout management, widget customization, and seamless database synchronization.



## 🚀 Features

* **Dynamic Grid Engine**: Powered by `GridStack.js` for smooth dragging, resizing, and floating layouts.
* **Auto-Load System**: Automatically restores your saved layout from the database upon page load.
* **Elementor-Style Inspector**: Dedicated sidebar for editing widget content and Tailwind CSS styling.
* **One-Click Duplication**: Clone existing widgets with all their styles and data intact.
* **JSON-Based Storage**: Efficiently saves complex layouts into a single `LONGTEXT` column.
* **Modern UI**: Fully responsive interface built with Tailwind CSS and FontAwesome icons.

---

## 🛠️ Tech Stack

| Component | Technology |
| :--- | :--- |
| **Server Side** | PHP 8.x |
| **Database** | MySQL (PDO) |
| **Grid Engine** | GridStack.js 7.2.3 |
| **Styling** | Tailwind CSS |
| **Icons** | FontAwesome 6.4.0 |

---

## 📂 Installation & Setup

### 1. Database Configuration
Your `pages` table requires a column to store the JSON string. Ensure it is set to `LONGTEXT` to prevent data truncation for large pages.

```sql
ALTER TABLE pages MODIFY COLUMN content LONGTEXT;