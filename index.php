<!DOCTYPE html>
<?php
spl_autoload_register(function ($c) { $f = 'src/' .  explode("\\",$c)[2]  . '.php'; if (file_exists($f)) require_once $f; });

use marianojwl\GenericMySqlCRUD\Database;
use marianojwl\GenericMySqlCRUD\Table;
//$table = new Table("muvidb","peliculas");
$db = new Database("localhost","root","","muvidb");
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <title><?=$db->getName()?></title>
    <style>
        body {
            background-color:#000;
        }
    </style>
</head>
<body>
<!-- NAV BAR / -->
<?php
$tables = $db->getTables();
?>
<nav class="navbar navbar-expand-sm bg-dark navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><?=$db->getName()?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav">
        <?php
        foreach($tables as $table)
            echo '<li class="nav-item"><a class="nav-link" href="?table='.$table->getName().'">'.$table->getName().'</a></li>' . PHP_EOL
        ?>
      </ul>
    </div>
  </div>
</nav>
<!-- / NAV BAR -->




<?php
$tableName = $_GET["table"] ?? "";
$table = $db->getTable($tableName);
if($table !== null) {
?>
    <div class="container mt-3">
        <?php
        switch($_GET['action']??"") {
            default:
                echo '<h2>New</h2>' . PHP_EOL;
                $table->renderForm(); 
                break;
            case "edit":
                echo '<h2>Edit</h2>' . PHP_EOL;
                $keyValue = $_GET[ $table->getPrimaryKey() ];
                $formValues = $table->getRecordByPrimaryKey( $keyValue );
                $table->renderForm( $formValues );
                break;
            case "insert":
                    $table->insert();
                break;
        }
        ?>
        
    </div>
    <div class="container mt-3">
        <h2>Records</h2>
        <?php $table->renderRecords(); ?> 
    </div>  

<?php
}
?>
</body>
</html>

