<?php
declare(strict_types=1);

function auto_seed_catalog(PDO $pdo): void
{
    try {
        // Only run when the required tables exist.
        if ($pdo->query("SHOW TABLES LIKE 'products'")->fetchColumn() !== 'products') {
            return;
        }

        if ($pdo->query("SHOW TABLES LIKE 'categories'")->fetchColumn() !== 'categories') {
            return;
        }

        $inventory = [
            ['2040 GTS MP3','Speakers',5,1099],
            ['MP3 GTS 2068','Speakers',1,1099],
            ['MP3 KIX 1477','Speakers',9,1399],
            ['KS43','Earphones',10,199],
            ['ST12','Earphones',50,149],
            ['MARS MP1 8000','Speakers',5,1299],
            ['MP3 2 KTS 2295','Speakers',4,1099],
            ['MP3 1251 KTS','Speakers',5,1149],
            ['SIGMA TC1 V8','Cables',15,299],
            ['SIGMA TC2','Cables',13,399],
            ['Goodlife GL24','Cables',35,149],
            ['Ronin R2025 Type-C','Cables',10,399],
            ['Ronin R2025 V8','Cables',10,349],
            ['Ronin R2025 iPhone','Cables',4,449],
            ['Ronin R2045 Type-C to iPhone','Cables',1,599],
            ['MP3 DV 1185','Speakers',2,1199],
            ['MP3 219 Mini','Speakers',2,1099],
            ['MP3 DV 1018','Speakers',2,1099],
            ['MP3 X 2652','Speakers',1,1049],
            ['MP3 DV 1211S','Speakers',1,1099],
            ['Buraq 5','Batteries',17,299],
            ['ITELE BL 5C','Batteries',10,199],
            ['FASTER O3 S Z12 240','Cables',5,299],
            ['SIGMA F1 V8','Cables',10,279],
            ['Sovo S008 Type-C','Cables',4,449],
            ['Sovo S008 V8','Cables',4,399],
            ['Sovo S006 Type-C','Cables',4,299],
            ['SIGMA Clip1 V8','Cables',9,699],
            ['Block Hares C100','Chargers',25,399],
            ['V8 Cable','Cables',50,99],
            ['313 8600 Cable','Cables',50,119],
            ['Sovo SH15','Cables',7,299],
            ['X Mart 2','Chargers',4,499],
            ['Type-C IN V8 APKINA','Cables',2,799],
            ['Mobile Socket','Repair',2,299],
            ['R9 Ronin','Cables',3,699],
            ['FASTER S9','Chargers',5,499],
            ['FASTER C62 2-in-1','Cables',11,599],
            ['FASTER FCC950','Chargers',5,799],
            ['SH04','Chargers',2,599],
            ['Login L600 V8','Cables',1,749],
            ['Shock Sasta Cable','Cables',10,30],
            ['65W A78 2-in-1 FAST','Chargers',1,699],
            ['Amplyzo 2-in-1 45W AP43','Chargers',1,599],
            ['Airbuds BUDS PRO8 ANC','TWS',6,1199],
            ['Buds Pro 7','TWS',4,1199],
            ['Airbuds Black','TWS',2,1299],
            ['MP3 Khaki Y30','Speakers',6,599],
        ];

        $images = [
            '2040 GTS MP3'=>'product-2040-gts-mp3.webp',
            'MP3 GTS 2068'=>'product-2068-gts-mp3.webp',
            'MP3 KIX 1477'=>'product-1477-kix-mp3.webp',
            'KS43'=>'product-ks43-earphones.webp',
            'ST12'=>'product-st12-earphones.webp',
            'MARS MP1 8000'=>'product-mars-mp1-8000.webp',
            'MP3 2 KTS 2295'=>'product-kts-2295.webp',
            'MP3 1251 KTS'=>'product-kts-1251.webp',
            'SIGMA TC1 V8'=>'product-sigma-tc1-v8.webp',
            'SIGMA TC2'=>'product-sigma-tc2.webp',
            'Goodlife GL24'=>'product-goodlife-gl24.webp',
            'Ronin R2025 Type-C'=>'product-ronin-r2025-type-c.webp',
            'Ronin R2025 V8'=>'product-ronin-r2025-v8.webp',
            'Ronin R2025 iPhone'=>'product-ronin-r2025-iphone.webp',
            'Ronin R2045 Type-C to iPhone'=>'product-ronin-r2045-type-c-to-iphone.webp',
            'MP3 DV 1185'=>'product-dv-1185.webp',
            'MP3 219 Mini'=>'product-219-mini.webp',
            'MP3 DV 1018'=>'product-dv-1018.webp',
            'MP3 X 2652'=>'product-x-2652.webp',
            'MP3 DV 1211S'=>'product-dv-1211s.webp',
            'Buraq 5'=>'product-buraq-5.webp',
            'ITELE BL 5C'=>'product-itele-bl5c.webp',
            'FASTER O3 S Z12 240'=>'product-faster-o3-s-z12-240.webp',
            'SIGMA F1 V8'=>'product-sigma-f1-v8.webp',
            'Sovo S008 Type-C'=>'product-sovo-s008-type-c.webp',
            'Sovo S008 V8'=>'product-sovo-s008-v8.webp',
            'Sovo S006 Type-C'=>'product-sovo-s006-type-c.webp',
            'SIGMA Clip1 V8'=>'product-sigma-clip1-v8.webp',
            'Block Hares C100'=>'product-block-hares-c100.webp',
            'V8 Cable'=>'product-v8-cable.webp',
            '313 8600 Cable'=>'product-313-8600-cable.webp',
            'Sovo SH15'=>'product-sovo-sh15.webp',
            'X Mart 2'=>'product-x-mart-2.webp',
            'Type-C IN V8 APKINA'=>'product-type-c-in-v8-apkina.webp',
            'Mobile Socket'=>'product-mobile-socket.webp',
            'R9 Ronin'=>'product-r9-ronin.webp',
            'FASTER S9'=>'product-faster-s9.webp',
            'FASTER C62 2-in-1'=>'product-faster-c62-2-in-1.webp',
            'FASTER FCC950'=>'product-faster-fcc950.webp',
            'SH04'=>'product-sh04.webp',
            'Login L600 V8'=>'product-login-l600-v8.webp',
            'Shock Sasta Cable'=>'product-shock-sasta-cable.webp',
            '65W A78 2-in-1 FAST'=>'product-65w-a78-2-in-1-fast.webp',
            'Amplyzo 2-in-1 45W AP43'=>'product-amplyzo-2-in-1-45w-ap43.webp',
            'Airbuds BUDS PRO8 ANC'=>'product-airbuds-buds-pro8-anc.webp',
            'Buds Pro 7'=>'product-buds-pro-7.webp',
            'Airbuds Black'=>'product-airbuds-black.webp',
            'MP3 Khaki Y30'=>'product-mp3-khaki-y30.webp',
        ];

        $pdo->beginTransaction();

        /*
         * 1. Make sure all required categories exist.
         */
        $categoryNames = [];

        foreach ($inventory as $row) {
            $categoryNames[$row[1]] = true;
        }

        $categoryInsert = $pdo->prepare(
            "INSERT IGNORE INTO categories (name, status)
             VALUES (?, 'active')"
        );

        foreach (array_keys($categoryNames) as $categoryName) {
            $categoryInsert->execute([$categoryName]);
        }

        /*
         * 2. Get category IDs.
         */
        $lookupStmt = $pdo->query(
            "SELECT name, id FROM categories WHERE status = 'active'"
        );

        $lookup = $lookupStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        /*
         * 3. Detect whether products table has a status column.
         */
        $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN, 0);
        $hasStatus = in_array('status', $columns, true);

        /*
         * 4. Prepare product statements.
         */
        $existingStmt = $pdo->prepare(
            "SELECT id FROM products WHERE name = ? LIMIT 1"
        );

        if ($hasStatus) {
            $insert = $pdo->prepare(
                "INSERT INTO products
                (category_id, name, selling_price, quantity, image_path, description, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')"
            );
        } else {
            $insert = $pdo->prepare(
                "INSERT INTO products
                (category_id, name, selling_price, quantity, image_path, description)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
        }

        /*
         * 5. Insert missing products.
         */
        foreach ($inventory as [$name, $category, $qty, $price]) {

            if (!isset($lookup[$category])) {
                continue;
            }

            $existingStmt->execute([$name]);

            if ($existingStmt->fetchColumn()) {
                continue;
            }

            $image = isset($images[$name])
                ? 'assets/images/' . $images[$name]
                : null;

            $insert->execute([
                $lookup[$category],
                $name,
                $price,
                $qty,
                $image,
                'Shop inventory item. Please contact the shop for exact compatibility and latest availability.'
            ]);
        }

        $pdo->commit();

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Catalogue bootstrap failed: ' .
            $e->getMessage()
        );
    }
}