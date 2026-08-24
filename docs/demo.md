# 3–5 Minute Demonstration Script

Begin by introducing the business: “This is Masha Allah Mobile & EasyPaisa Shop, a local business based at Main Chowrangi, Sibi Road, Quetta. The project is developed in PHP 8 and MySQL/MariaDB for XAMPP.” Show the home page in a desktop browser and then switch the browser to a mobile width. Point out the responsive navigation, bilingual labels, service highlights, selected products, and the inquiry call to action.

Next, open the Products page. Search for a product such as `Ronin` or select the Cables category. Demonstrate the sorting menu and explain the stock labels. Show that the displayed price is a starting retail price and that the interface invites customers to ask the shop for current information. Then open Services and About to show the five core services and local Quetta business information.

Open the Contact page. Show the embedded map and the inquiry form. Submit one short valid message using a test email address. Explain that the form data is written to the `contact_messages` table rather than being shown only as a visual confirmation.

Then open `admin/login.php` and sign in using the administrator account created during setup. In the dashboard, show the product count, total stock, low-stock count, and the new inquiry. Open Products and demonstrate adding or editing a product. If possible, upload a small JPG, PNG, or WEBP image and explain the type and size checks. Show that the product record has a category relationship and that the public catalogue reflects administrator changes.

Finally, open Services and demonstrate editing or adding a service, then open Inquiries and mark the test inquiry as read. Conclude by opening `schema.sql` in the editor and briefly explain the related tables: categories to products, users for authentication, services for managed content, and contact messages for inquiries. Mention that the source package also includes the project report, installation guide, and 48-item inventory seed script.
