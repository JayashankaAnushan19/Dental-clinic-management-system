<?php 
session_start(); 
require ('connect-mysql.php'); 

/* set Connection */
$Connection = setConnection();

if (isset($_POST['submit'])) {

if (empty($_POST['FeedMsg'])) {
	echo "<script type='text/javascript'>alert('Empty Feedback')</script>";
}
elseif (!empty($_SESSION['userid'])) {
	

	/* Search user's mails*/
	runQuary("INSERT INTO `tb_feedback`(`feedback_userid`, `feedback_msg`, `feedback_rate`) VALUES ('".$_SESSION['userid']."','".$_POST['FeedMsg']."','".$_POST['FeedRate']."')","Feedback added successfully !!!");
 }

else{
echo "<script type='text/javascript'>alert('You colud be re-log to system.')</script>";
}
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

	
<div id="container">
	<h2  style="text-align: center; font-family: 'Lato', sans-serif; margin-bottom: 50px">Add feedback and rate our service.</h2>
	<form style="margin-top: 10px" class="form" method="post" >
		<label class="label"> Feedback : </label>
		<textarea class="textarea" style="min-height: 100px; min-width: 35%;" name="FeedMsg"></textarea>
		<br>
		<br>
		<label class="label"> Rate our service (0-5) : </label>
		<input type="text" style="min-height: 20px; min-width: 35%;" name="FeedRate">
		<br><br>
		<input id="submit" type="submit" name="submit" value="Submit Feedback">
	</form>
</div>
</body>
</html>