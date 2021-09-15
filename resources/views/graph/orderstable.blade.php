<?php
$old_date = with(new \Carbon\Carbon($oldDate))->format('d.m.Y');
$new_date = with(new \Carbon\Carbon($newDate))->format('d.m.Y');

$_SESSION['$old_date'] = $old_date;
$_SESSION['$new_date'] = $new_date;
$_SESSION['onesumRegion1C'] = $onesumRegion1C;
$_SESSION['onetotal1C'] = $onetotal1C;
$_SESSION['twosumRegion1C'] = $twosumRegion1C;
$_SESSION['twototal1C'] = $twototal1C;

\PC::debug($_SESSION);
?>
<table class="table table-bordered regions-table" id="myFCKtabl">
    <thead>
        <tr>
            <th>Региональный центр</th>
            <?php
            $datekey = array_keys($onesumRegion1C);
            $date1C =  $datekey[0];
            echo '<th>' . $date1C . '</th>';
                $datekey2 = array_keys($twosumRegion1C);
                $date1C2 = $datekey2[0];
                echo '<th>' . $date1C2 . '</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                $onesumRegion1CNames = [];
                foreach ($onesumRegion1C as $value) {
                    foreach ($value as $name => $summa) {
                        $onesumRegion1CNames[] = $name;
                        echo '<tr>';
                            echo '<td>';
                                echo $name;
                            echo '</td>';
                        foreach ($twosumRegion1C as $value2) {
                            foreach ($value2 as $name2 => $summa2) {
                                if ($name == $name2) {
                                    foreach ($summa as $sum1c) {
                                        foreach ($summa2 as $sum1c2) {
                                            echo '<td class="' . (($sum1c > $sum1c2) ? 'green' : 'red') . '">';
                                                echo $sum1c;
                                            echo '</td>';
                                            echo '<td class="' . (($sum1c < $sum1c2) ? 'green' : 'red') . '">';
                                                echo $sum1c2;
                                            echo '</td>';
                                        }
                                    }
                                }                                 
                            }
                        }
                    }
                }
                echo '</tr>';

                foreach ($twosumRegion1C as $value2) {
                    foreach ($value2 as $name2 => $summa2) {
                        if(!empty($name2) && !in_array($name2,$onesumRegion1CNames)){
                            echo '<tr>';
                            echo '<td>';
                            echo $name2;
                            echo '</td>';
                            echo '<td class="red">';
                            echo '0';
                            echo '</td>';
                            echo '<td class="green">';
                            echo $summa2['money'];
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                }
                echo '<tr><td>ИТОГО</td>';
                    echo '<td class="' . (($onetotal1C > $twototal1C) ? 'green' : 'red') . '">' . $onetotal1C . '</td>';
                    echo '<td class="' . (($onetotal1C < $twototal1C) ? 'green' : 'red') . '">' . $twototal1C . '</td>';
                echo '</tr>';
            ?>  
    </tbody>
</table>
