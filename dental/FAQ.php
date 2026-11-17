<!DOCTYPE html>
<html lang=en>
<head>
<title>FAQ</title>
<meta charset=utf-8>
<link rel="stylesheet" type="text/css" href="FAQ.css">
</head>
<body>
  <img src="download.png" width="40" height="40" class="logo">
    <p class="logotext">Dental Clinic</p>

	<h2  style="text-align: center; font-family: 'Lato', sans-serif; margin-bottom: 50px">FAQ - Frequently Asked Questions</h2>

<!-- Quections starting -->
<button type="button" class="collapsible">Quection 01</button>
<div class="content">
  <p>Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer </p>
</div>
<button type="button" class="collapsible">Quection 01</button>
<div class="content">
  <p>Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer </p>
</div>
<button type="button" class="collapsible">Quection 01</button>
<div class="content">
  <p>Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer </p>
</div>
<button type="button" class="collapsible">Quection 01</button>
<div class="content">
  <p>Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer Answer </p>
</div>
<!-- Quections starting -->

<!-- Collapsibling script -->
<script>
var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.display === "block") {
      content.style.display = "none";
    } else {
      content.style.display = "block";
    }
  });
}
</script>




</body>
</html>