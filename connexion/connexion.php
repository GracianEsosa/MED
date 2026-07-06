<?php
try {
    // Récupération des paramètres de connexion Railway
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $port = getenv('MYSQLPORT') ?: '3306';
    $dbname = getenv('MYSQLDATABASE') ?: 'gestion_mutualite';
    $username = getenv('MYSQLUSER') ?: 'root';
    $password = getenv('MYSQLPASSWORD') ?: '';

    // Connexion à la base de données
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    echo "<h1>📊 STRUCTURE DE LA BASE DE DONNÉES</h1>";
    echo "<hr>";

    // Récupérer toutes les tables
    $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
    $tables = $stmt->fetchAll();

    foreach ($tables as $table) {
        $tableName = $table['TABLE_NAME'];
        
        echo "<h2>📋 TABLE: <strong>$tableName</strong></h2>";
        echo "<table border='1' cellpadding='10' cellspacing='0' style='width:100%; margin-bottom:20px;'>";
        echo "<tr style='background-color:#f0f0f0;'>";
        echo "<th>Colonne</th>";
        echo "<th>Type</th>";
        echo "<th>Nullable</th>";
        echo "<th>Clé</th>";
        echo "<th>Extra</th>";
        echo "</tr>";

        // Récupérer les colonnes
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? 
             ORDER BY ORDINAL_POSITION"
        );
        $stmt->execute([$tableName]);
        $columns = $stmt->fetchAll();

        foreach ($columns as $col) {
            $nullable = $col['IS_NULLABLE'] === 'YES' ? '✓ NULL' : '✗ NOT NULL';
            $key = $col['COLUMN_KEY'] ? $col['COLUMN_KEY'] : '-';
            $extra = $col['EXTRA'] ? $col['EXTRA'] : '-';
            
            echo "<tr>";
            echo "<td><strong>{$col['COLUMN_NAME']}</strong></td>";
            echo "<td>{$col['COLUMN_TYPE']}</td>";
            echo "<td>$nullable</td>";
            echo "<td>$key</td>";
            echo "<td>$extra</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Récupérer les clés étrangères
        $stmt = $pdo->prepare(
            "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        $stmt->execute([$tableName]);
        $fks = $stmt->fetchAll();

        if (!empty($fks)) {
            echo "<h3>🔗 Clés étrangères:</h3>";
            echo "<ul>";
            foreach ($fks as $fk) {
                echo "<li><strong>{$fk['COLUMN_NAME']}</strong> → <strong>{$fk['REFERENCED_TABLE_NAME']}</strong>({$fk['REFERENCED_COLUMN_NAME']})</li>";
            }
            echo "</ul>";
        }

        // Récupérer les index
        $stmt = $pdo->query("SHOW INDEX FROM $tableName");
        $indexes = $stmt->fetchAll();
        
        $uniqueIndexes = array_filter($indexes, function($idx) {
            return $idx['Key_name'] !== 'PRIMARY';
        });

        if (!empty($uniqueIndexes)) {
            echo "<h3>📑 Index:</h3>";
            echo "<ul>";
            foreach ($uniqueIndexes as $idx) {
                echo "<li><strong>{$idx['Key_name']}</strong> on {$idx['Column_name']}</li>";
            }
            echo "</ul>";
        }

        echo "<hr>";
    }

    echo "<h2>✅ Total: " . count($tables) . " tables trouvées</h2>";

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
