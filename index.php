<?php
require 'db.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null; 

if ($action === 'add' && isset($_POST['title'])) {
    $stmt = $db->prepare("INSERT INTO books (title, author, genre, year, pages) VALUES (:t, :a, :g, :y, :p)");
    $stmt->execute([
        ':t' => $_POST['title'],
        ':a' => $_POST['author'],
        ':g' => $_POST['genre'],
        ':y' => $_POST['year'],
        ':p' => $_POST['pages']
    ]);
    header('Location: ?action=list'); 
    exit;
}

if ($action === 'edit' && $id && isset($_POST['title'])) {
    $stmt = $db->prepare("UPDATE books SET title=:t, author=:a, genre=:g, year=:y, pages=:p WHERE id=:i");
    $stmt->execute([
        ':t' => $_POST['title'],
        ':a' => $_POST['author'],
        ':g' => $_POST['genre'],
        ':y' => $_POST['year'],
        ':p' => $_POST['pages'],
        ':i' => $id
    ]);
    header('Location: ?action=view&id=' . $id);
    exit;
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM books WHERE id=?");
    $stmt->execute([$id]);
    header('Location: ?action=list');
    exit;
}

$new_books_stmt = $db->query("SELECT * FROM books WHERE year > 2020 ORDER BY year DESC");
$new_books = $new_books_stmt->fetchAll(PDO::FETCH_ASSOC);

$max_pages_stmt = $db->query("SELECT * FROM books ORDER BY pages DESC LIMIT 1");
$max_pages_book = $max_pages_stmt->fetch(PDO::FETCH_ASSOC);

$book = null;
if ($action === 'view' || $action === 'edit') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM books WHERE id=?");
        $stmt->execute([$id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$books = [];
if ($action === 'list') {
    $stmt_all = $db->query("SELECT * FROM books");
    $books = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Книжный каталог</title>
    <style>
        body { font-family: sans-serif; margin: 20px; line-height: 1.6; }
        table { width: 90%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        form div { margin-bottom: 10px; }
        label { display: inline-block; width: 120px; }
        input { width: calc(100% - 130px); }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<h1>Книжный каталог</h1>

<p><a href="?action=list">Список книг</a> | <a href="?action=add">Добавить книгу</a></p>

<?php if ($action === 'list'): ?>
    <?php if (!empty($books)): ?>
        <table>
            <tr><th>ID</th><th>Название</th><th>Автор</th><th>Жанр</th><th>Год</th><th>Страниц</th><th>Действия</th></tr>
            <?php foreach ($books as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><?= htmlspecialchars($b['title'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($b['author'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($b['genre'], ENT_QUOTES) ?></td>
                    <td><?= $b['year'] ?></td>
                    <td><?= $b['pages'] ?></td>
                    <td>
                        <a href="?action=view&id=<?= $b['id'] ?>">Просмотр</a> |
                        <a href="?action=edit&id=<?= $b['id'] ?>">Редактировать</a> |
                        <a href="?action=delete&id=<?= $b['id'] ?>" onclick="return confirm('Удалить книгу?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>В каталоге пока нет ни одной книги.</p>
    <?php endif; ?>

    <hr>
    <h2>Запросы к таблице:</h2>

    <h4>Книги, изданные после 2020 года:</h4>
    <?php if (!empty($new_books)): ?>
        <ul>
            <?php foreach ($new_books as $nb): ?>
                <li>"<?= htmlspecialchars($nb['title'], ENT_QUOTES) ?>" — <?= htmlspecialchars($nb['author'], ENT_QUOTES) ?> (<?= $nb['year'] ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Книг, изданных после 2020 года, не найдено.</p>
    <?php endif; ?>

    <h4>Книга с самым большим количеством страниц:</h4>
    <?php if ($max_pages_book): ?>
        <p>"<?= htmlspecialchars($max_pages_book['title'], ENT_QUOTES) ?>" — <?= $max_pages_book['pages'] ?> стр.</p>
    <?php else: ?>
        <p>Данные отсутствуют.</p>
    <?php endif; ?>

<?php elseif ($action === 'view' && $book): ?>
    <h2><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></h2>
    <p><strong>Автор:</strong> <?= htmlspecialchars($book['author'], ENT_QUOTES) ?></p>
    <p><strong>Жанр:</strong> <?= htmlspecialchars($book['genre'], ENT_QUOTES) ?></p>
    <p><strong>Год издания:</strong> <?= $book['year'] ?></p>
    <p><strong>Количество страниц:</strong> <?= $book['pages'] ?></p>
    <br>
    <a href="?action=list">Вернуться к списку</a>

<?php elseif ($action === 'add' || ($action === 'edit' && $book)): ?>
    <form method="post">
        <div>
            <label for="title">Название:</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($book['title'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div>
            <label for="author">Автор:</label>
            <input type="text" name="author" required value="<?= htmlspecialchars($book['author'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div>
            <label for="genre">Жанр:</label>
            <input type="text" name="genre" value="<?= htmlspecialchars($book['genre'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div>
            <label for="year">Год:</label>
            <input type="number" name="year" value="<?= htmlspecialchars($book['year'] ?? date('Y'), ENT_QUOTES) ?>">
        </div>
        <div>
            <label for="pages">Страниц:</label>
            <input type="number" name="pages" min="1" value="<?= htmlspecialchars($book['pages'] ?? '', ENT_QUOTES) ?>">
        </div>
        <button type="submit"><?= $action === 'add' ? 'Добавить' : 'Сохранить' ?></button>
    </form>
    <br>
    <a href="?action=list">Отмена</a>

<?php else: ?>
    <p>Ошибка: книга не найдена.</p>
    <a href="?action=list">Вернуться к списку</a>
<?php endif; ?>

</body>
</html>