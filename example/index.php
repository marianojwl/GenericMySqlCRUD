<!DOCTYPE html>
<?php
spl_autoload_register(function ($c) { $f = '../' .  explode("\\",$c)[2]  . '.php'; if (file_exists($f)) require_once $f; });

use marianojwl\GenericMySqlCRUD\Database;
/**
 * ONLY SETUP
 */
$db = new Database("localhost","root","","muvidb", ["afiches_alta"] );
?>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <title><?=$db->getName()?></title>
</head>
<body>
<!-- NAV BAR / -->
<?php
$tables = $db->getTables();
?>
<nav class="navbar navbar-expand-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="?"><?=$db->getName()?></a>
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

<!-- MAIN SECTION -->
<main>
<div class="container mt-3">
<?php
/**
 * DETERMINES WHICH TABLE WE ARE WOKING ON
 */
$tableName = $_GET["table"] ?? "";
$table = $db->getTable($tableName);
if($table !== null) {
?>
    
        <?php
        switch($_GET['action']??"") {
            case "new":
                echo '<h3 class="mb-3">New record for '.$table->getName().'</h3>' . PHP_EOL;
                $table->tagClass("table table-striped table-bordered")->renderForm(); 
                break;
            case "view":
                echo '<h3 class="mb-3">View record from '.$table->getName().'</h3>' . PHP_EOL;
                $keyValue = $_GET[ $table->getPrimaryKey() ];
                $formValues = $table->getRecordByPrimaryKey( $keyValue );
                $table->renderForm( $formValues );
                break;
            case "edit":
                echo '<h3 class="mb-3">Edit record from '.$table->getName().'</h3>' . PHP_EOL;
                $keyValue = $_GET[ $table->getPrimaryKey() ];
                $formValues = $table->getRecordByPrimaryKey( $keyValue );
                $table->renderForm( $formValues );
                break;
            case "insert":
                    $table->insert();
                break;
            case "update":
                    $table->update();
                break;
            case "delete":
                    $table->delete();
                break;
            default:
            ?>
            <h3 class="mb-3">Records for <?=$table->getName()?></h3>
            <div class="my-3"><a class="btn btn-primary" href="?table=<?=$table->getName()?>&action=new">Add New</a></div>
            <div class="table-responsive">
            <?php $table->tagClass("table table-striped table-bordered table-responsive")->renderRecords(); ?> 
            </div>
            <?php
                break;
        }
        ?>        
    


<?php
} else {
  ?>
  <h3 class="mb-3">Available Tables</h3>
<div class="row">
  <?php
  foreach($db->getTables() as $table)
   echo '<div class="col">' . $table->tagClass("table table-striped table-bordered table-responsive")->showInfo() . '</div>' . PHP_EOL;
  ?>
</div>
  <?php
}
?>
</div>
</main>
<!-- / MAIN SECTION -->
</body>
</html>

