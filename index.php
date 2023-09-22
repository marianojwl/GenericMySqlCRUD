<!DOCTYPE html>
<?php
spl_autoload_register(function ($c) { $f = 'src/' .  explode("\\",$c)[2]  . '.php'; if (file_exists($f)) require_once $f; });

use marianojwl\GenericMySqlCRUD\Table;
$table = new Table("muvidb","peliculas");
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <title><?=$table->getDbName()?> :: <?=$table->getName()?></title>
    <style>
        body {
            background-color:#000;
        }
    </style>
</head>
<body>

    <div class="container mt-3">
        <?php
        switch($_GET['action']??"") {
            default:
                echo '<h2>New</h2>';
                break;
            case "edit":
                echo '<h2>Edit</h2>';
                break;
        }
        ?>
        <?php $table->renderForm(); ?> 
    </div>
    <div class="container mt-3">
        <h2>Records</h2>
        <?php $table->renderRecords(); ?> 
    </div>  
</body>
</html>

