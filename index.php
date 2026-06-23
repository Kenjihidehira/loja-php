<?php
session_start();

$products = require __DIR__ . '/products.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function money(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function findProduct(array $products, int $id): ?array
{
    foreach ($products as $product) {
        if ($product['id'] === $id) {
            return $product;
        }
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'add' && findProduct($products, $id)) {
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    }

    if ($action === 'remove' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]--;

        if ($_SESSION['cart'][$id] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    header('Location: index.php');
    exit;
}

$cartItems = [];
$subtotal = 0;

foreach ($_SESSION['cart'] as $id => $quantity) {
    $product = findProduct($products, (int) $id);

    if (!$product) {
        continue;
    }

    $total = $product['price'] * $quantity;
    $subtotal += $total;

    $cartItems[] = [
        'product' => $product,
        'quantity' => $quantity,
        'total' => $total,
    ];
}

$shipping = $subtotal > 0 && $subtotal < 300 ? 29.90 : 0;
$discount = $subtotal >= 600 ? $subtotal * 0.10 : 0;
$grandTotal = $subtotal + $shipping - $discount;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja PHP</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="hero">
        <nav>
            <strong>SetupStore</strong>
            <a href="#carrinho">Carrinho: <?= count($cartItems) ?> item(ns)</a>
        </nav>

        <section>
            <p class="eyebrow">Projeto PHP</p>
            <h1>Mini loja virtual com carrinho em sessão</h1>
            <p>Catálogo de produtos, cálculo de frete, desconto automático e carrinho persistente usando PHP puro.</p>
        </section>
    </header>

    <main class="layout">
        <section>
            <div class="section-title">
                <p class="eyebrow">Produtos</p>
                <h2>Escolha seu setup</h2>
            </div>

            <div class="products">
                <?php foreach ($products as $product): ?>
                    <article class="product" style="--color: <?= e($product['color']) ?>">
                        <div class="product-image">
                            <span><?= e(substr($product['name'], 0, 1)) ?></span>
                        </div>
                        <div>
                            <span class="category"><?= e($product['category']) ?></span>
                            <h3><?= e($product['name']) ?></h3>
                            <p><?= e($product['description']) ?></p>
                        </div>
                        <footer>
                            <strong><?= money($product['price']) ?></strong>
                            <form method="post">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                <button type="submit">Adicionar</button>
                            </form>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="cart" id="carrinho">
            <p class="eyebrow">Resumo</p>
            <h2>Seu carrinho</h2>

            <?php if (empty($cartItems)): ?>
                <p class="muted">O carrinho ainda está vazio.</p>
            <?php else: ?>
                <div class="cart-list">
                    <?php foreach ($cartItems as $item): ?>
                        <article class="cart-item">
                            <div>
                                <strong><?= e($item['product']['name']) ?></strong>
                                <span><?= $item['quantity'] ?> × <?= money($item['product']['price']) ?></span>
                            </div>
                            <div class="cart-actions">
                                <strong><?= money($item['total']) ?></strong>
                                <form method="post">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="id" value="<?= $item['product']['id'] ?>">
                                    <button class="ghost" type="submit">-</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <dl class="totals">
                    <div>
                        <dt>Subtotal</dt>
                        <dd><?= money($subtotal) ?></dd>
                    </div>
                    <div>
                        <dt>Frete</dt>
                        <dd><?= $shipping === 0.0 ? 'Grátis' : money($shipping) ?></dd>
                    </div>
                    <div>
                        <dt>Desconto</dt>
                        <dd>- <?= money($discount) ?></dd>
                    </div>
                    <div class="grand-total">
                        <dt>Total</dt>
                        <dd><?= money($grandTotal) ?></dd>
                    </div>
                </dl>

                <form method="post">
                    <input type="hidden" name="action" value="clear">
                    <button class="ghost full" type="submit">Limpar carrinho</button>
                </form>
            <?php endif; ?>
        </aside>
    </main>
</body>
</html>
