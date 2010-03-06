<?php
/**
 * @author Alex10336
 * Dernière modification: $Id$
 * @license GNU Public License 3.0 ( http://www.gnu.org/licenses/gpl-3.0.txt )
 * @license Creative Commons 3.0 BY-SA ( http://creativecommons.org/licenses/by-sa/3.0/deed.fr )
 *
 **/

/**
 Array (
 Name		=> "MaPlanète"
 Coord		=> xxxx-xx-xx-xx
 *			=> Ressources prod/h
 current_*	=> Ressources dispo
 bunker_*	=> Ressources dans le bunker
 total_*		=> current_* + bunker_*
 sell_*		=> Ressources vendu/j
 percent_*	=> Ratio d'exploitation des ressources
 )
 "*" => Nom de ressource
 **/

class ownuniverse {
    static private $instance;

    protected $ressourcesnames = array('Titane', 'Cuivre', 'Fer', 'Aluminium', 'Mercure', 'Silicium', 'Uranium', 'Krypton', 'Azote', 'Hydrogene' => 'Hydrogène');
    protected $BatimentsName = array('control' => '[Centre de contrôle]', 'communication' => '[Centre de communication]', 'university' => '[Université]',
            'technology' => '[Centre de recherches]', 'gouv' => '[Centre gouvernemental]', 'defense' => '[Caserne]',
            'shipyard' => '[Chantier spatial]', 'spacedock' => '[Hangar de maintenance]', 'bunker' => '[Bunker]',
            'tradepost' => '[Poste de commerce]', 'ressource' => '[Complexe d\'extraction]');

    protected $universe_data;
    protected $ress_data;

    /**
     * @since 1.4.1
     */
    protected $race;

    /**
     * @since 1.4.2
     */
    protected $readonly=false;
    /**
     @param	array		sorted data
     @param	&array	output
     @param	int			Planets number
     @param	string	search key from
     @param	string	search key to
     @param	string	subkey to find
     @param	string	prefix when store in array
     @return	boolean
     **/
    private function GetRessFrom($data, &$output, $nb, $subkey, $from='', $to='', $prefix='') {
        if ($this->readonly) return false;
        $from_pos		= array_search($from, $data);
        $to_pos			= array_search($to, $data);
        if ($from_pos === false)
            $slice		= $data;
        elseif ($to_pos === false)
            $slice		= array_slice($data,$from_pos+1);
        else
            $slice		= array_slice($data,$from_pos+1, $to_pos-$from_pos-1);

        foreach($slice as $k => $v) $slice[$k] = trim($v); // chrome => ' $subkey'

        foreach($subkey as $a => $m) {
            $p=0;
            $k = array_search($m, $slice);
            if ($k === false) { // en cas d'érreur de 'subkey'
                echo "Error ownuniverse::GetRessFrom(., $from, $to: $p => $m\n".print_r($subkey,true).print_r($slice,true)."\n";
                xdebug_break();
                return false;
            }
            $tmp = array_slice($slice, $k+1, $nb);
            foreach($tmp as $v) {
                if (!is_numeric($a))
                    $output[$p]["$prefix$a"] = str_replace('.','',$v);
                else
                    $output[$p]["$prefix$m"] = str_replace('.','',$v);
                $p++;
            }
        }
        return true;
    }

    /**
     *	@param	string		Données brute...
     *	@param	array			Quelles donnée récupérer ?... (option)
     *	@return	array			Données dans un tableau
     **/
    public function parse_ownuniverse($data, $return=array(1,2,3,4,5,6)) {
        if ($this->readonly) return false;

        if (DataEngine::$browser->getBrowser() != Browser::BROWSER_IE)
            define('DATA_SEP',"\t\t");
        else
            define('DATA_SEP','  ');

        $parser = parser::getinstance();
        //        $portions = array();

        // Planètes présente...
        $tmp = $parser->GetInner($data, 'Description', 'Total');
        $tmp = $parser->cleaning_array(explode(DATA_SEP, $tmp));
        $nbplanets = count($tmp);
        $cleandata = array_fill(0,$nbplanets-1,array());
        foreach($tmp as $k => $v) $cleandata[$k]['Name'] = $v;

        // leurs coordonnées...
        $tmp = $parser->GetInner($data, 'Coordonnées', 'Distance vers');
        $tmp = $parser->cleaning_array(explode(DATA_SEP, $tmp));
        foreach($tmp as $k => $v) $cleandata[$k]['Coord'] = $v;

        // Batiments...
        $tmp = $parser->GetInner($data, 'Bâtiment (Niveaux de développement)', 'Ressources');
        $tmp = explode("\n", $tmp);
        $i = 0;
        foreach ($this->BatimentsName as $k => $v) {
            $tmp[$i] = trim($parser->GetInner($tmp[$i],$v)); // strip FF
            $tmp[$i] = $parser->cleaning_array(explode(DATA_SEP, $tmp[$i]));
            foreach($tmp[$i] as $p => $n) {
                if ($p == $nbplanets) break;
                $cleandata[$p][$k] = $n;
            }
            $i++;
        }

        // ressources sur planètes
        $tmp = $parser->GetInner($data, 'Ressources', 'Production par heure');
        $tmp = explode("\n", $tmp);
        $i = 0;
        foreach ($this->ressourcesnames as $k => $v) {
            $tmp[$i] = trim(preg_replace('/([^\d\.\s\t])/', '', $tmp[$i]));
            $tmp[$i] = $parser->cleaning_array(explode(DATA_SEP, $tmp[$i]));
            foreach($tmp[$i] as $p => $n) {
                if ($p == $nbplanets) break;
                if (is_numeric($k))
                    $cleandata[$p]['current_'.$v] = DataEngine::strip_number($n);
                else
                    $cleandata[$p]['current_'.$k] = DataEngine::strip_number($n);
            }
            $i++;
        }

        // Production par heure
        $tmp = $parser->GetInner($data, 'Production par heure', 'Ressources dans le bunker');
        $tmp = explode("\n", $tmp);
        $i = 0;
        foreach ($this->ressourcesnames as $k => $v) {
            $tmp[$i] = trim(preg_replace('/([^\d\.\s\t])/', '', $tmp[$i]));
            $tmp[$i] = $parser->cleaning_array(explode(DATA_SEP, $tmp[$i]));
            foreach($tmp[$i] as $p => $n) {
                if ($p == $nbplanets) break;
                if (is_numeric($k))
                    $cleandata[$p][''.$v] = DataEngine::strip_number($n);
                else
                    $cleandata[$p][''.$k] = DataEngine::strip_number($n);
            }
            $i++;
        }

        // Ressources dans le bunker
        $tmp = $parser->GetInner($data, 'Ressources dans le bunker', 'Approvisionnement du peuple par jour');
        $tmp = explode("\n", $tmp);
        $i = 0;
        foreach ($this->ressourcesnames as $k => $v) {
            $tmp[$i] = trim(preg_replace('/([^\d\.\s\t])/', '', $tmp[$i]));
            $tmp[$i] = $parser->cleaning_array(explode(DATA_SEP, $tmp[$i]));
            foreach($tmp[$i] as $p => $n) {
                if ($p == $nbplanets) break;
                if (is_numeric($k))
                    $cleandata[$p]['bunker_'.$v] = DataEngine::strip_number($n);
                else
                    $cleandata[$p]['bunker_'.$k] = DataEngine::strip_number($n);
            }
            $i++;
        }
        // Ventes par jours
        $tmp = $parser->GetInner($data, 'Approvisionement du peuple par jour', 'Prochain approvisionnement');
        $tmp = explode("\n", $tmp);
        $i = 0;
        foreach ($this->ressourcesnames as $k => $v) {
            $tmp[$i] = trim(preg_replace('/([^\d\.\s\t])/', '', $tmp[$i]));
            $tmp[$i] = $parser->cleaning_array(explode(DATA_SEP, $tmp[$i]));
            foreach($tmp[$i] as $p => $n) {
                if ($p == $nbplanets) break;
                if (is_numeric($k))
                    $cleandata[$p]['sell_'.$v] = DataEngine::strip_number($n);
                else
                    $cleandata[$p]['sell_'.$k] = DataEngine::strip_number($n);
            }
            $i++;
        }

        $this->universe_data = $cleandata;
        return $this->get_universe(false);
    }

    /**
     * Parsing donnée planète
     * @param	string
     */
    public function parse_planet($data) {
        if ($this->readonly) return false;
        //        $data = str_replace("\n\n", "\n", $data);

        if (DataEngine::$browser->getBrowser() == Browser::BROWSER_IE)
            $data = implode("\n",explode("      ",$data));

        $data = explode("\n",$data);
        foreach ($data as $k => $v)
            if (trim($v) != "")
                $result[] = trim($v);

        $this->GetRessFrom($result, $result2, 1, $this->ressourcesnames,
                'Détails ressources', '', 'percent_');

        $from_pos		= array_search('Informations planètes', $data);
        $to_pos			= array_search('Détails ressources', $data);
        $slice		= array_slice($data,$from_pos+1, $to_pos-$from_pos-2);
        foreach($slice as $v) {
            if ( strpos($v, 'Coordonnées') !== false )
                $result2['Coord'] = str_replace(':','-', trim(substr($v,13)) );
            if ( strpos($v, 'Surface d\'eau') !== false )
                $result2[0]['percent_water'] = trim(substr($v,15));
        }
        // utile ? (enlève les % des concentrations)
        foreach ($this->ressourcesnames as $k => $v) {
            if (!is_numeric($k)) $v = $k;
            $result2[0]["percent_$v"] = substr($result2[0]["percent_$v"],0,-1);
        }
        return $result2;
    }

    /**
     *  @param array		Tableau contenant tout...
     *  @return array		($info, $warn)
     **/
    public function add_ownuniverse($data) {
        if ($this->readonly) return false;
        $info=$warn="";
        $qnom  = sqlesc($_SESSION['_login']);
        foreach($data as $k => $v) {
            $qp[$k]			= sqlesc($v['Name']);
            $qc[$k]			= sqlesc($v['Coord']);
            $qdataa[$k]		= sqlesc(serialize($data[$k]));
        }

        $query = "SELECT data0 FROM SQL_PREFIX_ownuniverse where UTILISATEUR='$qnom'";
        $array = DataEngine::sql($query);
        if (mysql_num_rows($array) > 0) {
            $query = "UPDATE SQL_PREFIX_ownuniverse SET `planet0`='%s',`coord0`='%s',`data0`='%s', `planet1`='%s',`coord1`='%s',`data1`='%s', `planet2`='%s',`coord2`='%s',`data2`='%s', `planet3`='%s',`coord3`='%s',`data3`='%s', `planet4`='%s',`coord4`='%s',`data4`='%s' where UTILISATEUR='%s'";
            // Remettre les planètes a zéro ?  // , ress0='',ress1='',ress2='',ress3='',ress4=''
            $query = sprintf($query, $qp[0],$qc[0],$qdataa[0], $qp[1],$qc[1],$qdataa[1], $qp[2],$qc[2],$qdataa[2], $qp[3],$qc[3],$qdataa[3], $qp[4],$qc[4],$qdataa[4], $qnom);
            $array = DataEngine::sql($query);
            if (mysql_affected_rows() > 0)
                $info.="Information de votre univers mise a jour.<br/>";
            else
                $warn.="Aucune différence, donnée inchangée.<br/>";
        } else {
            $query    = "INSERT INTO SQL_PREFIX_ownuniverse (UTILISATEUR, `planet0`,`coord0`,`data0`, `planet1`,`coord1`,`data1`, `planet2`,`coord2`,`data2`, `planet3`,`coord3`,`data3`, `planet4`,`coord4`,`data4`) VALUES ('%s', '%s','%s','%s', '%s','%s','%s', '%s','%s','%s', '%s','%s','%s', '%s','%s','%s')";
            $query = sprintf($query,$qnom, $qp[0],$qc[0],$qdataa[0], $qp[1],$qc[1],$qdataa[1], $qp[2],$qc[2],$qdataa[2], $qp[3],$qc[3],$qdataa[3], $qp[4],$qc[4],$qdataa[4]);
            DataEngine::sql($query);
            $info .= "Information de votre univers ajouté.<br/>";
        }
        return array($info, $warn);
    }


    public function add_planet($id, $data) {
        if ($this->readonly) return false;
        $info=$warn="";
        $qnom  = sqlesc($_SESSION['_login']);

        $query = "SELECT planet{$id} as name FROM SQL_PREFIX_ownuniverse where UTILISATEUR='$qnom'";
        $sql_r = DataEngine::sql($query);
        if (mysql_num_rows($sql_r) > 0) {
            $name = mysql_fetch_assoc($sql_r);
            $query = "UPDATE SQL_PREFIX_ownuniverse SET `ress{$id}`='%s' where UTILISATEUR='%s'";
            $query = sprintf($query, sqlesc(serialize($data)), $qnom);
            $array = DataEngine::sql($query);
            if (mysql_affected_rows() > 0)
                $info.="Information de la planète {$name['name']} ajouté.<br/>";
            else
                $warn.="Aucune différence, donnée inchangée.<br/>";
        }
        return array($info, $warn);
    }

    public function get_universe($player) {
        if ($player) {
            unset ($this->universe_data);
            unset ($this->ress_data);
            $this->readonly = true;
        } else $player = $_SESSION['_login'];
        if (!$this->universe_data) {
            $qnom  = sqlesc($player, false);
            $query = "SELECT * FROM SQL_PREFIX_ownuniverse where UTILISATEUR='$qnom'";
            $sql_r = DataEngine::sql($query);
            $ligne = mysql_fetch_assoc($sql_r);
            for ($i=0;$i<5;$i++) {
                if (is_null($ligne["data$i"])) return false;
                $data[$i] = unserialize($ligne["data$i"]);

                if ($ligne["ress$i"]!='' && is_array(unserialize($ligne["ress$i"])))
                    $this->ress_data[$i] = unserialize($ligne["ress$i"]);
            }
            $this->universe_data = $data;
        }
        if (is_array($this->ress_data)) {
            $result = array();
            for ($i=0;$i<5;$i++)
                if (isset($this->universe_data[$i]) && isset($this->ress_data[$i])) {
                    $result[$i] = array_merge($this->universe_data[$i], $this->ress_data[$i]);
                } else {
                    $result[$i] = $this->universe_data[$i];
                }

            return $result;
        } else {
            return $this->universe_data;
        }
    }

    public function get_comlevel() {
        if (($data = $this->get_universe(false))===false) return false;

        $map = map::getinstance();

        $com_level = array();
        foreach($data as $k => $v) {
            $com_level[$k]['ss'] = $map->Parcours()->get_coords_part($v['Coord']);
            $com_level[$k]['level'] = $v['communication'];
        }
        return $com_level;
    }
    /**
     * @since 1.4.1
     */
    public function get_comlevelwithname() {
        if (($data = $this->get_universe())===false) return false;

        $map = map::getinstance();

        $com_level = array();
        foreach($data as $k => $v) {
            $com_level[$k]['ss'] = $map->Parcours()->get_coords_part($v['Coord']);
            $com_level[$k]['name'] = $v['Name'];
            $com_level[$k]['level'] = $v['communication'];
        }
        return $com_level;
    }

    public function get_race() {
        if ($this->race != '') return $this->race;

        $result = DataEngine::sql('SELECT Race FROM SQL_PREFIX_Membres WHERE Joueur=\''.$_SESSION['_login'].'\'');
        $line = mysql_fetch_assoc($result);
        $this->race = $line['Race'];
        return $this->race;
    }

    /// fonctions générique....

    /**
     *
     * @return ownuniverse
     */
    static public function getinstance() {
        if ( ! self::$instance )
            self::$instance = new self();

        return self::$instance;
    }

}