<?php
/*
Copyright (c) 2012, Dafydd Crosby
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met: 

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer. 
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution. 

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those
of the authors and should not be interpreted as representing official policies, 
either expressed or implied, of this software project.
*/

//Include my PEAR path
set_include_path("." . PATH_SEPARATOR . ($UserDir = dirname($_SERVER['DOCUMENT_ROOT'])) . "/pear/php" . PATH_SEPARATOR . get_include_path());

// Use sqlite for $DB
try 
{
    /*** connect to SQLite database ***/

    $DB = new PDO("sqlite:tassimo.db");
}
catch(PDOException $e)
{
    echo $e->getMessage();
    echo "<br><br>Database -- NOT -- loaded successfully .. ";
    die( "<br><br>Query Closed !!! $error");
}


function get_ean_checkdigit($barcode){
    $sum = 0;
    for($i=(strlen($barcode));$i>0;$i--){
        $sum += (($i % 2) * 2 + 1 ) * substr($barcode,$i-1,1);
    }
    return (10 - ($sum % 10));
}

if (isset($_GET['bc'])) {
    $bcnum = $_GET['bc'];
    if ($bcnum > 0 || $bcnum < 100000) {
        $bcnum .= get_ean_checkdigit($bcnum);
    }
    require_once 'Image/Barcode2.php';
    $bc = new Image_Barcode2;
    $bc->draw($bcnum, "int25", "png");
    die();
}

function get_binary ($int) {
    return strrev(sprintf("%016b", $int));
}

$gc="get_ean_checkdigit";
$gb="get_binary";

$binary_string = 0;
// Ensure the number is valid
if (isset($_GET['int'])) {
    $int = intval($_GET['int']);
    if (($int > 0) && ($int < 1000000)) {
        // Lop off last number, which is a checksum
        $number = intval(substr(strval(sprintf("%6u",$int)), 0, -1));
        if ($number <= 65535) {
            $binary_string = get_binary($number);
        }
    }
}

echo <<<HTML
<html>
<head>
<title>The Tassimo Barcode Laboratory</title>
</head>
<body>
<table>
<tr>
<th>Name</th>
<th>Barcode</th>
<th>Binary</th>
</tr>
HTML;

$sql = "select * from discs order by name";
$q = $DB->query($sql);
$result = $q->fetchAll();
foreach($result as $tlink) {
    $number = intval(substr(strval(sprintf("%6u",$tlink['barcode'])), 0, -1));
    echo '<tr>';
    echo '<td>'.$tlink['name'].'</td>';
    echo '<td>'.$tlink['barcode'].' '.$number.'</td>';
    echo '<td><div style="font-family: Courier, monospace;">'.get_binary($number).'</div></td>';
    echo '</tr>';
}

echo <<<HTML
</table><br>
<form name="input" action="tassimo.php" method="get">
Barcode # (remove check digit): <input type="text" name="bc" />
<input type="submit" value="Submit" />
</form>
</body>
</html>
HTML;
?>
