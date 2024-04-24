<!-- Version 1.0.0 -->
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta name="description" content="Couleurs jours EDF tempo.">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <title>EDF Tempo</title>
    <style>
      body {background-color: powderblue;text-align:center;}
      main { display: flex;justify-content: center; align-items: end; width:100%; max-width:600px; margin:auto;}
      div.card { width:300px; max-width:100%; border-radius: 20px; display: flex; align-items: center; justify-content: center; height:150px; flex-direction: column;}
      div.prix { background-color: red; border-radius: 10px; margin: 5px 0px; font-size:70%;}
      h2 {font-size:1.2em;}
      p {margin:0px;}
      footer {padding-top:1em;}
    </style>
    <?php

      class MyDB extends SQLite3 {
        function __construct() {
          if (!file_exists("tempo.db")) {
            touch ("tempo.db");
            $cmd = "CREATE TABLE IF NOT EXISTS jours (jour_id INTEGER PRIMARY KEY AUTOINCREMENT, date DATE NOT NULL, num_day INTEGER NOT NULL)";
          }
          $this->open('tempo.db');
          if (isset($cmd)) { $this->exec("$cmd"); }
        }

        public function get_codeJour(string $jour) :int|false { // $jour = today | tomorrow
          if ($json = file_get_contents("https://www.api-couleur-tempo.fr/api/jourTempo/$jour")) {
            $obj = json_decode($json);
            return $obj->codeJour;
          }
          else return false;
        }
      }

      $db = new MyDB();

      $msg = "";
      
      date_default_timezone_set('Europe/Paris');
      $today = date("Y-m-d");

      $cj=0;

      $sql_query = "SELECT num_day from jours WHERE date = '$today'";

      if ($res=$db->query($sql_query)) {
        if ($row = $res->fetchArray()) {
          if ($row['num_day']) {
              $msg = $msg."<br/>La date existe 1";
              $cj = $row['num_day'];
          }
        }
        else {
          if ($cj = $db->get_codeJour("today")) {
            $db->query("INSERT INTO jours (date,num_day) values ('$today',$cj)");
          }
        }
      }

      $heure = date( "H",  time());
      if ($heure >= 11) {
        $Datetime = new Datetime('NOW', new DateTimeZone('Europe/Paris'));
        $Datetime->add(DateInterval::createFromDateString('1 day'));
        $tomorrow = $Datetime->format("Y-m-d");

        $sql_query = "SELECT num_day from jours WHERE date = '$tomorrow'";

        if ($res=$db->query($sql_query)) {

          $row = $res->fetchArray();

          if (isset($row['num_day'])) {
              $msg = $msg."<br/>La date existe 2";
              $cj1 = $row['num_day'];
          }
          else {
            if ($cj1 = $db->get_codeJour("tomorrow")) {
              $db->query("INSERT INTO jours (date,num_day) values ('$tomorrow',$cj1)");
            }
          }
        }
      }
    ?>
  </head>
  <body id='page'>
    <?php
      // echo $msg;
      $couleur = array( 1 => "bleu", 2 => "blanc", 3 => "rouge", );
      $bg_color = array( 1 => "blue", 2 => "aliceblue", 3 => "red", );
      $color = array( 1 => "aliceblue", 2 => "black", 3 => "aliceblue", );

      $tarif = array(
        "bleu"  => array("hc" => 0.1296, "hp" => 0.1609),
        "blanc" => array("hc" => 0.1486, "hp" => 0.1894),
        "rouge" => array("hc" => 0.1568, "hp" => 0.7562),
        );

      if ($heure > 6 and $heure < 22) { $periode = "hp"; }
      else                            { $periode = "hc"; }
      $a = $tarif[$couleur[$cj]][$periode];
      $w =100/0.7562*$a;
    ?>
    <header><h1>Tempo</h1></header>
    <main>
      <div>
        <div class=card style="color:<?php echo $color[$cj];?>; background-color:<?php echo $bg_color[$cj];?>">
          <h2>Aujourd'hui</h2>
          <p><?php echo $tarif[$couleur[$cj]]['hc']." / ".$tarif[$couleur[$cj]]['hp']." € ";?></p>
        </div>

        <div class=prix style="width:<?php echo $w;?>%;">
          <p><?php echo $a;?> €</p>
        </div>

        <?php
            if (isset($cj1)) {
        ?>
        <div class=card style="color:<?php echo $color[$cj1];?>; background-color:<?php echo $bg_color[$cj1];?>">        
          <h2>Demain</h2>
          <p><?php echo $tarif[$couleur[$cj1]]['hc']." / ".$tarif[$couleur[$cj1]]['hp']." €"; ?></p> 
        </div>
        <?php
            }
            else {
        ?>
        <div class=card style="background-image:url('bg.png'); color:white;">        
          <h2>Demain</h2>
          <p>Couleur du jour non disponible<br/>avant 11h00</p> 
        </div>
        <?php
            }
        ?>

      </div>
    </main>
    <footer>
      <p>Tarif bleu : 0,2068 / 0,2700 €</p>
    </footer>
  </body>
</html>
