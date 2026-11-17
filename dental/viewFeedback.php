<?php 
session_start(); 
require ('connect-mysql.php'); 

/* set Connection */
$Connection = setConnection();

  try
  {
    $myCommand = "SELECT `feedback_userid`, `feedback_msg`, `feedback_rate` FROM `tb_feedback`";
    $RecoResult=mysqli_query($Connection, $myCommand);
    if ($RecoResult) {
      $record = mysqli_fetch_assoc($RecoResult);
    }
    else {
      echo "<script type='text/javascript'>alert('Error')</script>";
    }
  }
  catch (exception $ex)
  {
    echo 'Caught exception: ', $ex -> getMessage(), "\n";
  }
  finally
  {
    mysqli_close($Connection);
  }
  ?>



<!DOCTYPE html>
<html lang=en>
<head>
<title>Feedback and Rate</title>
<meta charset=utf-8>
<link rel="stylesheet" type="text/css" href="offerToCustomer.css">
</head>
<body>
  <a href="index1.html"><img src="download.png" width="40" height="40" class="logo"></a>
    <p class="logotext">Dental Clinic</p>

	<h2  style="text-align: center; font-family: 'Lato', sans-serif; margin-bottom: 50px">Feedback and service rate review</h2>
<div id="container" class="" >
	<table class="table" style="min-width: 100%; border: 1px solid black">
		<tr class="table-row">
			<th class="table-head" style=" background-color: #f5f5dc; font-size: 20px">User ID</th>
			<th class="table-head" style=" background-color: #f5f5dc; font-size: 20px">Feedback</th>
			<th class="table-head" style=" background-color: #f5f5dc; font-size: 20px">Rate (0-5)</th>
		</tr>
<!-- Add Dental treatment Code and their names to the Dropdown menu-->
      <?php
      while ($record = mysqli_fetch_assoc($RecoResult)) 
      {
        $FedUserID=$record['feedback_userid'];
        $FedMsg = $record['feedback_msg'];
        $FedRate = $record['feedback_rate'];
      ?>

		<tr>
			<td style="font-size: 18px; padding-top: 5px; padding-bottom: 5px; text-align: center; "><?php echo($FedUserID) ?></td>
			<td style="font-size: 18px; padding-top: 5px; padding-bottom: 5px; "><?php echo($FedMsg) ?></td>
			<td style="font-size: 18px; padding-top: 5px; padding-bottom: 5px; text-align: center; "><?php echo($FedRate) ?></td>
		</tr>
      <?php
      }
      ?>

	</table>
</div>
</body>
</html>