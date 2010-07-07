<?php
/**
 * @author Alex10336
 * Dernière modification: $Id$
 * @license GNU Public License 3.0 ( http://www.gnu.org/licenses/gpl-3.0.txt )
 * @license Creative Commons 3.0 BY-SA ( http://creativecommons.org/licenses/by-sa/3.0/deed.fr )
 *
 **/
define('USE_AJAX',true);

require_once('../init.php');
require_once(INCLUDE_PATH.'Script.php');

if (!DataEngine::CheckPerms('CARTE_JOUEUR')) {
    $out=<<<o
<carte>
    <script>
    Carte.DetailsShow(false);
    alert('Accès requis manquant');
    </script>
</carte>
o;
    output::_DoOutput($out);
}
//Tracé Menu
if (!isset($_GET["ID"]) or $_GET["ID"] == "") {
    output::_DoOutput("<CarteDetails><content><![CDATA[Aucune donn&eacute;e a charger, retour :<a href='javascript:void();' onclick='Carte.DetailsShow(false)'>Carte</a>]]></content></CarteDetails>");
}
$sql = 'SELECT `TYPE`, `POSIN`, `POSOUT`, `COORDET`, `COORDETOUT`, `USER`, `EMPIRE`, `INFOS` from `SQL_PREFIX_Coordonnee` WHERE `INACTIF`=0 AND (`POSIN`='.intval($_GET['ID']).' OR `POSOUT`='.intval($_GET['ID']).') ORDER BY `USER`';
$mysql_result = DataEngine::sql($sql);

require_once(TEMPLATE_PATH.'cartedetails.tpl.php');

$tpl = tpl_cartedetails::getinstance();
$tpl->Setheader(intval($_GET["ID"]));
while ($ligne=mysql_fetch_array($mysql_result)) {
    $tpl->AddRow($ligne);
}
$tpl->DoOutput();


