<?php
$totalLH = count($lastHeard);
?>
  <div class="panel panel-default">
  <!-- Standard-Panel-Inhalt -->
  <div class="panel-heading">Last Heard List of today's <?php echo $totalLH; ?> callsigns.</div>
  <!-- Tabelle -->
  <div class="table-responsive">  
  <table id="lastHeard" class="table table-condensed table-striped table-hover">
   <thead>
    <tr>
      <th>Time (UTC)</th>
      <th>Mode</th>
      <th>Callsign</th>
      <th>DSTAR-ID</th>
      <th>Target</th>
      <th>Source</th>
      <th>Dur (s)</th>
      <th>Loss</th>
      <th>BER</th>
    </tr>
   </thead>
   <tbody>
<?php
for ($i = 0;  ($i < $totalLH); $i++) {
		$listElem = $lastHeard[$i];
		echo"<tr>";
		$fechaHora = explode(" ",$listElem[0]);
		echo"<td nowrap>$fechaHora[1]</td>";
		echo"<td nowrap>$listElem[1]</td>";
		echo"<td nowrap>$listElem[2]</td>";
		echo"<td nowrap>$listElem[3]</td>";
		echo"<td nowrap>$listElem[4]</td>";
		if ($listElem[5] == "RF"){
			echo "<td nowrap><span class=\"label label-success\">RF</span></td>";
		}else{
			echo"<td nowrap>$listElem[5]</td>";
		}
		if ($listElem[6] == null) {
				echo'<td nowrap>transmitting</td><td></td><td></td>';
			} else if ($listElem[6] == "SMS") {
				echo'<td nowrap>sending or receiving SMS</td><td></td><td></td>';
			} else {
			echo"<td nowrap>$listElem[6]</td>";
			echo"<td nowrap>$listElem[7]</td>";
			echo"<td nowrap>$listElem[8]</td>";
		}
		echo"</tr>\n";
	}

?>
  </tbody>
  </table>
  </div>  
</div>

