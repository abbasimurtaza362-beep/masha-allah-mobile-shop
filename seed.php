<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (!in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1','::1'], true)) {
    http_response_code(404); exit('Not found.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?><!doctype html><meta charset="utf-8"><title>Initialize shop data</title>
    <style>body{font-family:Arial;max-width:620px;margin:60px auto;padding:20px}button{padding:12px 18px}</style>
    <h1>Initialize shop data</h1><p>The catalogue now initializes automatically on the first local site request. This page remains only as a manual recovery tool.</p>
    <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button>Initialize catalogue</button></form><?php exit;
}
verify_csrf();
$inventory = [
['2040 GTS MP3','Speakers',5,1099],['MP3 GTS 2068','Speakers',1,1099],['MP3 KIX 1477','Speakers',9,1399],['KS43','Earphones',10,199],['ST12','Earphones',50,149],['MARS MP1 8000','Speakers',5,1299],['MP3 2 KTS 2295','Speakers',4,1099],['MP3 1251 KTS','Speakers',5,1149],['SIGMA TC1 V8','Cables',15,299],['SIGMA TC2','Cables',13,399],['Goodlife GL24','Cables',35,149],['Ronin R2025 Type-C','Cables',10,399],['Ronin R2025 V8','Cables',10,349],['Ronin R2025 iPhone','Cables',4,449],['Ronin R2045 Type-C to iPhone','Cables',1,599],['MP3 DV 1185','Speakers',2,1199],['MP3 219 Mini','Speakers',2,1099],['MP3 DV 1018','Speakers',2,1099],['MP3 X 2652','Speakers',1,1049],['MP3 DV 1211S','Speakers',1,1099],['Buraq 5','Batteries',17,299],['ITELE BL 5C','Batteries',10,199],['FASTER O3 S Z12 240','Cables',5,299],['SIGMA F1 V8','Cables',10,279],['Sovo S008 Type-C','Cables',4,449],['Sovo S008 V8','Cables',4,399],['Sovo S006 Type-C','Cables',4,299],['SIGMA Clip1 V8','Cables',9,699],['Block Hares C100','Chargers',25,399],['V8 Cable','Cables',50,99],['313 8600 Cable','Cables',50,119],['Sovo SH15','Cables',7,299],['X Mart 2','Chargers',4,499],['Type-C IN V8 APKINA','Cables',2,799],['Mobile Socket','Repair',2,299],['R9 Ronin','Cables',3,699],['FASTER S9','Chargers',5,499],['FASTER C62 2-in-1','Cables',11,599],['FASTER FCC950','Chargers',5,799],['SH04','Chargers',2,599],['Login L600 V8','Cables',1,749],['Shock Sasta Cable','Cables',10,30],['65W A78 2-in-1 FAST','Chargers',1,699],['Amplyzo 2-in-1 45W AP43','Chargers',1,599],['Airbuds BUDS PRO8 ANC','TWS',6,1199],['Buds Pro 7','TWS',4,1199],['Airbuds Black','TWS',2,1299],['MP3 Khaki Y30','Speakers',6,599],
];
$images = [
    '2040 GTS MP3' => 'assets/images/product-2040-gts-mp3.png',
    'MP3 GTS 2068' => 'assets/images/product-2068-gts-mp3.png',
    'MP3 KIX 1477' => 'assets/images/product-1477-kix-mp3.png',
    'KS43' => 'assets/images/product-ks43-earphones.png',
    'ST12' => 'assets/images/product-st12-earphones.png',
    'MARS MP1 8000' => 'assets/images/product-mars-mp1-8000.png',
    'MP3 2 KTS 2295' => 'assets/images/product-kts-2295.png',
    'MP3 1251 KTS' => 'assets/images/product-kts-1251.png',
    'MP3 DV 1185' => 'assets/images/product-dv-1185.png',
    'MP3 219 Mini' => 'assets/images/product-219-mini.png',
    'MP3 DV 1018' => 'assets/images/product-dv-1018.png',
    'MP3 X 2652' => 'assets/images/product-x-2652.png',
    'MP3 DV 1211S' => 'assets/images/product-dv-1211s.png',
    'Buraq 5' => 'assets/images/product-buraq-5.png',
    'ITELE BL 5C' => 'assets/images/product-itele-bl5c.png',
    'SIGMA TC1 V8' => 'assets/images/product-sigma-tc1-v8.png',
    'SIGMA TC2' => 'assets/images/product-sigma-tc2.png',
    'Goodlife GL24' => 'assets/images/product-goodlife-gl24.png',
    'Ronin R2025 Type-C' => 'assets/images/product-ronin-r2025-type-c.png',
    'Ronin R2025 V8' => 'assets/images/product-ronin-r2025-v8.png',
    'Ronin R2025 iPhone' => 'assets/images/product-ronin-r2025-iphone.png',
    'Ronin R2045 Type-C to iPhone' => 'assets/images/product-ronin-r2045-type-c-to-iphone.png',
    'FASTER O3 S Z12 240' => 'assets/images/product-faster-o3-s-z12-240.png',
    'SIGMA F1 V8' => 'assets/images/product-sigma-f1-v8.png',
    'Sovo S008 Type-C' => 'assets/images/product-sovo-s008-type-c.png',
    'Sovo S008 V8' => 'assets/images/product-sovo-s008-v8.png',
    'Sovo S006 Type-C' => 'assets/images/product-sovo-s006-type-c.png',
    'SIGMA Clip1 V8' => 'assets/images/product-sigma-clip1-v8.png',
    'Block Hares C100' => 'assets/images/product-block-hares-c100.png',
    'V8 Cable' => 'assets/images/product-v8-cable.png',
    '313 8600 Cable' => 'assets/images/product-313-8600-cable.png',
    'Sovo SH15' => 'assets/images/product-sovo-sh15.png',
    'X Mart 2' => 'assets/images/product-x-mart-2.png',
    'Type-C IN V8 APKINA' => 'assets/images/product-type-c-in-v8-apkina.png',
    'Mobile Socket' => 'assets/images/product-mobile-socket.png',
    'R9 Ronin' => 'assets/images/product-r9-ronin.png',
    'FASTER S9' => 'assets/images/product-faster-s9.png',
    'FASTER C62 2-in-1' => 'assets/images/product-faster-c62-2-in-1.png',
    'FASTER FCC950' => 'assets/images/product-faster-fcc950.png',
    'SH04' => 'assets/images/product-sh04.png',
    'Login L600 V8' => 'assets/images/product-login-l600-v8.png',
    'Shock Sasta Cable' => 'assets/images/product-shock-sasta-cable.png',
    '65W A78 2-in-1 FAST' => 'assets/images/product-65w-a78-2-in-1-fast.png',
    'Amplyzo 2-in-1 45W AP43' => 'assets/images/product-amplyzo-2-in-1-45w-ap43.png',
    'Airbuds BUDS PRO8 ANC' => 'assets/images/product-airbuds-buds-pro8-anc.png',
    'Buds Pro 7' => 'assets/images/product-buds-pro-7.png',
    'Airbuds Black' => 'assets/images/product-airbuds-black.png',
    'MP3 Khaki Y30' => 'assets/images/product-mp3-khaki-y30.png',
];
$images = array_map(static fn (string $path): string => str_replace('.png', '.webp', $path), $images);
$pdo = db();
$serviceImages = ['Mobile Accessories' => 'assets/images/service-mobile-accessories.webp', 'Mobile Repairing' => 'assets/images/service-mobile-repairing.webp', 'Mobile Software Services' => 'assets/images/service-mobile-software-services.webp', 'EasyPaisa Services' => 'assets/images/service-easypaisa.webp', 'JazzCash Services' => 'assets/images/service-jazzcash.webp'];
$updateServiceImage = $pdo->prepare('UPDATE services SET image_path = ? WHERE name = ?');
foreach ($serviceImages as $serviceName => $imagePath) $updateServiceImage->execute([$imagePath, $serviceName]);
$lookup = $pdo->query('SELECT name,id FROM categories')->fetchAll(PDO::FETCH_KEY_PAIR);
$exists = $pdo->prepare('SELECT id FROM products WHERE name = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO products (category_id,name,selling_price,quantity,image_path,description) VALUES (?,?,?,?,?,?)');
foreach ($inventory as [$name,$category,$qty,$price]) { $exists->execute([$name]); if (!$exists->fetch()) $insert->execute([$lookup[$category],$name,$price,$qty,$images[$name] ?? null,'Shop inventory item. Please contact the shop for exact compatibility and latest availability.']); }
echo 'Inventory seed complete: ' . count($inventory) . ' supplied products checked.';
