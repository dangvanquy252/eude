<?php
/**
 * @author Alex10336
 * Dernière modification: $Id$
 * @license GNU Public License 3.0 ( http://www.gnu.org/licenses/gpl-3.0.txt )
 * @license Creative Commons 3.0 BY-SA ( http://creativecommons.org/licenses/by-sa/3.0/deed.fr )
 *
 **/

require_once('./init.php');
require_once(INCLUDE_PATH.'Script.php');

DataEngine::CheckPermsOrDie('MEMBRES_EDIT');

// TODO REMOVE ME & UPDATE tpl
$tabrace[0]='Cyborg';
$tabrace[1]='Humain';
$tabrace[2]='Jamozoïde';
$tabrace[3]='Magumar';
$tabrace[4]='Mosoran';
$tabrace[5]='Ozoidien';
$tabrace[6]='Plentropien';
$tabrace[7]='Weganien';
$tabrace[8]='Zuup';

if(isset($_POST['ModifJoueur0'])) {
    /*
        ModifJoueur : Caché id du joueur
        ModifGrade	-	OldGrade
        ModifPOS		-	OldPOS
        ModifPoints	-	OldPoints
        ModifDon		-	OldDon
        ModifRace		-	OldRace
        Modification des données pour tous les joueurs.
    */
    $i = 0;
    while(isset($_POST['ModifJoueur'.$i])) {
        $Joueur['ID']                = sqlesc($_POST['ModifJoueur'.$i]);
        $OldJoueur['ID']             = sqlesc($_POST['ModifJoueur'.$i]);
        $Joueur['ModifGrade']        = sqlesc($_POST['ModifGrade'.$i]);
        $OldJoueur['ModifGrade']     = sqlesc($_POST['OldGrade'.$i]);
        $Joueur['ModifPermission']   = sqlesc($_POST['ModifPermission'.$i]);
        $OldJoueur['ModifPermission']= sqlesc($_POST['OldPermission'.$i]);
        $Joueur['ModifPoints']       = sqlesc($_POST['ModifPoints'.$i]);
        $OldJoueur['ModifPoints']    = sqlesc($_POST['OldPoints'.$i]);
        $Joueur['ModifDon']          = sqlesc($_POST['ModifDon'.$i]);
        $OldJoueur['ModifDon']       = sqlesc($_POST['OldDon'.$i]);
        $Joueur['ModifRace']         = sqlesc($_POST['ModifRace'.$i]);
        $OldJoueur['ModifRace']      = sqlesc($_POST['OldRace'.$i]);
        $Joueur['Suppr']             = sqlesc($_POST['Suppr'.$i]);
        $Joueur['pass']              = sqlesc($_POST['pass'.$i]);
        $i++;

        $modif = $modifaxx = false;
        foreach($Joueur as $k => $v) {
            if ($k == 'ModifPermission' && $v != $OldJoueur[$k]) {
                $modifaxx = true;
                continue;
            }
            if($v != $OldJoueur[$k]) {
                $modif=true;
                break;
            }
        }
        if($modif) {
            DataEngine::sql_spool("INSERT INTO SQL_PREFIX_Membres_log(Joueur,Date,Points,Dons) SELECT Joueur,Date,Points,Don FROM SQL_PREFIX_Membres a WHERE a.Joueur='".$Joueur["ID"]."';");
            DataEngine::sql_spool("UPDATE SQL_PREFIX_Membres SET Points='".$Joueur['ModifPoints']."', Don='".$Joueur['ModifDon']."', Date=now(), Grade='".$Joueur['ModifGrade']."', Race='".$Joueur['ModifRace']."' WHERE Joueur='".$Joueur["ID"]."';");
        }
        if ($modifaxx)
            DataEngine::sql_spool('UPDATE SQL_PREFIX_Users SET Permission=\''.$Joueur['ModifPermission'].'\' WHERE Login=\''.$Joueur['ID'].'\'');

        if($Joueur['pass'] && DataEngine::CheckPerms('MEMBRES_NEWPASS')) {
            DataEngine::sql_spool('UPDATE SQL_PREFIX_Users SET Password=md5(\''.$Joueur['pass'].'\') WHERE Login=\''.$Joueur['ID'].'\'');
        }

        if($Joueur['Suppr'] && DataEngine::CheckPerms('MEMBRES_DELETE')) {
            DataEngine::DeleteUser($Joueur['ID']);
        }
    } //while

    if (DataEngine::has_sql_spool()) DataEngine::sql_do_spool();
} //if

$mysql_result = DataEngine::sql('SELECT * from SQL_PREFIX_Grade ORDER BY Rattachement,Niveau');
$i=0;
while ($ligne=mysql_fetch_assoc($mysql_result))
    $Grades[] = $ligne;

//***********
// GESTION DES TRIS
//**********

$Order = ' ORDER BY Joueur';
$TriMembre	= $_GET['TriMembre'];
$TriGrade	= $_GET['TriGrade'];
$TriPermission	= $_GET['TriPermission'];
$TriPoints	= $_GET['TriPoints'];
$TriRace	= $_GET['TriRace'];
$TriShip	= $_GET['TriShip'];
$TriModif	= $_GET['TriModif'];
$TriDons	= $_GET['TriDons'];
if($TriMembre != '') {
    if($TriMembre==1)	$Order = ' ORDER BY JOUEUR';
    else $Order = ' ORDER BY JOUEUR DESC';
} else $TriMembre='0';
if($TriGrade != '') {
    if($TriGrade==1)	$Order = ' ORDER BY GRADE';
    else $Order = ' ORDER BY GRADE DESC';
} else $TriGrade='0';
if($TriPermission != '') {
    if($TriPermission==1)	$Order = ' ORDER BY u.Permission';
    else $Order = ' ORDER BY u.Permission DESC';
} else $TriPermission='0';
if($TriPoints != '') {
    if($TriPoints==1)	$Order = ' ORDER BY Points';
    else $Order = ' ORDER BY Points DESC';
} else $TriPoints='0';
if($TriDons != '') {
    if($TriDons==1)	$Order = ' ORDER BY Don';
    else $Order = ' ORDER BY Don DESC';
} else $TriDons='0';
if($TriRace != '') {
    if($TriRace==1)	$Order = ' ORDER BY Race';
    else $Order = ' ORDER BY Race DESC';
} else $TriRace='0';
if($TriShip != '') {
    if($TriShip==1)	$Order = ' ORDER BY ship';
    else $Order = ' ORDER BY ship DESC';
} else $TriShip='0';
if($TriModif != '') {
    if($TriModif==1)	$Order = ' ORDER BY Date';
    else $Order = ' ORDER BY Date DESC';
} else $TriModif='0';

$where = '';
if ($_GET['Joueur'] != '') {
    $where = ' AND m.Joueur=\''.sqlesc($_GET['Joueur']).'\'';
}

$axx = array();
foreach (DataEngine::s_perms() as $k => $v) {
    if ($k == AXX_DISABLED) continue;
    if (DataEngine::CurrentPerms() > $k || DataEngine::CurrentPerms() == AXX_ROOTADMIN)
        $axx[$k] = $v;
}
require_once(TEMPLATE_PATH.'editmembres.tpl.php');
$tpl = tpl_editmembres::getinstance();
$tpl->page_title = 'EU2: Membres (Éditions)';

$mysql_result = DataEngine::sql('SELECT m.*,u.Permission from SQL_PREFIX_Membres m, SQL_PREFIX_Users u WHERE (m.Joueur=u.Login)'.$where.$Order);

if (mysql_num_rows($mysql_result) == 0)
    output::Boink('Membres.php');

$tpl->header($myget, $TriMembre, $triPermission, $TriPoints,
    $TriDons, $TriRace, $TriShip, $TriModif);

$i=0;
while ($ligne=mysql_fetch_assoc($mysql_result))
    $tpl->row($i++, $ligne, $Grades, $tabrace, $axx);

$tpl->footer();
$tpl->DoOutput();