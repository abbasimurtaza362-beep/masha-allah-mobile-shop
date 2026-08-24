# Masha Allah Mobile & EasyPaisa Shop

**Course Project Report**

| Item | Details |
| --- | --- |
| Project type | Responsive local-business website with PHP administration panel |
| Business | Masha Allah Mobile & EasyPaisa Shop |
| Location | Main Chowrangi, Sibi Road, Quetta, Pakistan |
| Technology | PHP 8+, MySQL/MariaDB, HTML5, CSS3, PDO, JavaScript where required |
| Prepared by | ______________________________ |
| Submission date | ______________________________ |

## 1. Business Problem

Masha Allah Mobile & EasyPaisa Shop is a local mobile-accessories and digital-services business in Quetta. The shop sells common mobile accessories, offers basic repairing and software-related support, and provides available EasyPaisa and JazzCash assistance. Before this project, a customer would normally need to visit in person or rely on informal communication to find out whether a product was available, what its starting price was, and which services the shop could provide. This makes it difficult for customers to plan a visit and for the shop owner to keep service information organised.

The central business problem is therefore the absence of a clear, trustworthy digital catalogue and a manageable inquiry process. A customer needs an easy way to browse the available products, identify categories such as cables, chargers, earphones, speakers, batteries, and wireless earbuds, and submit a question without needing unverified contact details. At the same time, the business owner needs a protected workspace to update product prices, stock quantities, descriptions, services, images, and customer messages without changing source code.

The project also responds to the expectations of a Pakistani local-business audience. The interface uses English for broad usability and includes Urdu labels for familiar navigation and service language. The design avoids unsupported promises such as fixed repair outcomes, unavailable phone numbers, or claims about inventory that have not been confirmed. This makes the website more suitable for a real local shop than a generic e-commerce template.

## 2. Proposed Solution

The proposed solution is a responsive PHP and MySQL website that connects the public shop website with a secure administration panel. The public website includes Home, About, Products, Services, Contact, Login, and Sign Up pages. The Home page introduces the business, its location, service categories, selected catalogue entries, and a call to action. The About page gives a concise, factual explanation of the local business. The Products page allows customers to search products, filter them by category, and sort by price or newest entries. Stock status is displayed as In Stock, Low Stock, or Out of Stock based on the stored quantity.

The contact page includes an embedded interactive map based on the supplied Quetta address and a customer inquiry form. The form collects a name, email, optional phone number, subject, and message. Every valid inquiry is saved in the MySQL database and appears in the owner’s protected inquiries screen. Since the verified business phone number and WhatsApp number were not supplied, the system intentionally does not invent them. The administrator can add verified contact details later.

The protected administration panel supports the required CRUD operations. After a secure owner login, the administrator can create, read, edit, and delete products and services. Product management includes the category, selling price, quantity, description, status, and product image. The administrator can also review inquiries, mark them as read, and delete them when they are no longer required. This solves the maintenance problem because the shop owner does not need to edit PHP files to keep the catalogue current.

| Requirement | Implemented solution |
| --- | --- |
| Responsive local-business website | Mobile-first CSS layout for home, about, products, services, and contact pages |
| Administration login | Session-protected PHP login with owner-only role checking |
| CRUD | Product and service create, read, update, and delete operations |
| Image upload | JPG, PNG, and WEBP upload validation with randomized server-side filenames |
| MySQL database | Related `categories`, `products`, `services`, `users`, and `contact_messages` tables |
| Contact form | Validated inquiry storage in the database and an admin follow-up view |
| AI use | Original product/hero visuals and implementation support, documented below |

## 3. Database and Technical Design

The database uses MySQL/MariaDB and InnoDB tables. The `categories` table stores the product groups, while the `products` table has a foreign key named `category_id` that connects each product to one category. This relationship prevents a product from being inserted without a valid category. The `services` table stores service names, descriptions, display order, image references, and active or inactive status. The `users` table contains account names, email addresses, password hashes, and roles. The `contact_messages` table stores customer inquiries and their reading status.

The application uses PHP Data Objects (PDO) and prepared statements for all database values. Passwords are not stored in plain text. The owner account is created one time using `setup.php`, which calls `password_hash`. Login uses `password_verify` and regenerates the session identifier after a successful login. All management pages use an admin guard that checks the active session role before data is displayed or changed.

Important forms include a CSRF token. Output is escaped with `htmlspecialchars` through a helper function. File upload protection checks the uploaded file error status, a 4 MB maximum size, an allow-list of JPEG, PNG, and WEBP MIME types, and a real image check. The application does not reuse the uploaded filename; it assigns a random server-side filename and stores only the safe relative path in the database.

## 4. AI Tools Used

AI tools were used appropriately in three areas. First, they were used to develop an initial design direction for a premium, bilingual local-business website. The visual direction uses a restrained navy, warm-gold, and off-white palette, strong typographic hierarchy, and clearly separated card layouts. Second, AI image generation was used to create original visual assets for the hero area and initial product set. No stock photographs were used. For items without an exact verified visual, the website deliberately uses an honest neutral visual state instead of falsely representing a similar item as the exact product.

Third, AI assistance was used during development to structure the PHP application, produce clear comments and documentation, and create safe implementation patterns. The final PHP source code remains understandable for a student demonstration because the files are grouped into `config`, `includes`, `admin`, `assets`, and public pages. AI was not used to fabricate customer reviews, customer data, supplier data, business phone numbers, or false business claims.

## 5. Challenges Faced and How They Were Addressed

One challenge was converting a real shop inventory into a relational catalogue without inventing products. The solution was to retain the supplied forty-eight product names, quantities, and estimated starting retail prices in `seed.php`. The script checks whether a product already exists before inserting it, so it can be used safely during initial local setup. The category relationship was also chosen carefully so the catalogue can be filtered in a meaningful way.

Another challenge was balancing a visually polished website with accurate product representation. Not every named accessory had a supplied physical photograph. Using random web images would be misleading, so the solution was to use original AI-generated visual assets only where appropriate and to preserve an image-pending state for products that need owner-approved photos. The administrator can upload verified images later using the protected product management form.

The third challenge was security in a student-level PHP project. To address it, the project uses PDO prepared statements rather than string-built SQL, password hashing rather than plaintext passwords, session management, role protection, CSRF tokens, output escaping, file size and MIME validation, randomized upload filenames, and delete confirmations in the administration panel. These controls make the project suitable for a classroom localhost demonstration and provide a clear basis for explaining secure development practices.

## 6. Conclusion

This project provides a complete local-business solution for Masha Allah Mobile & EasyPaisa Shop. It gives customers a mobile-friendly way to discover products and services, check starting prices and stock indicators, find the Quetta address, and submit inquiries. It gives the owner a secure PHP administration panel with CRUD operations and image upload support. The project meets the assessment requirements while remaining grounded in the supplied business information and avoiding made-up commercial content.

For future improvement, the shop can add its verified telephone number, WhatsApp link, operating hours, exact map pin, owner-approved photos for all products, and a real notification service for email or messaging alerts. These additions can be made without changing the core database design.
