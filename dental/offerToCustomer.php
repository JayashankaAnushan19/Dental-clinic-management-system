<?php 
require ('connect-mysql.php'); 

/* set Connection */
$Connection = setConnection();

if (isset($_POST['submit'])) {

	/* Search user's mails*/
	$serachMails = 'SELECT `email` FROM `signup` WHERE `user_level`=0';
	$SearchMailResult = mysqli_query($Connection, $serachMails);
	if ($SearchMailResult) {
		$mailRecords = mysqli_fetch_assoc($SearchMailResult);
	}
	else{
		echo "<script type='text/javascript'>alert('Error')</script>";
	}
    while ($mailRecords = mysqli_fetch_assoc($SearchMailResult)) 
    {
    	 /* Enter code to send emails to user*/
        $sentToThisMail = $mailRecords['email'];
        $msgOffer = "New Offer (Subject) : ".$_POST['OfferSub']. " <br> " .$_POST['offerMsg'];

// Enter code to send a really e-mail using SMTP and port 25...

    }  /* Mail sending process end */

 	echo"<script type='text/javascript'>alert('User's informed about the Offer')</script>";

 }

 else
 {
 	/* Do nothing*/
 } 
?>

<!DOCTYPE html>
<html lang=en>
<head>
<title>Offer to customer</title>
<meta charset=utf-8>
<link rel="stylesheet" type="text/css" href="offerToCustomer.css">
</head>
<body>
  <a href="index1.html"><img src="download.png" width="40" height="40" class="logo"></a>
    <p class="logotext">Dental Clinic</p>

	
<div id="container">
	<h2  style="text-align: center; font-family: 'Lato', sans-serif;">Add offer</h2>
	<form style="margin-top: 10px" class="form" method="post" >
		<label class="label"> Offer Subject : </label>
		<textarea class="textarea" style="min-height: 50px; min-width: 35%;" name="OfferSub"></textarea>
		<br>
		<br>
		<label class="label"> Enter message : </label>
		<textarea class="textarea" style="min-height: 300px; min-width: 35%;" name="offerMsg"></textarea>
		<br>
		<input id="submit" type="submit" name="submit" value="Publish Offer">
	</form>
</div>
</body>
</html>