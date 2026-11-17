<?php
session_start(); 

$_SESSION['userid'];
require ('connect-mysql.php'); 

/* Add appointment to the system */
$Connection = setConnection();
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
             $codes = trim($_POST['codes']);
             $dent = trim($_POST['dent']);
             $dates = trim($_POST['dates']);
             $time = trim($_POST['time']);


             // Run the query using function.
try {
  runQuary("INSERT INTO `appointement`(`userid`, `code`, `dentist`, `regdate`, `regtime`, `status`) VALUES ('".$_SESSION['userid']."', '$codes','$dent','$dates','$time','Not Confirmed')","Appointment Fixed successfully.");

  header("location: index1.html");

} catch (Exception $ex) {
  echo 'Caught exception: ', $ex -> getMessage(), "\n";
}
finally{
  // Close the database connection.
  mysqli_close($dbcon); 
}         

            } 

// End of the main Submit conditional.
?>

<style type="text/css">
  .outer{
  position: absolute;
  top: 10px;
  left: -10px;
  right: -10px;
}
select {
  float: right;
  width: 100%;
  margin-top: 5px;
  margin-bottom: 5px;
  padding-top: 5px;
  padding-bottom: 5px;
}
#submit{
  margin-top: 10px;
}
</style>

<html>
<head>
  <link rel="stylesheet" type="text/css" href="appointement.css">
<link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
</head>
<body>
  <a href="index1.html"><img src="download.png" width="40" height="40" class="logo"></a>
    <p class="logotext">Dental Clinic</p>
    <h2 class="page">Make Appointment</h2>

    <div class="outer">
<form action="appointement.php" method="post" class="form">


<!-- Search Dental treatment Code and their names -->
  <?php
  $Connection = setConnection();
  try
  {
    $myCommand = "SELECT `code`,`description` FROM `dentalcode`";
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

      <label for="codes">Dental Codes: <select name="codes" value="Select Value" >
<!-- Add Dental treatment Code and their names to the Dropdown menu-->
      <?php
      while ($record = mysqli_fetch_assoc($RecoResult)) 
      {
        $code=$record['code'];
        $describ = $record['description'];
      ?>
      <option><?php echo $code,"-",$describ ?> </option>

      <?php
      }
      ?>

      </select></label><br><br>


      
<!-- Search Dentist Code and name names -->
<?php
$Connection = setConnection();
  try
  {
    $myDenCommand = "SELECT `did`,`name` FROM `dentist`";
    $DenResult=mysqli_query($Connection, $myDenCommand);
    if ($DenResult) {
      $Denrecord = mysqli_fetch_assoc($DenResult);
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

  <!-- Add Dentist Code and name names to Drop down menu-->
      <label for="dent">Select Dentist: <select name="dent" value="Select Dentist" >
      <?php
      while ($Denrecord = mysqli_fetch_assoc($DenResult)) 
      {
        $Dencode=$Denrecord['did'];
        $DenName = $Denrecord['name'];
      ?>
      <option><?php echo $Dencode,"-",$DenName ?> </option>

      <?php
      }
      ?>
      </select></label><br><br>




<!-- Search Available dates -->
<?php
$Connection = setConnection();
  try
  {
    $myDateCommand = "SELECT `regdate` FROM `adminreg`";
    $DateResult=mysqli_query($Connection, $myDateCommand);
    if ($DateResult) {
      $Daterecord = mysqli_fetch_assoc($DateResult);
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
  <!-- Add Dentist Code and name names to Drop down menu-->
      <label for="dates">Select Dates: <select name="dates" value="sdgdfgsdf" >
      <?php
      while ($Daterecord = mysqli_fetch_assoc($DateResult)) 
      {
        $DateReg=$Daterecord['regdate'];
      ?>
      <option><?php echo $DateReg ?> </option>

      <?php
      }
      ?>
      </select></label><br><br>





      <label>Time:</label> <select name="time" id="hall" value="<?php if (isset($_POST['time'])) echo $_POST['time']; ?>" >
        <option>9 AM</option>
        <option>10 AM</option>
        <option>11 AM</option>
        <option>12 PM</option>
        <option>1 PM</option>
        <option>2 PM</option>
        <option>3 PM</option>
        <option>4 PM</option>
        <option>5 PM</option>
        <option>6 PM</option>
        <option>7 PM</option>  <!-- time for 24 houre -->
      </select>
      <br>
      <br>
      
      <input id="submit" type="submit" name="submit" value="Fix Appointment">

</form></div>
</body>
</html>