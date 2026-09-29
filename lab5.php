<?php
function connectDb()
{
    $dbFile = __DIR__ . '/guitar_shop.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function initDb()
{
    $pdo = connectDb();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS products (
            product_id INTEGER PRIMARY KEY AUTOINCREMENT,
            category VARCHAR(255) NOT NULL,
            product_code VARCHAR(255) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            list_price DECIMAL(10,2) NOT NULL
        )"
    );

    $exists = $pdo->query("SELECT COUNT(*) AS count FROM products")->fetch();
    if ((int) $exists['count'] === 0) {
        $pdo->prepare(
            "INSERT INTO products (category, product_code, product_name, list_price) VALUES (?, ?, ?, ?)"
        )->execute(['Guitars', 'test1', 'Test Product 2211', '550.00']);
    }
}

function saveProduct($category, $code, $name, $price)
{
    $pdo = connectDb();
    $stmt = $pdo->prepare(
        "INSERT INTO products (category, product_code, product_name, list_price) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$category, $code, $name, $price]);
    return $pdo->lastInsertId();
}

function getProducts()
{
    $pdo = connectDb();
    return $pdo->query(
        "SELECT product_id, category, product_code, product_name, list_price
         FROM products
         ORDER BY product_id DESC"
    )->fetchAll();
}

function getProductById($id)
{
    $pdo = connectDb();
    $stmt = $pdo->prepare(
        "SELECT product_id, category, product_code, product_name, list_price
         FROM products WHERE product_id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function formatCurrency($value)
{
    return '$' . number_format((float) $value, 2, '.', ',');
}

initDb();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $category = trim((string) $_POST['category']);
    $code = trim((string) $_POST['code']);
    $name = trim((string) $_POST['name']);
    $price = trim((string) $_POST['list_price']);

    if ($category !== '' && $code !== '' && $name !== '' && is_numeric($price)) {
        saveProduct($category, $code, $name, $price);
        $message = 'The product was added successfully.';
    } else {
        $message = 'Please enter valid product information.';
    }
}

$action = $_GET['action'] ?? 'menu';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guitar Shop</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f5efe7 0%, #eef3ff 100%);
            color: #222;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7e7e7;
        }

        h1, h2 {
            margin-top: 0;
            color: #1f2937;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
        }

        .menu {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin: 25px 0 30px;
        }

        .menu a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 22px;
            background: linear-gradient(135deg, #2d6cdf, #4b8df8);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(45, 108, 223, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .menu a:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(45, 108, 223, 0.3);
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .card {
            background: linear-gradient(180deg, #f9fbff, #f2f6ff);
            border: 1px solid #dfeaff;
            border-radius: 16px;
            padding: 22px;
            min-height: 170px;
        }

        .card-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: #e7f0ff;
            margin-bottom: 18px;
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 1.3rem;
        }

        .card p {
            margin: 0;
            color: #555;
            line-height: 1.6;
        }

        .section-box {
            background: #fbfcff;
            border: 1px solid #ebedf3;
            border-radius: 16px;
            padding: 24px;
            margin-top: 22px;
        }

        form {
            max-width: 620px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: #2b2b2b;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 18px;
            box-sizing: border-box;
            border: 1px solid #d9e1f2;
            border-radius: 10px;
            font-size: 1rem;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: #4b8df8;
            box-shadow: 0 0 0 4px rgba(75, 141, 248, 0.12);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 22px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #2d6cdf, #4b8df8);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(45, 108, 223, 0.22);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            overflow: hidden;
            border-radius: 12px;
        }

        th, td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #eef1f7;
        }

        th {
            background: #f4f7ff;
            color: #394b66;
            font-weight: 700;
        }

        tr:hover {
            background: #fafbff;
        }

        .success {
            color: #13795b;
            background: #eafaf4;
            border: 1px solid #bfead9;
            font-weight: bold;
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 10px;
        }

        .error {
            color: #b42318;
            background: #fff1f2;
            border: 1px solid #f5c2c7;
            font-weight: bold;
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 10px;
        }

        .muted {
            color: #666;
        }

        @media (max-width: 700px) {
            .container {
                padding: 20px 16px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($action === 'menu' || $action === 'home'): ?>
            <div class="page-header">
                <div class="page-title">🎸 Guitar Shop</div>
            </div>

            <div class="dashboard">
                <div class="card">
                    <div class="card-icon">📦</div>
                    <h3>Product Manager</h3>
                    <p>Thêm sản phẩm mới và quản lý dữ liệu trong cửa hàng guitar.</p>
                </div>

                <div class="card">
                    <div class="card-icon">🛒</div>
                    <h3>Product Catalog</h3>
                    <p>Xem danh sách sản phẩm hiện có và giá bán chi tiết.</p>
                </div>
            </div>

            <div class="menu">
                <a href="?action=manager">📦 Product Manager</a>
                <a href="?action=catalog">🛒 Product Catalog</a>
            </div>

        <?php elseif ($action === 'manager'): ?>
            <div class="page-header">
                <div class="page-title">📦 Product Manager</div>
            </div>

            <div class="menu">
                <a href="?action=menu">🏠 Main Menu</a>
                <a href="?action=catalog">🛒 Catalog</a>
            </div>

            <?php if ($message !== ''): ?>
                <div class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="section-box">
                <h2>➕ Add New Product</h2>
                <form method="post" action="?action=manager">
                    <input type="hidden" name="action" value="add_product">

                    <div class="form-row">
                        <div>
                            <label for="category">Category</label>
                            <input id="category" name="category" type="text" value="Guitars" required>
                        </div>
                        <div>
                            <label for="code">Code</label>
                            <input id="code" name="code" type="text" value="test1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="name">Name</label>
                            <input id="name" name="name" type="text" value="Test Product 2211" required>
                        </div>
                        <div>
                            <label for="list_price">List Price</label>
                            <input id="list_price" name="list_price" type="text" value="550.00" required>
                        </div>
                    </div>

                    <button class="button" type="submit">➕ Add Product</button>
                </form>
            </div>

        <?php elseif ($action === 'catalog'): ?>
            <div class="page-header">
                <div class="page-title">🛒 Product Catalog</div>
            </div>

            <div class="menu">
                <a href="?action=menu">🏠 Main Menu</a>
                <a href="?action=manager">📦 Product Manager</a>
            </div>

            <div class="section-box">
                <?php $products = getProducts(); ?>
                <?php if (empty($products)): ?>
                    <p class="muted">No products found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>📁 Category</th>
                                <th>🔤 Code</th>
                                <th>📝 Name</th>
                                <th>💰 Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['category']) ?></td>
                                    <td><?= htmlspecialchars($product['product_code']) ?></td>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><?= formatCurrency($product['list_price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
