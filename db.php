<?php
$db = new PDO('sqlite:books.db');

$db->exec(
    "CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    genre TEXT,
    year INTEGER,
    pages INTEGER
)");
?>