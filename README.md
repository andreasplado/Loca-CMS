# 🏗️ Locawork CMS: The Full Suite Documentation

Locawork CMS is a JSON-driven, block-based Content Management System. It replaces traditional, rigid HTML storage with a modern "blueprint" system. Pages are stored as structured JSON payloads, allowing for perfect design precision, easy maintenance, and lightning-fast rendering across all devices.




## 📂 1. Directory Structure

/locawork-cms
├── core/
│   └── config.php         # Database Connection (PDO)
├── admin_builder.php      # The Visual Builder (Admin interface)
├── save_handler.php       # The API (Bridge between Builder and DB)
├── index.php              # The Public Renderer (Live website)
└── README.md              # Documentation
## 🛠️ 2. Database InstallationRun
the following SQL query in your database manager (such as phpMyAdmin) to create the necessary architecture to store your page blueprints:SQLCREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL, -- Stores the JSON Grid Blueprint
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


## ⚙️ 3. Component Breakdown
### A.
The Core (core/config.php)This file establishes the secure PDO connection. It is the dependency required by the builder, the handler, and the renderer.
### B.
The Visual Builder (admin_builder.php)The primary workspace for administrators. Built using GridStack.js and Tailwind CSS.Auto-Loading: On startup, it fetches the JSON from the database and rebuilds the grid exactly as it was last saved.Widgets: Includes Headings, Paragraphs, Images, and Buttons.Inspector: A sidebar for live-editing text content and Tailwind utility classes.Duplicate: A clone feature that creates an exact copy of a widget with a new unique ID.
### C.
The Save Handler (save_handler.php)A specialized API endpoint. It listens for POST requests from the builder, parses the JSON array, and updates the SQL content column for the specific page_id.
### D. The Public Renderer (index.php)The performance-optimized "visitor" side of the CMS. It reads the JSON array and loops through it to generate a clean, static-like website for the end user without the overhead of the builder's JavaScript.

## 📝 4. Data Format (The Blueprint)
The CMS does not save raw HTML blocks. It saves a JSON Blueprint. This ensures the data is portable, searchable, and future-proof.JSON[
  {
    "x": 0,
    "y": 0,
    "w": 12,
    "h": 2,
    "id": "el_unique_123",
    "type": "heading",
    "content": "Welcome to Locawork",
    "extra": "text-4xl font-black text-blue-600 text-center"
  }
]
## 🚀 5. Quick Start Guide
Connect:
Update core/config.php with your local or production database credentials.Setup: Add a row to your pages table (e.g., ID: 1, Title: "Home Page").Build: Visit admin_builder.php?id=1. Drag widgets from the sidebar and design your layout.Publish: Click the "Publish Changes" button to sync the JSON to the database.View: Visit index.php?id=1 to see the live, rendered website.
## 🛠️ 6. Troubleshooting
Issue
Solution"Save Failed"Ensure save_handler.php exists and the fetch URL in the builder matches the filename.Grid is emptyCheck if the content column in the DB is empty or contains malformed JSON.Icons missingEnsure you have an active internet connection for the FontAwesome CDN.© 2026 Locawork Admin Suite. Optimized for flexibility and speed.