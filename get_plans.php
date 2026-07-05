<?php 
$c = mysqli_connect('localhost', 'root', '', 'arterastaging'); 
$r = mysqli_query($c, 'SELECT * FROM subscription_plan WHERE status = 1'); 
$p = []; 
while($row = mysqli_fetch_assoc($r)) { 
    $p[] = $row; 
} 
echo json_encode($p, JSON_PRETTY_PRINT); 
?>
